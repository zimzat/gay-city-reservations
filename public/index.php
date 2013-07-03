<?php

try {
	require_once realpath(dirname(__FILE__) . '/../scripts/_init.php');
	$application->bootstrap()->run(); /* @var $application Zend_Application */
} catch (Exception $e) {
	try {
		var_dump($e);
		$dbExceptionLog = new Base_Model_DbTable_ExceptionLog();
		$dbExceptionLog->saveException($e);
	} catch (Exception $e2) { }

	if (isset($application) && $application->getBootstrap()->hasResource('log')) {
		$application->getBootstrap()->getResource('log')->log($e, LOG_EMERG);
	}

	// Your php.ini should be set appropriately to hide or show errors.
	throw $e;
}
