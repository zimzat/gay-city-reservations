<?php

interface Base_Job_ProcessCheckInterface
{
	public function isRunning();
	public function startOrAbort();
	public function start();
	public function stop();
}
