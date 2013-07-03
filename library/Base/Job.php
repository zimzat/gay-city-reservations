<?php

abstract class Base_Job
{
	protected $_processCheck;

	public function __construct(Base_Job_ProcessCheckInterface $processCheck)
	{
		$this->_processCheck = $processCheck;
	}

	public function run()
	{
		$this->_processCheck->startOrAbort();
		try {
			$this->_run();
		} catch (Exception $e) {
			throw $e;
		}
		$this->_processCheck->stop();
	}

	abstract protected function _run();
}
