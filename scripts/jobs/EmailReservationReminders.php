<?php

require_once __DIR__ . '/../_init.php';
$application->bootstrap();

class EmailReservationReminders extends Base_Job
{
	protected $_modelCalendar;

	protected function _run()
	{
		$modelFactory = new Application_Model_Factory();
		$this->_modelCalendar = $modelFactory->getCalendar();
		$notificationEvents = $this->_modelCalendar->getEventsForEmailNotification();

		// Notify the end-user their appointment is near.
		if (!empty($notificationEvents['email'])) {
			$this->_notifyEmails($notificationEvents['email']);
		}

		// Notify our internal user to remind end-users of their appointments.
		if (!empty($notificationEvents['phone'])) {
			$this->_notifyPhone($notificationEvents['phone']);
		}
	}

	protected function _notifyEmails($events)
	{
		$view = new Zend_View(array(
			'basePath' => APPLICATION_PATH . '/modules/default/views/',
		));
		$view->reminder = true;

		foreach ($events as $event) {
			$view->details = $this->_modelCalendar->generateDetailsFromEvent($event);
			$body = $view->render(
				'reservation/confirm-appointment.phtml'
			);

			try {
				$mail = new Zend_Mail();
				$mail->addTo($view->details['email'], $view->details['name']);
				$mail->setSubject('Gay City Appointment Reminder');
				$mail->setBodyText(strip_tags($body));
				$mail->setBodyHtml($body);
				$mail->send();
			} catch (Exception $e) {
				$this->log($e);
			}
		}
	}

	protected function _notifyPhone($events)
	{
		$view = new Zend_View(array(
			'basePath' => APPLICATION_PATH . '/modules/default/views/',
		));

		$details = array();
		foreach ($events as $event) {
			$details[] = $this->_modelCalendar->generateDetailsFromEvent($event);
		}

		$view->eventDetails = $details;
		$body = $view->render('index/phone-reservations.phtml');

		try {
			$reminderEmail = Zend_Registry::get('AppConfig')->app->reservation->phoneNotification->email;
			if (empty($reminderEmail)) {
				return;
			}

			$mail = new Zend_Mail();
			$mail->addTo($reminderEmail);
			$mail->setSubject('Gay City Appointment Reminder - Phone');
			$mail->setBodyText(strip_tags($body));
			$mail->setBodyHtml($body);
			$mail->send();
		} catch (Exception $e) {
			$this->log($e);
		}
	}
}

$jobName = basename(__FILE__, '.php');
$job = new $jobName(
	new Base_Job_ProcessCheck_File(
		$jobName
	)
);
$job->run();
