<?php

/**
 * Helper for making easy links and getting urls that depend on the routes and router
 *
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Base_View_Helper_UrlSimple extends Zend_View_Helper_Abstract
{
	/**
	 * Generates an url given the name of a route.
	 *
	 * @access public
	 *
	 * @param string $action
	 * @param string $controller
	 * @param string $module
	 * @param  array $urlOptions Options passed to the assemble method of the Route object.
	 * @param  mixed $name The name of a Route to use. If null it will use the current Route
	 * @param  bool $reset Whether or not to reset the route defaults with those provided
	 * @param  bool $encode
	 * @return string Url for the link href attribute.
	 */
	public function urlSimple($action = null, $controller = null, $module = null, array $urlOptions = array(), $name = null, $reset = false, $encode = true)
	{
		if ($action) $urlOptions['action'] = $action;
		if ($controller) $urlOptions['controller'] = $controller;
		if ($module) $urlOptions['module'] = $module;
		if (!$name) $name = 'default';

		$router = Zend_Controller_Front::getInstance()->getRouter();
		return $router->assemble($urlOptions, $name, $reset, $encode);
	}
}
