<?php

class IndexController extends Base_Controller_Action
{
	public function indexAction()
	{
		$this->_forward('index', 'reservation');
	}

	public function authAction()
	{
		if ($this->_request->isPost()) {
			$adminPassword = Zend_Registry::get('AppConfig')->app->admin->password;
			if ($this->_request->getPost('password') === $adminPassword) {
				$this->_session->adminAuth = true;

				if ($this->_session->redirect) {
					$redirect = $this->_session->redirect;
					unset($this->_session->redirect);

					$this->_redirect->gotoUrl($redirect);
				} else {
					$this->_redirect->gotoSimpleAndExit('index');
				}
			}

			$this->_flashMessenger->addMessage('Invalid password');
		}
	}

	public function imprintAction()
	{
		if (empty($this->_session->adminAuth)) {
			$this->_session->redirect = $this->_request->getPathInfo();
			$this->_redirect->gotoRouteAndExit(array(), 'auth');
		}

		if (file_exists(APPLICATION_PATH . '/../data/calendarImprint.json')) {
			$this->_flashMessenger->addMessage('The calendar information has already been imprinted.');
			$this->_redirect->gotoSimpleAndExit('index', 'index');
		}

		$response = $this->_modelFactory->getCalendar()->imprintPermissions();

		if ($response === true) {
			$this->_flashMessenger->addMessage('Calendar settings have been successfully imprinted.');
			$this->_redirect->gotoUrlAndExit('/');
		}

		$this->view->calendarList = $response;
	}

	public function phoneReservationsAction()
	{
		if (empty($this->_session->adminAuth)) {
			$this->_session->redirect = $this->_request->getPathInfo();
			$this->_redirect->gotoRouteAndExit(array(), 'auth');
		}

		$modelCalendar = $this->_modelFactory->getCalendar();
		$notificationEvents = $modelCalendar->getEventsForEmailNotification();

		$details = array();
		if (!empty($notificationEvents['phone'])) {
			foreach ($notificationEvents['phone'] as $event) {
				$details[] = $modelCalendar->generateDetailsFromEvent($event);
			}
		}

		$this->view->eventDetails = $details;
	}

	public function loginAction()
	{
		Zend_Session::rememberMe();
		$this->_auth->getIdentity()
			->setRole(Base_Acl::ROLE_GUEST)
			->setInfo(array());

		$this->_redirect->gotoSimpleAndExit('index');
	}

	public function logoutAction()
	{
		Zend_Session::regenerateId();
		$this->_auth->getIdentity()
			->setRole(Base_Acl::ROLE_GUEST)
			->setInfo(array());

		$this->_redirect->gotoSimpleAndExit('index');
	}
}
