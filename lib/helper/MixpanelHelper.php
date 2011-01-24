<?php

function analytics_by_mixpanel()
{
	return '<a href="http://mixpanel.com/?from=partner"><img src="http://mixpanel.com/site_media/images/mixpanel_partner_logo.gif" alt="Analytics by Mixpanel" /></a>';
}

function track_mixpanel_event($event,$properties)
{
  $html[] = '<script type="text/javascript">';
  $html[] = '//<![CDATA[';

  if(count($properties) > 0)
  {
    $html[] = '  mpmetrics.track("'.$name.'",'.json_encode($properties).');';
  }
  else
  {
    $html[] = '  mpmetrics.track("'.$name.'");';
  }
  $html[] = '//]]>';
  $html[] = '</script>';

  $html = join("\n", $html);
  return $html;
}