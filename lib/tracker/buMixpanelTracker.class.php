<?php

class buMixpanelTracker
{

  protected static $instance = null;
  protected $configuration = array();

  public static function getInstance()
  {
    if(is_null(self::$instance))
    {
      self::$instance = new buMixpanelTracker();
    }
    return self::$instance;
  }

  public function configure($newConfigurations)
  {
    $this->configuration = array_merge($this->configuration, $newConfigurations);
  }


  public function isEnabled()
  {
  	return true;
  }

  public function useRemoteJs()
  {
  	return false;
  }

  public function isTestingModeEnabled()
  {
  	return true;
  }

  public function getEvents()
  {
    $user = sfContext::getInstance()->getUser();
    $events = $user->getAttribute('events', array(), 'bu_mixpanel_plugin');
    $user->setAttribute('events', array(), 'bu_mixpanel_plugin');
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
    $user = sfContext::getInstance()->getUser();

    $events = $user->getAttribute('events', array(), 'bu_mixpanel_plugin');

    $events = array_merge($events, array($eventName => $properties));

    $user->setAttribute('events', $events, 'bu_mixpanel_plugin');
  }


  public function getSuperProperties()
  {
    $user = sfContext::getInstance()->getUser();
    return $user->getAttribute('super_properties', array(), 'bu_mixpanel_plugin');
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
    $superProperties = array_merge($superProperties, array($name => $value));
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

  public function getToken()
  {
    return 'ab07fc79825fd4fcc7deab28d0f8d52a';
  }


  public function insert(sfWebResponse $response)
  {
    $html = array();

    if ($this->useRemoteJs())
    {
    	$html[] = '<script type="text/javascript">';
    	$html[] = '//<![CDATA[';
    	$html[] = 'var mp_protocol = (("https:" == document.location.protocol) ? "https://" : "http://");';
    	$html[] = 'document.write(unescape("%3Cscript src=\'" + mp_protocol + "api.mixpanel.com/site_media/js/api/mixpanel.js\' type=\'text/javascript\'%3E%3C/script%3E"));';
    	$html[] = '//]]>';
    	$html[] = '</script>';
    }

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

    $superProperties = $this->getSuperProperties();

    if(count($superProperties) > 0)
    {
      $html[] = '<script type="text/javascript">';
      $html[] = '//<![CDATA[';
      $html[] = '  mpmetrics.register_once("'.json_encode($superProperties).'");';
      $html[] = '//]]>';
      $html[] = '</script>';
    }

    $events = $this->getEvents();

    if(count($events) > 0)
    {
      $html[] = '<script type="text/javascript">';
      $html[] = '//<![CDATA[';

      foreach($events as $name => $properties)
      {
        //$html[] = '  mpmetrics.track("'.$name.'",'.json_encode($properties).');';
        $html[] = '  mpmetrics.track("'.$name.'");';
      }
      $html[] = '//]]>';
      $html[] = '</script>';
    }

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