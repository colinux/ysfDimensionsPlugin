<?php

require_once(dirname(__FILE__).'/../lib/dimensionsConfiguration.class.php');

$configuration = new dimensionsConfiguration('dev', true);
sfContext::createInstance($configuration)->dispatch();
