<?php

  $listener = array('buMixpanelListener', 'observe');

  $this->dispatcher->connect('request.method_not_found', $listener);
  $this->dispatcher->connect('response.method_not_found', $listener);
  $this->dispatcher->connect('component.method_not_found', $listener);
  $this->dispatcher->connect('user.method_not_found', $listener);
