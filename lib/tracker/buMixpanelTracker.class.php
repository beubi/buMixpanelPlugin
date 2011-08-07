<?php

/**
 * Mixpanel Tracker. Is the main handler of all events, properties, etc.
 *
 * @package    buMixpanelPlugin
 * @subpackage tracker
 * @author     Ubiprism Lda. / be.ubi <contact@beubi.com>
 * @version    SVN: $Id$
 */
class buMixpanelTracker
{

  protected static $instance = null;
  protected $configuration = array();
  protected $user;

  /**
   * Constructs a mixpanel tracker instance, and saves teh session user object for future manipulation
   *
   * @param sfUser $user The session user to be handled
   */
  public function __construct($user)
  {
  	$this->user = $user;
  }

	/**
	 * Gets the singleton instance of the tracker.
	 *
	 * If no instance exists, one is created and then returned.
	 *
	 * @static
	 * @return buMixpanelTracker $mixpanelTracker The current tracker instance
	 */
  public static function getInstance()
  {
    if(is_null(self::$instance))
    {
      self::$instance = new buMixpanelTracker(sfContext::getInstance()->getUser());
    }
    return self::$instance;
  }

	/**
	 * Merges recursively a set of loaded configurations into the tracker
	 *
	 * @param array $newConfigurations The configurations to be merged
	 */
  public function configure($newConfigurations)
  {
    $this->configuration = array_merge_recursive($this->configuration, $newConfigurations);
  }

	/**
	 * Checks in the configuration if mixpanel is activated
	 *
	 * @return boolean $isEnabled True if it is, false if it isn't
	 */
  public function isEnabled()
  {
 		return $this->configuration['enabled'];
  }

	/**
	 * Checks in the auto-tracking mode is activated
	 *
	 * @return boolean $autoTrackingEnabled True if it is, false if it isn't
	 */
  public function isAutoTrackingEnabled()
  {
 		return $this->configuration['auto_tracking_enabled'];
  }

  /**
   * Checks in the configuration if we should be using the remote js library, from the mixpanel servers
   *
   * @return boolean $useRemoteJs True if the mixpanel library shall be used, false if a local one should be used
   */
  public function useRemoteJs()
  {
  	return $this->configuration['use_remote_js'];
  }

  /**
   * Gets the current bucket to be used, for mixpanel platform
   *
   * @return string $bucket The name of the bucket or false if none.
   */
  public function getBucket()
  {
  	return $this->configuration['bucket'];
  }

  /**
   * Gets if the testing mode is enabled
   *
   * @return boolean $testingMode True if the test mode shall be used, false otherwise
   */
  public function isTestingModeEnabled()
  {
 		return $this->configuration['testing_mode'];
  }

  /**
   * Gets the list of events to be processed and cleans them
   *
   * @return array $events The array of events to be processed
   */
  public function getEvents()
  {
    $events = $this->user->getAttribute('events', array(), 'bu_mixpanel_plugin');
    $this->user->setAttribute('events', array(), 'bu_mixpanel_plugin');
    return $events;
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
  	if($this->getBucket())
  	{
  		 $properties = array_merge($properties, array('bucket' => $this->getBucket()));
  	}
    $events = $this->user->getAttribute('events', array(), 'bu_mixpanel_plugin');
    $events = array_merge($events, array($eventName => $properties));
    $this->user->setAttribute('events', $events, 'bu_mixpanel_plugin');
  }

  /**
   * Gets the name_tag of the current user. This can be set by the app to identify the user in the analytics
   *
   * @return String $nameTag The string that represents the current user
   */
  public function getNameTag()
  {
    return $this->user->getAttribute('name_tag', array(), 'bu_mixpanel_plugin');
  }

  /**
   * Sets the name_tag of the current user. This allows the user to be easily identifiable in the mixpanel stream analytics
   *
   * @param string $nameTag A string that identifies the user (usually the user's name, login and/or e-mail address)
   *
   * @return nothing
   */
  public function setNameTag($nameTag)
  {
    $this->user->setAttribute('name_tag', $nameTag, 'bu_mixpanel_plugin');
  }


  public function getSuperProperties()
  {
    return $this->user->getAttribute('super_properties', array(), 'bu_mixpanel_plugin');
  }

  /**
   * Clears the superproperties currently stored in session (used usually upon a logoff)
   *
   * @return nothing
   */
  public function clearSuperProperties()
  {
    $this->user->setAttribute('super_properties', array(), 'bu_mixpanel_plugin');
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
    $superProperties = $this->user->getAttribute('super_properties', array(), 'bu_mixpanel_plugin');
    $superProperties = array_merge($superProperties, array($name => $value));
    $this->user->setAttribute('super_properties', $superProperties, 'bu_mixpanel_plugin');
    $this->user->setAttribute('super_properties_changed', true, 'bu_mixpanel_plugin');
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

  /**
   * Gets the currently configured token
   *
   * @return string $token current token
   */
  public function getToken()
  {
  	return $this->configuration['token'];
  }

	/**
	 * Inserts the code into the body
	 *
	 * @param sfWebResponse $response The response where the mixpanel code shall be inserted
	 */
  public function insert(sfWebResponse $response)
  {
  	// Creates a container for the generated html
    $html = array();

    // If we need to use the remote js, we should insert this first
    if ($this->useRemoteJs())
    {
    	$html[] = '<script type="text/javascript">';
    	$html[] = '//<![CDATA[';
    	$html[] = 'var mp_protocol = (("https:" == document.location.protocol) ? "https://" : "http://");';
    	$html[] = 'document.write(unescape("%3Cscript src=\'" + mp_protocol + "api.mixpanel.com/site_media/js/api/mixpanel.js\' type=\'text/javascript\'%3E%3C/script%3E"));';
    	$html[] = '//]]>';
    	$html[] = '</script>';
    }

    // Inserts the init script, with the optional testing mode option
    $html[] = '<script type="text/javascript">';
    $html[] = '//<![CDATA[';
    $html[] = 'try {';
    $html[] = '    var mpmetrics = new MixpanelLib("'.$this->getToken().'");';
		if($this->isTestingModeEnabled())
		{
    	$html[] = '    mpmetrics.set_config({"test": 1});';
		}
    $html[] = '} catch(err) {';
    $html[] = '    var null_fn = function () {};';
    $html[] = '    var mpmetrics = {';
    $html[] = '        track: null_fn,';
    $html[] = '        track_funnel: null_fn,';
    $html[] = '        register: null_fn,';
    $html[] = '        register_once: null_fn,';
    $html[] = '        register_funnel: null_fn,';
    $html[] = '        identify: null_fn';
    $html[] = '    };';
    $html[] = '};';
    $html[] = '//]]>';
    $html[] = '</script>';

    // Inserts a script with the current superproperties
    $superProperties = $this->getSuperProperties();
		$superProperties = array_merge($this->configuration['super_properties'],$superProperties);

    if(count($superProperties) > 0)
    {
      $html[] = '<script type="text/javascript">';
      $html[] = '//<![CDATA[';
      $html[] = '  mpmetrics.register_once('.json_encode($superProperties).');';
      $html[] = '//]]>';
      $html[] = '</script>';
    }

    // Inserts a script with the events (and automatically cleans them
    $events = $this->getEvents();

    if(count($events) > 0)
    {
      $html[] = '<script type="text/javascript">';
      $html[] = '//<![CDATA[';

      foreach($events as $name => $properties)
      {
      	if(count($properties) > 0)
      	{
      		$html[] = '  mpmetrics.track("'.$name.'",'.json_encode($properties).');';
      	}
        else
        {
        	$html[] = '  mpmetrics.track("'.$name.'");';
        }
      }

      // Insert the user's recognizable name, if provided
      if(!is_null($this->getNameTag()))
      {
        $html[] = '  mpmetrics.name_tag("'.$this->getNameTag().'");';
      }

      $html[] = '//]]>';
      $html[] = '</script>';
    }

    // Inserts the code just before </body>
    $html = join("\n", $html);
    $old = $response->getContent();
    $new = str_ireplace('</body>', "\n".$html."\n</body>", $old);

    if ($old == $new)
    {
      $new .= $html;
    }

    $response->setContent($new);
  }
}