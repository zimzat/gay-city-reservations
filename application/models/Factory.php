<?php

/**
 * Succient creation of various interfaces and resources.
 */
class Application_Model_Factory
{
	/**
	 * Our interface to the Calendar resource and API.
	 *
	 * @return Application_Model_Calendar
	 */
	public function getCalendar()
	{
		return new Application_Model_Calendar();
	}
}
