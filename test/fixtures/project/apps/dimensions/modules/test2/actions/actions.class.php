<?php

/**
 * test2 actions.
 *
 * @package    sf_test_project
 * @subpackage test
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 7310 2008-02-04 00:05:46Z dwhittle $
 */
class test2Actions extends sfActions
{
  /**
   * Executes index action
   *
   */
  public function executeIndex()
  {
    $this->text = 'generic action';
  }
}
