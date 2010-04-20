<?php

require_once($sf_symfony_lib_dir.'/autoload/sfCoreAutoload.class.php');
sfCoreAutoload::register();

// manually require class since not part of symfony core
require_once(dirname(__FILE__).'/../../../../lib/config/ysfProjectConfiguration.class.php');

class ProjectConfiguration extends ysfProjectConfiguration
{
  public function setup()
  {
  }
}
