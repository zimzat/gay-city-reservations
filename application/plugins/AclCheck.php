<?php

class Application_Plugin_AclCheck extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		/* @var $acl Zend_Acl */
		$acl = Zend_Registry::get('acl');
		if (empty($acl)) {
			return;
		}

		$auth = Zend_Auth::getInstance();

		$role = $this->_getRole($auth);
		$resource = $this->_getResource($request);
		$privilege = $this->_getPrivilege($request);

		if (!$acl->has($resource)) {
			$request
				->setModuleName('default')
				->setControllerName('error')
				->setActionName('not-found');
		} elseif (!$acl->isAllowed($role, $resource, $privilege)) {
			if (!$auth->getIdentity()->isGuest()) {
				$request
					->setModuleName('default')
					->setControllerName('error')
					->setActionName('forbidden');
			} else {
				$session = new Zend_Session_Namespace();
				$session->redirect = $request->getPathInfo();

				$request
					->setModuleName('default')
					->setControllerName('index')
					->setActionName('login');
			}
		}
	}

	protected function _getRole(Zend_Auth $auth)
	{
		return $auth->getIdentity()->role;
	}

	protected function _getResource(Zend_Controller_Request_Abstract $request)
	{
		return $request->getModuleName() . ':' . $request->getControllerName();
	}

	protected function _getPrivilege(Zend_Controller_Request_Abstract $request)
	{
		return $request->getActionName();
	}
}
