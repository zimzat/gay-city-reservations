<?php

class ReservationController extends Base_Controller_Action
{
	public function init()
	{
		parent::init();

		date_default_timezone_set('America/Los_Angeles');
		ini_set('date.timezone', 'America/Los_Angeles');

		if (!file_exists(APPLICATION_PATH . '/../data/calendarImprint.json')) {
			$this->_redirect->gotoSimpleAndExit('imprint', 'index');
			return;
		}
	}

	public function indexAction()
	{
		if ($this->_request->isPost()) {
			if (count(array_filter($this->_getParam('disclaimer', array()))) == 4) {
				$this->_redirect->gotoSimpleAndExit('select-date');
			} else {
				$this->_flashMessenger->addMessage('Please confirm your agreement to all statements.');
			}
		}
	}

	public function testAction()
	{
		header('Content-type: text/plain');
		$dt = new DateTime();
		var_dump(
			date('c'),
			gmdate('c'),
			$dt->getTimestamp(),
			$dt->getTimezone(),
			$dt->getOffset(),
			$dt->format('c'),
			$this->_modelFactory->getCalendar()->getAvailableTimes('2013-05-08')
		);
		exit;
	}

	public function selectDateAction()
	{
		$dates = $this->_modelFactory->getCalendar()->getAvailableDates();

		$this->view->dates = $dates;
	}

	public function selectTimeAction()
	{
		$date = $this->_getParam('date');

		$times = $this->_modelFactory->getCalendar()->getAvailableTimes($date);
		if (empty($times)) {
			$this->_flashMessenger->addMessage('The date selected is no longer available.');
			$this->_redirect->gotoSimpleAndExit('select-date', 'reservation');
		}

		$this->view->date = $date;
		$this->view->times = $times;
	}

	public function selectDetailsAction()
	{
		$date = $this->_getParam('date');
		$time = $this->_getParam('time');

		$times = $this->_modelFactory->getCalendar()->getAvailableTimes($date);
		if (array_search($time, $times) === false) {
			$this->_flashMessenger->addMessage('The time selected is no longer available.');
			$this->_redirect->gotoSimpleAndExit('select-date', 'reservation');
		}

		$form = new Default_Form_ReservationDetails();
		$form->setDefaults(array(
			'date' => $date,
			'time' => $time,
		));

		$types = array();
		foreach ($this->_modelFactory->getCalendar()->getTypes() as $key => $value) {
			$types[$key] = '<b>' . $key . '</b> ' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
		}
		$form->getElement('type')->setMultiOptions($types);

		if ($this->_request->isPost()) {
			if ($form->isValid($this->_getAllParams())) {
				$isSuccess = $this->_modelFactory->getCalendar()->reserveAppointment(
					$form->getValue('type'),
					$date,
					$time,
					$form->getValues()
				);

				if ($isSuccess) {
					$confirmationId = uniqid();
					$this->_session->appointment[$confirmationId] = $form->getValues() + array(
						'date' => $date,
						'time' => $time,
					);

					if ($form->getValue('email') && $form->getValue('contactMethod') == 'email') {
						$this->view->details = $this->_session->appointment[$confirmationId];
						$body = $this->view->render('reservation/confirm-appointment.phtml');

						try {
							$mail = new Zend_Mail();
							$mail->addTo($form->getValue('email'), $form->getValue('name'));
							$mail->setSubject('Gay City Appointment Confirmation');
							$mail->setBodyText(strip_tags($body));
							$mail->setBodyHtml($body);
							$mail->send();
						} catch (Exception $e) {
							$this->log($e);
						}
					}

					$this->_redirect->gotoSimpleAndExit('confirm-appointment', null, null, array(
						'confirmationId' => $confirmationId,
					));
				} else {

				}
			}
		}

		$this->view->date = $date;
		$this->view->time = $time;
		$this->view->form = $form;
	}

	public function confirmAppointmentAction()
	{
		$confirmationId = $this->_getParam('confirmationId');
		if (empty($this->_session->appointment[$confirmationId])) {
			$this->_flashMessenger->addMessage('The confirmation details are unavailable or have expired.');
			$this->_redirect->gotoSimpleAndExit('index');
		}

		$this->view->details = $this->_session->appointment[$confirmationId];
	}
}
