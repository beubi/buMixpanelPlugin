<?php

/**
 * Event listener for buMixpanelPlugin.
 *
 *   Provides a getter and setter for "method_not_found" events
 *    Will dynamically create a getMixpanelTracker() and setMixpanelTracker() on the following objects:
 * 			- Request
 * 			- Response
 * 			- Component
 * 			- User
 *
 * @package    buMixpanelListener
 * @subpackage listener
 * @author     Luís Faceira <luis.faceira@beubi.com>
 * @version    SVN: $Id: $
 */
class buMixpanelListener
{
  /**
   * Event that fets or sets the current mixpanel tracker
   *
   * @param sfEvent $event The event to be processed
   *
   * @return  bool
   */
  public static function observe(sfEvent $event)
  {
    $subject = $event->getSubject();

    switch ($event['method'])
    {
      case 'getMixpanelTracker':
      	$event->setReturnValue(buMixpanelTracker::getInstance($subject));
      	return true;
   	}
  }

}
