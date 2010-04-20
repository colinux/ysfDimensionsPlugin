<?php

require_once(dirname(__FILE__).'/../lib/dimensionsConfiguration.class.php');

$configuration = new dimensionsConfiguration('prod', false);
sfContext::createInstance($configuration)->dispatch();
