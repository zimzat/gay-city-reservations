<?php

// Define path to application directory
defined('APPLICATION_PATH')
	|| define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));
defined('APPLICATION_PUBLIC_PATH')
	|| define('APPLICATION_PUBLIC_PATH', realpath(dirname($_SERVER['SCRIPT_FILENAME'])));

// Define path to public directory
if (!defined('APPLICATION_PUBLIC_PATH')) {
	if (PHP_SAPI === 'cli') {
		define('APPLICATION_PUBLIC_PATH', realpath(APPLICATION_PATH . '/../public'));
	} else {
		define('APPLICATION_PUBLIC_PATH', realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
	}
}

// Define application environment
if (!defined('APPLICATION_ENV')) {
	if (file_exists(APPLICATION_PATH . '/configs/environment')) {
		define('APPLICATION_ENV', trim(file_get_contents(APPLICATION_PATH . '/configs/environment')));
	} elseif (getenv('APPLICATION_ENV')) {
		define('APPLICATION_ENV', getenv('APPLICATION_ENV'));
	} else {
		define('APPLICATION_ENV', 'local');
	}
}

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
	realpath(APPLICATION_PATH . '/../library'),
	get_include_path(),
)));

/** Zend_Loader_Autoloader */
require_once 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance();

$config = new Zend_Config_Ini(
	APPLICATION_PATH . '/configs/application.ini',
	APPLICATION_ENV,
	true
);
if (file_exists(APPLICATION_PATH . '/configs/installation.ini')) {
	$config->merge(
		new Zend_Config_Ini(
			APPLICATION_PATH . '/configs/installation.ini',
			APPLICATION_ENV
		)
	);
}
$config->setReadOnly();

// Create application, bootstrap, and run
$application = new Zend_Application(
	APPLICATION_ENV,
	$config
);
$application->getBootstrap()->setApplicationConfig($config);

// Reduce Pollution! Be Kind, Rewind!
unset($config);

function cleanupFilePermissions()
{
	if (stripos(PHP_OS, 'linux') === false && stripos(PHP_OS, 'freebsd') === false) {
		return;
	}

	$jobProcessCheck = new Base_Job_ProcessCheck_File('FilePermissionSync');
	if ($jobProcessCheck->isRunning()) {
		return;
	}
	$jobProcessCheck->start();

	$dirs = array(
		realpath(APPLICATION_PATH . '/../temp/'),
		realpath(APPLICATION_PATH . '/../data/'),
	);

	$chmodQuiet = PHP_OS === 'FreeBSD' ? '-f' : '--quiet';
	$findSplit = PHP_OS === 'FreeBSD' ? '-or' : ',';

	foreach ($dirs as $dir) {
		`find {$dir} \( -type d \! -perm 777 -exec chmod $chmodQuiet 777 '{}' \; \) $findSplit \( -type f \! -perm 666 -exec chmod $chmodQuiet 666 '{}' \; \)`;
	}

	$jobProcessCheck->stop();
}

// Clean up file permissions. Only necessary if non-webserver (cli, cron) scripts are being run
//register_shutdown_function('cleanupFilePermissions');
