<?php

/**
 * Filter that adds the tracking code to the response and initializes the "tracker" object
 *
 * @package    buMixpanelPlugin
 * @subpackage filter
 * @author     Ubiprism Lda. / be.ubi <contact@beubi.com>
 * @version    SVN: $Id$
 */
class buMixpanelFilter extends sfFilter
{
  /**
   * Inserts the tracking code for the web responses
   *
   * @param   sfFilterChain $filterChain
   */
  public function execute($filterChain)
  {
    $prefix   = 'bu_mixpanel_plugin_';
    $user     = $this->context->getUser();
    $request  = $this->context->getRequest();
    $response = $this->context->getResponse();

    if ($this->isFirstCall())
    {
      $tracker = new buMixpanelTracker($this->context);

      //TODO: Check what these are and check if they apply to this plugin
      // pull callables from session storage
      $callables = $user->getAttribute('callables', array(), 'bu_mixpanel_plugin');
      foreach ($callables as $callable)
      {
        list($method, $arguments) = $callable;
        call_user_func_array(array($tracker, $method), $arguments);
      }
    }

    $filterChain->execute();
    $tracker = $request->getMixpanelTracker();

    // apply module- and action-level configuration
    $module = $this->context->getModuleName();
    $action = $this->context->getActionName();

    $moduleParams = sfConfig::get('mod_'.strtolower($module).'_bu_mixpanel_plugin_params', array());
    $tracker->configure($moduleParams);

    $actionConfig = sfConfig::get('mod_'.strtolower($module).'_'.$action.'_bu_mixpanel_plugin', array());
    if (isset($actionConfig['params']))
    {
      $tracker->configure($actionConfig['params']);
    }

    // insert tracking code
    if ($this->isTrackable() && $tracker->isEnabled())
    {
      if (sfConfig::get('sf_logging_enabled'))
      {
        buMixpanelToolkit::logMessage($this, 'Inserting tracking code.');
      }

      $tracker->insert($response);
    }
    else if (sfConfig::get('sf_logging_enabled'))
    {
      buMixpanelToolkit::logMessage($this, 'Tracking code not inserted.');
    }

    $user->getAttributeHolder()->removeNamespace('bu_mixpanel_plugin');
    $tracker->shutdown($user);
  }

  /**
   * Test whether the response is trackable.
   *
   * @return  bool
   */
  protected function isTrackable()
  {
    $request    = $this->context->getRequest();
    $response   = $this->context->getResponse();
    $controller = $this->context->getController();

    // don't add analytics:
    // * for XHR requests
    // * if not HTML
    // * if 304
    // * if not rendering to the client
    // * if HTTP headers only
    if ($request->isXmlHttpRequest() ||
        strpos($response->getContentType(), 'html') === false ||
        $response->getStatusCode() == 304 ||
        $controller->getRenderMode() != sfView::RENDER_CLIENT ||
        $response->isHeaderOnly())
    {
      return false;
    }
    else
    {
      return true;
    }
  }
}
