<?php

class Application_Plugin_RequestLog extends Zend_Controller_Plugin_Abstract
{
	protected $_requestLog = array();
	protected $_isSaved = false;

	public function routeStartup(Zend_Controller_Request_Abstract $request)
	{
		$this->_requestLog['requestStart'] = microtime(true);

		register_shutdown_function(array($this, 'dispatchLoopShutdown'));
	}

	public function routeShutdown(Zend_Controller_Request_Abstract $request)
	{
		$this->_requestLog['module'] = $request->getModuleName();
		$this->_requestLog['controller'] = $request->getControllerName();
		$this->_requestLog['action'] = $request->getActionName();

		// Save the request data as soon as possible so the dispatch loop doesn't modify it.
		// This is especially true, and dangerous, because of exception objects being added.
		if (Zend_Registry::get('AppConfig')->app->requestLog->includeParams) {
			$this->_requestLog['requestData'] = pack(
				'V',
				strlen($a = serialize($this->getRequest()->getParams()))
			) . gzcompress($a);
		}
	}

	public function dispatchLoopShutdown()
	{
		if ($this->_isSaved) {
			return;
		}
		$this->_isSaved = true;

		$this->_requestLog['server'] = php_uname('n');

		$this->_requestLog['memoryEnd'] = memory_get_usage(true);
		$this->_requestLog['memoryPeak'] = memory_get_peak_usage(true);

		if (stripos(PHP_OS, 'win') === false) {
			$usage = getrusage();
			$this->_requestLog['userTime'] = round($usage['ru_utime.tv_usec'] / 10000);
			$this->_requestLog['systemTime'] = round($usage['ru_stime.tv_usec'] / 10000);
		}

		$this->_requestLog['requestEnd'] = microtime(true);
		$this->_requestLog['requestTotal'] = $this->_requestLog['requestEnd'] - $this->_requestLog['requestStart'];

		$dbRequestLog = new Base_Model_DbTable_RequestLog();
		$dbRequestLog->saveRequestLog($this->_requestLog);
	}
}
