<?php

class Base_Application_Bootstrap_Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	/**
	 * @var Zend_Config
	 */
	protected $_config;

	/**
	 * Stores a Zend_Config version of the application config for later recall.
	 *
	 * This reduces duplication, though increases chance of misuse.
	 *
	 * @param Zend_Config $config
	 */
	public function setApplicationConfig(Zend_Config $config)
	{
		$this->_config = $config;
	}

	/**
	 * Prioritize the bootstrap of logging functionality in case anything else goes wrong.
	 */
	protected function _initPriorityLog()
	{
		$this->bootstrap('log');
	}

	/**
	 * Initialize the default application model Acl object and set it to the registry under 'acl';
	 *
	 * @return \Application_Model_Acl
	 */
	protected function _initAcl()
	{
		$acl = new Application_Model_Acl();
		Zend_Registry::set('acl', $acl);
		return $acl;
	}

	/**
	 * Initialize and register the application configuration file for later access.
	 *
	 * @return \Zend_Config_Ini I
	 */
	protected function _initAppConfig()
	{
		Zend_Registry::set('AppConfig', $this->_config);
		return $this->_config;
	}

	/**
	 * Initialize the auth component, optionally to a blank auth identity for a guest user.
	 *
	 * @return type
	 */
	protected function _initAuth()
	{
		$auth = Zend_Auth::getInstance();

		if (!$auth->hasIdentity()) {
			$auth->getStorage()->write(new Base_Auth_Identity(
				Application_Model_Acl::ROLE_GUEST
			));
		}

		return $auth;
	}

	/**
	 * Include any global route config files into the router.
	 *
	 * @return type
	 * @throws Exception
	 */
	protected function _initRouter()
	{
		$router = $this->bootstrap('FrontController')->getResource('FrontController')->getRouter();

		$routeFile = APPLICATION_PATH . '/configs/routes.ini';
		if (file_exists($routeFile)) {
			if (!is_readable($routeFile)) {
				throw new Exception('Unable to read route file: ' . $routeFile);
			}
			$config = new Zend_Config_Ini($routeFile, 'routes');
			$router->addConfig($config, 'routes');
		}

		return $router;
	}

	/**
	 * Setup the Acl and Role bindings, include any global navigation config files
	 *
	 * @return type
	 */
	protected function _initNavigation()
	{
		/* @var $navigation Zend_Navigation */
		$navigation = $this->bootstrap('View')->getResource('View')->getHelper('navigation');

		$navigation->setAcl($this->bootstrap('Acl')->getResource('Acl'));
		$navigation->setRole(Zend_Auth::getInstance()->getIdentity()->role);

		$navigationFile = APPLICATION_PATH . '/configs/navigation.ini';
		if (file_exists($navigationFile)) {
			$config = new Zend_Config_Ini($navigationFile, 'navigation');
			$navigation->addPages($config);
		}

		return $navigation;
	}

	/**
	 * Set up or warn for invalid cache directories as necessary.
	 *
	 * @throws Exception
	 */
	protected function _initCacheManagerDirs()
	{
		$config = $this->bootstrap('AppConfig')->getResource('AppConfig');

//		var_dump($config);

		if (!isset($config->resources->cachemanager)) {
			return;
		}

		foreach ($config->resources->cachemanager as $cacheConfig) {
			$backendOptions = $cacheConfig->backend;
			if (!isset($backendOptions->options->cache_dir)) {
				continue;
			}

			if (!file_exists($backendOptions->options->cache_dir)) {
				if (!@mkdir($backendOptions->options->cache_dir, 0777, true)) {
					throw new Exception('Unable to create cache directory');
				}
			}

			if (!is_writable($backendOptions->options->cache_dir)) {
				throw new Exception('Unable to write to cache directory');
			}
		}
	}

	/**
	 * Setup cache directories and required boolean values prior to loading default metadata cache
	 *
	 * @see http://framework.zend.com/manual/1.11/en/zend.db.table.html#zend.db.table.metadata.caching
	 * @throws Exception
	 */
    protected function _initDbTableCache()
    {
        $config = $this->bootstrap('AppConfig')->getResource('AppConfig');

        if ($config->cache->dbTable->enabled) {
            $frontendOptions = $config->cache->dbTable->frontendOptions->toArray();
            $backendOptions = $config->cache->dbTable->backendOptions->toArray();
            if (isset($backendOptions['cache_dir']) && !file_exists($backendOptions['cache_dir'])) {
                if (!@mkdir($backendOptions['cache_dir'], 0777, true)) {
					throw new Exception('DbTable cache directory or parent is not writable');
				}
            }

            if (isset($frontendOptions['automatic_serialization'])) {
                $frontendOptions['automatic_serialization'] = !empty($frontendOptions['automatic_serialization']);
            }

            $cache = Zend_Cache::factory(
                'Core',
                'File',
                $frontendOptions,
                $backendOptions
            );

            // Next, set the cache to be used with all table objects
            Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
        }
    }

	/**
	 * Setup plugin loader cache for performance optimization.
	 *
	 * @see http://framework.zend.com/manual/1.11/en/performance.classloading.html#performance.classloading.pluginloader
	 * @throws Exception
	 */
    protected function _initPluginLoaderCache()
    {
        $config = $this->bootstrap('AppConfig')->getResource('AppConfig');

        if ($config->cache->pluginLoader->enabled) {
            $includeClassFile = $config->cache->pluginLoader->includeClassFile;
            if (file_exists($includeClassFile)) {
                include_once $includeClassFile;
            } else {
				$includeClassDir = dirname($includeClassFile);
				if (!file_exists($includeClassDir)) {
					if (!@mkdir($includeClassDir, 0777, true)) {
						throw new Exception('Plugin cache directory or parent is not writable');
					}
				}
			}

            Zend_Loader_PluginLoader::setIncludeFileCache($includeClassFile);
        }
    }

	/**
	 *
	 */
	protected function _initViewHelpers()
	{
		$view = $this->bootstrap('View')->getResource('View');
		$view->addHelperPath(APPLICATION_PATH . '/../library/Base/View/Helper/', 'Base_View_Helper_');
	}
}
