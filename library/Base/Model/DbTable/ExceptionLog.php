<?php

class Base_Model_DbTable_ExceptionLog extends Zend_Db_Table
{
	protected $_name = 'ExceptionLog';

	public function saveException(Exception $exception)
	{
		if (!in_array($this->_name, $this->getAdapter()->listTables())) {
			return false;
		}

		return $this->createRow(array(
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
			'message' => $exception->getMessage(),
			'trace' => $exception->getTraceAsString(),
		))->save();
	}
}
