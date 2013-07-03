<?php

class ErrorController extends Base_Controller_Action
{
	public function errorAction()
	{
		$errors = $this->_getParam('error_handler');

		switch ($errors->type) {
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				// 404 error -- controller or action not found
				$this->getResponse()->setHttpResponseCode(404);
				$this->view->message = 'Page not found';
				break;

			default:
				// application error
				$this->getResponse()->setHttpResponseCode(500);
				$this->view->message = 'Application error';
				break;
		}

		// Log exception, if logger available
		$this->log($this->view->message, LOG_CRIT);
		$this->log($errors->exception, LOG_CRIT);
		$this->_logException($errors->exception);

		// conditionally display exceptions
		if ($this->getInvokeArg('displayExceptions') == true) {
			$this->view->exception = $errors->exception;
		}

		$this->view->request = $errors->request;
	}

	public function notFoundAction()
	{
		$this->getResponse()->setHttpResponseCode(404);
	}

	public function forbiddenAction()
	{
		$this->getResponse()->setHttpResponseCode(403);
	}

	protected function _logException(Exception $exception)
	{
		if (!Zend_Db_Table::getDefaultAdapter()) {
			return;
		}

		$dbExceptionLog = new Base_Model_DbTable_ExceptionLog();
		return $dbExceptionLog->saveException($exception);
	}
}
