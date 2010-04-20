<?php

require_once(dirname(__FILE__).'/../../bootstrap/functional.php');

// create a new test browser
$browser = new sfTestBrowser();
$browser->
  get('/test/index')->
  isStatusCode(200)->
  isRequestParameter('module', 'test')->
  isRequestParameter('action', 'index')->
  checkResponseElement('body', '!/This is a temporary page/');
