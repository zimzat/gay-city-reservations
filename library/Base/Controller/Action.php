<?php

/**
 * Base controller that instantiates basic objects needed for most controllers and actions.
 */
abstract class Base_Controller_Action extends Zend_Controller_Action
{
	/**
	 * @var Zend_Controller_Action_Helper_Redirector
	 */
	protected $_redirect;

	/**
	 * @var Zend_Session_Namespace
	 */
	protected $_session;

	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $_db;

	/**
	 * @var Zend_Controller_Request_Http
	 */
	protected $_request;

	/**
	 * @var Zend_Controller_Action_Helper_FlashMessenger
	 */
	protected $_flashMessenger;

	/**
	 * @var Zend_Auth
	 */
	protected $_auth;

	/**
	 * @var Zend_Log
	 */
	protected $_log;

	/**
	 * @var Application_Model_Factory 
	 */
	protected $_modelFactory;

	/**
	 * Instantiate all the above properties in a way that allows Netbeans to use the provided hints
	 * rather than what the object returns.
	 */
	public function init()
	{
		// This may look lame (it is), but it's to confuse Netbeans autocomplete.
		$this->{'_redirect'} = $this->_helper->getHelper('Redirector');
		$this->{'_session'} = new Zend_Session_Namespace();
		$this->{'_db'} = Zend_Db_Table_Abstract::getDefaultAdapter();
		$this->{'_request'} = $this->getRequest();
		$this->{'_flashMessenger'} = $this->_helper->getHelper('FlashMessenger');
		$this->{'_auth'} = Zend_Auth::getInstance();
		$this->_modelFactory = new Application_Model_Factory();

		$this->_redirect->setExit(true);

		if ($this->_flashMessenger->hasMessages()) {
			foreach ($this->_flashMessenger->getMessages() as $message) {
				$this->_flashMessenger->addMessage($message);
			}
			$this->_flashMessenger->clearMessages();
		}
	}

	/**
	 * @return Zend_Log
	 */
	public function getLog()
	{
		if (empty($this->_log)) {
			$this->{'_log'} = $this->getInvokeArg('bootstrap')->getResource('log');
		}

		return $this->_log;
	}

	public function log($message, $priority = LOG_DEBUG)
	{
		$this->getLog()->log($message, $priority);
	}

	public function logAndAbort($message, $priority = LOG_ALERT)
	{
		$this->log($message, $priority);
		$this->_redirect->gotoSimpleAndExit('index', 'index', 'default');
	}
}
