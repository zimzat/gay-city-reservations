<?php

/**
 * Provides a uniform interface to determine active identity & related info, whether logged or not.
 */
class Base_Auth_Identity
{
	/**
	 * @var string
	 */
	protected $_role;

	/**
	 * @var array
	 */
	protected $_info;

	/**
	 * Set initial role and related info.
	 *
	 * @param string $role
	 * @param array $info 
	 */
	public function __construct($role, Array $info=array())
	{
		$this->_role = $role;
		$this->_info = $info;
	}

	/**
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function &__get($name)
	{
		switch ($name) {
			case 'role':
				return $this->_role;
			default:
				return $this->_info[$name];
		}
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function __isset($name)
	{
		return array_key_exists($name, $this->_info);
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->_info[$name] = $value;
	}

	/**
	 * Replaces the existing identity role.
	 *
	 * @param string $role
	 * @return Base_Auth_Identity
	 */
	public function setRole($role)
	{
		$this->_role = $role;

		return $this;
	}

	/**
	 * Replaces the existing identity information (not including role).
	 *
	 * @param array $info
	 * @return Base_Auth_Identity
	 */
	public function setInfo(Array $info)
	{
		$this->_info = $info;

		return $this;
	}

	/**
	 * Determines if the active role is a guest (non-registered).
	 *
	 * @return boolean
	 */
	public function isGuest()
	{
		return ($this->_role === Base_Acl::ROLE_GUEST);
	}
}
