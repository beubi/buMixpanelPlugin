<?php

class buMixpanelTracker
{

	public static function getInstance()
	{
		return sfContext::getInstance()->getRequest()->getAttribute('mixpanel_tracker', null, 'bu_mixpanel_plugin');
	}

	/**
	 * Adds an event to be registered in the next html rendering
	 *
	 * @param string $eventName  The name of the event
	 * @param array  $properties An associative array of properties (and their values)
	 *
	 * @return nothing
	 */
	public function addEvent($eventName, $properties = array())
	{
		$user = sfContext::getInstance()->getUser();

		$events = $user->getAttribute('events', array(), 'bu_mixpanel_plugin');

		$events = array_push($events, array($eventName => $properties));

		$user->setAttribute('events', $events, 'bu_mixpanel_plugin');
	}

	/**
	 * Clears the superproperties currently stored in session (used usually upon a logoff)
	 *
	 * @return nothing
	 */
	public function clearSuperProperties()
	{
		$user = sfContext::getInstance()->getUser();

		$user->setAttribute('super_properties', array(), 'bu_mixpanel_plugin');
		$user->setAttribute('clear_super_properties', true, 'bu_mixpanel_plugin');
	}

	/**
	 * Adds a certain super property to be used in all events
	 *
	 * @param string $name  The name of the property
	 * @param string $value The value of the superproperty
	 *
	 * @return nothing
	 */
	public function addSuperProperty($name, $value)
	{
		$user = sfContext::getInstance()->getUser();
		$superProperties = $user->getAttribute('super_properties', array(), 'bu_mixpanel_plugin');
		$superProperties = array_push($superProperties, array($name => $value));
		$user->setAttribute('super_properties', $superProperties, 'bu_mixpanel_plugin');
		$user->setAttribute('super_properties_changed', true, 'bu_mixpanel_plugin');
	}

	/**
	 * Adds an array of superproperties
	 *
	 * This is a convenience method for adding multiple super properties at once
	 *
	 * @param array $properties An associative array with pairs of name=>value
	 *
	 * @return nothing
	 */
	public function addSuperProperties($properties)
	{
		// Adds each supplied super property
		foreach ($properties as $name => $value)
		{
			$this->addSuperProperty($name, $value);
		}
	}

}