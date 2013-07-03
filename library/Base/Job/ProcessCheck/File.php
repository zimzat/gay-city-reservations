<?php

class Base_Job_ProcessCheck_File implements Base_Job_ProcessCheckInterface
{
	protected $_name;
	protected $_silent;

	public function  __construct($name, $silent=false)
	{
		$this->_name = $name;
		$this->_silent = $silent;
	}

	/**
	 * Checks if the process is already running.
	 *
	 * @return boolean
	 */
	public function isRunning()
	{
		$pidFile = $this->_getPidFile();
		if (file_exists($pidFile)) {
			$pid = trim(@file_get_contents($pidFile));
			if (empty($pid)) {
				return false;
			}

			$pidArgv = explode(' ', trim(`ps -o command= -p $pid`));
			if (strpos(basename($pidArgv[0]), 'php') === false) {
				return false;
			}

			foreach ($pidArgv as $arg) {
				if (basename($arg, '.php') === $this->_name) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Unconditionally starts the process.
	 */
	public function start()
	{
		if (function_exists('posix_getpid')) {
			$pid = posix_getpid();
		} elseif (function_exists('getmypid')) {
			$pid = getmypid();
		} else {
			throw new Exception('Unable to guarantee only one instance of this job will run; not running.');
		}

		$pidFile = $this->_getPidFile();
		file_put_contents($pidFile, $pid);
		@chmod($pidFile, 0666);
	}

	/**
	 * Starts the process if not already running, otherwise terminates with error.
	 */
	public function startOrAbort()
	{
		if ($this->isRunning()) {
			if (!$this->_silent) {
				echo 'Process is already running.', "\n";
			}
			exit(1);
		}

		$this->start();
	}

	/**
	 * Stops the process.
	 */
	public function stop()
	{
		$pidFile = $this->_getPidFile();
		if (file_exists($pidFile)) {
			@unlink($pidFile);
		}
	}

	/**
	 * Gets the file used to store this process's running ID.
	 *
	 * @return string
	 */
	protected function _getPidFile()
	{
		return sys_get_temp_dir() . '/phpJob_' . $this->_name . '_' . md5(__FILE__) . '.pid';
	}
}
