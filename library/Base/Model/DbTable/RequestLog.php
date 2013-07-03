<?php

class Base_Model_DbTable_RequestLog extends Zend_Db_Table
{
	protected $_name = 'RequestLog';

	public function saveRequestLog(Array $requestLog)
	{
		if (!in_array($this->_name, $this->getAdapter()->listTables())) {
			return;
		}

		return $this->createRow($requestLog)->save();
	}
}
