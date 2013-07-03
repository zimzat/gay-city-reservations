<?php

/**
 * Interface to handle Calendar Resource and API access.
 */
class Application_Model_Calendar
{
	/**
	 * Google API Client object (cached)
	 *
	 * @see _getApiClient
	 * @var null|apiClient
	 */
	protected $_apiClient = null;

	/**
	 * Google API Calendar object (cached)
	 *
	 * @see _getCalendarService
	 * @var null|apiCalendarService
	 */
	protected $_calendarService = null;

	/**
	 * List the types of potential reservation events.
	 *
	 * @return array
	 */
	public function getTypes()
	{
		return Zend_Registry::get('AppConfig')->app->calendar->typeDescription->toArray();
	}

	/**
	 * List available upcoming reservation dates
	 *
	 * @return array
	 */
	public function getAvailableDates()
	{
		$events = $this->_getAvailableEvents();

		// Same-day reservations are not allowed.
		unset($events[date('Y-m-d')]);

		return array_keys($events);
	}

	/**
	 * List available upcoming reservation times for a specific date
	 *
	 * @return array
	 */
	public function getAvailableTimes($date)
	{
		$events = $this->_getAvailableEvents();

		return array_keys($events[$date]);
	}

	/**
	 * Reserve an open appointment using the provided contact information.
	 *
	 * @return boolean
	 */
	public function reserveAppointment($type, $date, $time, Array $contact)
	{
		$calendarId = $this->_getImprint()->calendarId;

		$events = $this->_getAvailableEvents(true);
		if (empty($events[$date][$time])) {
			return false;
		}

		$calendarService = $this->_getCalendarService();
		$eventId = reset($events[$date][$time]);

		$keyMatch = array(
			'phone' => 'Phone',
			'phoneMessageConsent' => 'Phone Message Consent',
			'email' => 'Email',
			'contactMethod' => 'Preferred Contact Method',
		);

		$description = '';
		if (!empty($contact['phone'])) {
			$description .= 'Phone: ' . preg_replace('#\D#', '', $contact['phone']) . "\n";
			$description .= 'Phone Consent: ' . (!empty($contact['phoneMessageConsent']) ? 'Yes' : 'No') . "\n";
		}
		if (!empty($contact['email'])) {
			$description .= 'Email: ' . $contact['email'] . "\n";
		}
		if (!empty($contact['email']) || !empty($contact['phone'])) {
			$methodDescription = array(
				'' => 'None',
				'phone' => 'Phone',
				'email' => 'Email',
			);
			$description .= 'Contact Method: ' . $methodDescription[$contact['contactMethod']] . "\n";
		}

		$event = new Event($calendarService->events->get($calendarId, $eventId));
		$event->setSummary($type . '/' . $contact['name']);
		$event->setDescription($description);

		$calendarService->events->update($calendarId, $eventId, $event);

		$cache = Zend_Controller_Front::getInstance()
			->getParam('bootstrap')
			->getPluginResource('cachemanager')
			->getCacheManager()
			->getCache('calendar');
		$cache->remove('events');

		return true;
	}

	/**
	 * Use the api and calendar services to authorize permission.
	 *
	 * @return boolean
	 */
	public function imprintPermissions()
	{
		$apiClient = $this->_getApiClient();
		$calendarService = $this->_getCalendarService();

		if (isset($_GET['code'])) {
			$apiClient->authenticate();
			$_SESSION['token'] = $apiClient->getAccessToken();
		}

		if (isset($_SESSION['token'])) {
			$apiClient->setAccessToken($_SESSION['token']);
		}

		if ($apiClient->getAccessToken()) {
			if (isset($_GET['calendarId'])) {
				$imprint = new Zend_Config(array(
					'permissions' => array(
						'calendarId' => $_GET['calendarId'],
						'apiToken' => $_SESSION['token'],
					),
				));
				$writer = new Zend_Config_Writer_Json();
				$writer->write(APPLICATION_PATH . '/../data/calendarImprint.json', $imprint);
				chmod(APPLICATION_PATH . '/../data/calendarImprint.json', 0666);

				return true;
			} else {
				return $calendarService->calendarList->listCalendarList();
			}
		} else {
			header('Location: ' . $apiClient->createAuthUrl());
		}
	}

	/**
	 * Determine upcoming reserved events ready for email notification.
	 *
	 * @return array
	 */
	public function getEventsForEmailNotification()
	{
		$calendarService = $this->_getCalendarService();
		$calendarId = $this->_getImprint()->calendarId;

		$events = $calendarService->events->listEvents($calendarId, array(
			'q' => 'Contact Method:',
			'singleEvents' => 'true',
			'fields' => 'items(end,id,recurrence,recurringEventId,start,status,summary,description)',
			'timeMin' => date('c', time()),
			'timeMax' => date('c', strtotime('now, +1 day')),
		));

		$notifications = array();
		foreach ($events['items'] as $event) {
			foreach (explode("\n", $event['description']) as $line) {
				$pair = explode(':', $line, 2);
				if (count($pair) != 2) {
					continue;
				}

				list ($key, $value) = $pair;
				$event['reservation'][$key] = trim($value);
			}

			if (isset($event['reservation']['Contact Method'])) {
				switch ($event['reservation']['Contact Method']) {
					case 'Email':
						$notifications['email'][] = $event;
						break;

					case 'Phone':
						$notifications['phone'][] = $event;
						break;
				}
			}
		}

		return $notifications;
	}

	/**
	 * Massage event data into more user-friendly details.
	 *
	 * @param array $event
	 * @return array
	 */
	public function generateDetailsFromEvent($event)
	{
		return array(
			'time' => date('H:i', strtotime($event['start']['dateTime'])) . '-' . strtotime('H:i', strtotime($event['end']['dateTime'])),
			'date' => date('Y-m-d', strtotime($event['start']['dateTime'])),
			'name' => substr($event['summary'], strpos($event['summary'], '/') + 1),
			'type' => substr($event['summary'], 0, strpos($event['summary'], '/')),
			'phone' => !empty($event['reservation']['Phone']) ? $event['reservation']['Phone'] : '',
			'phoneMessageConsent' => !empty($event['reservation']['Phone Consent']) && $event['reservation']['Phone Consent'] == 'Yes',
			'email' => !empty($event['reservation']['Email']) ? $event['reservation']['Email'] : '',
			'contactMethod' => !empty($event['reservation']['Contact Method']) ? $event['reservation']['Contact Method'] : '',
		);
	}

	/**
	 * Get available events, optionally forcing a server refresh of known events.
	 *
	 * @param boolean $refresh
	 * @return array
	 */
	protected function _getAvailableEvents($refresh = false)
	{
		/* @var $cache Zend_Cache_Core */
		$cache = Zend_Controller_Front::getInstance()
			->getParam('bootstrap')
			->getPluginResource('cachemanager')
			->getCacheManager()
			->getCache('calendar');

		if ($refresh || ($events = $cache->load('events')) === false) {
			$events = $this->_fetchEvents();

			$cache->save($events, 'events');
		}

		return $events;
	}

	/**
	 * Fetch all events from the service.
	 *
	 * @return array
	 */
	protected function _fetchEvents()
	{
		$calendarService = $this->_getCalendarService();
		$calendarId = $this->_getImprint()->calendarId;

		$events = $calendarService->events->listEvents($calendarId, array(
			'q' => 'Available',
			'singleEvents' => 'true',
			'fields' => 'items(end,id,recurrence,recurringEventId,start,status,summary)',
			'timeMin' => date('c', strtotime('midnight')),
			'timeMax' => date('c', strtotime('midnight +3 weeks')),
		));

		if (empty($events) || empty($events['items'])) {
			return array();
		}

		$cachedEvents = array();
		foreach ($events['items'] as $event) {
			$timestampStart = strtotime($event['start']['dateTime']);
			$timestampEnd = strtotime($event['end']['dateTime']);
			$date = date('Y-m-d', $timestampStart);
			$time = date('H:i', $timestampStart) . '-' . date('H:i', $timestampEnd);

			$cachedEvents[$date][$time][] = $event['id'];
		}

		return $cachedEvents;
	}

	/**
	 * Create or return the calendar service.
	 *
	 * @return apiCalendarService
	 */
	protected function _getCalendarService()
	{
		if ($this->_calendarService) {
			return $this->_calendarService;
		}

		$apiClient = $this->_getApiClient();

		require_once 'Google/contrib/apiCalendarService.php';
		$calendarService = new apiCalendarService($apiClient);

		$this->_calendarService = $calendarService;
		return $calendarService;
	}

	/**
	 * Retrieve API access imprint details.
	 *
	 * @return Zend_Config_Json
	 */
	protected function _getImprint()
	{
		if (file_exists(APPLICATION_PATH . '/../data/calendarImprint.json')) {
			$imprint = new Zend_Config_Json(
				APPLICATION_PATH . '/../data/calendarImprint.json',
				'permissions',
				array('allowModifications' => true)
			);
			return $imprint;
		} else {
			return array();
		}
	}

	/**
	 * Update the imprint details if they've changed.
	 *
	 * This becomes necessary from time to time when the previous token expires.
	 *
	 * @param apiClient $apiClient
	 * @param string $apiToken
	 */
	protected function _updateImprintApiToken($apiClient, $apiToken)
	{
		$imprint = $this->_getImprint();
		if ($imprint->apiToken !== $apiToken) {
			$imprint->apiToken = $apiToken;

			$writer = new Zend_Config_Writer_Json();
			$writer->write(APPLICATION_PATH . '/../data/calendarImprint.json', $imprint);

			$apiClient->setAccessToken($apiToken);
		}
	}

	/**
	 * Creates the Google API client based off configuration and library objects.
	 *
	 * @return apiClient
	 */
	protected function _getApiClient()
	{
		if ($this->_apiClient) {
			return $this->_apiClient;
		}

		$clientId = Zend_Registry::get('AppConfig')->app->calendar->clientId;
		$clientSecret = Zend_Registry::get('AppConfig')->app->calendar->clientSecret;
		$developerKey = Zend_Registry::get('AppConfig')->app->calendar->developerKey;

		$serverUrl = new Zend_View_Helper_ServerUrl();
		$baseUrl = new Zend_View_Helper_BaseUrl();
		$redirectUri = $serverUrl->serverUrl() . $baseUrl->baseUrl('/oauth2callback');

		require_once 'Google/apiClient.php';
		$apiClient = new apiClient();
		$apiClient->setClientId($clientId);
		$apiClient->setClientSecret($clientSecret);
		$apiClient->setDeveloperKey($developerKey);
		$apiClient->setRedirectUri($redirectUri);
		$apiClient->setScopes(array(
			'https://www.google.com/calendar/feeds/',
		));

		$imprint = $this->_getImprint();
		if (!empty($imprint->apiToken)) {
			$apiClient->setAccessToken($imprint->apiToken);

			$this->_updateImprintApiToken($apiClient, $apiClient->getAccessToken());
		}

		$this->_apiClient = $apiClient;
		return $this->_apiClient;
	}
}