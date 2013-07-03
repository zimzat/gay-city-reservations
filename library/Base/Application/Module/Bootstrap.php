<?php

class Base_Application_Module_Bootstrap extends Zend_Application_Module_Bootstrap
{
	protected function _initRouter()
	{
		$router = $this->getApplication()->bootstrap('FrontController')->getResource('FrontController')->getRouter();

		$routeFile = APPLICATION_PATH . '/modules/' . strtolower($this->getModuleName()) . '/configs/routes';
		if (file_exists($routeFile . '.ini') && is_readable($routeFile . '.ini')) {
			$router->addConfig(
				new Zend_Config_Ini($routeFile . '.ini', 'routes'),
				'routes'
			);
		}
		if (file_exists($routeFile . '.xml') && is_readable($routeFile . '.xml')) {
			$router->addConfig(
				new Zend_Config_Xml($routeFile . '.xml', 'routes'),
				'routes'
			);
		}

		return $router;
	}

	protected function _initNavigation()
	{
		/* @var $navigation Zend_Navigation */
		$navigation = $this->getApplication()->bootstrap('View')->getResource('View')->getHelper('navigation');

		$navigationFile = APPLICATION_PATH . '/modules/' . strtolower($this->getModuleName()) . '/configs/navigation';
		if (file_exists($navigationFile . '.ini') && is_readable($navigationFile . '.ini')) {
			$navigation->addPages(
				new Zend_Config_Ini($navigationFile . '.ini', 'navigation')
			);
		}
		if (file_exists($navigationFile . '.xml') && is_readable($navigationFile . '.xml')) {
			$navigation->addPages(
				new Zend_Config_Xml($navigationFile . '.xml', 'navigation')
			);
		}

		return $navigation;
	}
}
