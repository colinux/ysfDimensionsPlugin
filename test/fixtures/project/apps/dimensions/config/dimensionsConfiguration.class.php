<?php

// manually require class since not part of symfony core
require_once(dirname(__FILE__).'/../../../../../../lib/config/ysfApplicationConfiguration.class.php');

class dimensionsConfiguration extends ysfApplicationConfiguration
{
  /**
   * Configure the symfony application
   */
  public function configure()
  {
    // setup dimensions before calling parent::configure();
    $this->setDimension(array('culture' => 'en', 'skin' => null, 'host' => 'sp1'));

    parent::configure();
  }
}
