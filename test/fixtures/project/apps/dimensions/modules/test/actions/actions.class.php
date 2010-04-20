<?php

require_once(realpath(dirname(__FILE__).'/../lib/BaseTestActions.class.php'));

/**
 * test actions.
 *
 * @package    sf_test_project
 * @subpackage test
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 7310 2008-02-04 00:05:46Z dwhittle $
 */
class testActions extends baseTestActions
{
  /**
   * Executes index action
   *
   */
  public function executeIndex()
  {
    return parent::executeIndex();
  }
}
