<?php

class Base_Acl extends Zend_Acl
{
	const ROLE_GUEST = 'guest';

	public function __construct()
	{
		$this->addRole(self::ROLE_GUEST);

		$this
			->addResource('default')
			->addResource('default:index', 'default')
			->addResource('default:reservation', 'default')
			->addResource('default:error', 'default');

		$this->allow(self::ROLE_GUEST, 'default');
	}
}
