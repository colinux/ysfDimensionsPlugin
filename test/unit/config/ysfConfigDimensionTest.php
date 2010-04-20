<?php

/**
 *
 * Copyright (c) 2007 Yahoo! Inc.  All rights reserved.
 * The copyrights embodied in the content in this file are licensed
 * under the MIT open source license.
 *
 * For the full copyright and license information, please view the LICENSE.yahoo
 * file that was distributed with this source code.
 */

$sf_symfony_lib_dir = '/Users/dustin/projects/symfony/branch/1.1/lib';

$sf_root_dir = realpath(dirname(__FILE__).'/../../fixtures/project');

require_once($sf_root_dir.'/config/ProjectConfiguration.class.php');
$configuration = new ProjectConfiguration($sf_root_dir);

// load lime
require_once($configuration->getSymfonyLibDir().'/vendor/lime/lime.php');

$t = new lime_test(28, new lime_output_color());

$d = new ysfConfigDimension($configuration->getEventDispatcher(), new sfNoCache(), array('set_default' => true));
foreach (array('initialize', 'loadConfiguration', 'clean', 'check', 'set', 'get', 'getCascade', 'getName', 'getAllowed', 'getDefault', 'getCache') as $method)
{
  $t->can_ok($d, $method, sprintf('"%s" is a method of ysfConfigDimension', $method));
}

$t->diag('::initialize()');
$t->isa_ok($d->getCache(), true, 'dimension cache is a valid cache adapter');

$t->diag('::loadConfiguration()');
$t->is($d->getOption('set_default'), true, 'dimension configuration is loaded after initialization');

$t->is($d->get(), array('culture' => 'en', 'host' => 'sp1'), 'dimension is valid after initialization');
$t->is($d->getDefault(), array('culture' => 'en',  'host' => 'sp1'), 'default is valid after initialization');
$t->is($d->getAllowed(), array('culture' => array('en', 'fr', 'it', 'de'), 'skin' => array('corp', 'mybrand'), 'host' => array('sp1', 're4')), 'allowed is valid after initialization');
$t->is($d->getCascade(), array('en_sp1', 'en', 'sp1'), 'cascade is valid after initialization');

$t->diag('::clean()');
$t->is($d->clean(array('culture' => 'en', 'skin' => null, 'host' => null)), array('culture' => 'en'), 'removes keys with null values');
$t->is($d->clean(array('culture' => 'en', 'skin' => 'corp', 'host' => 'sp1')), array('culture' => 'en', 'skin' => 'corp', 'host' => 'sp1'), 'strtolower all values');

$t->diag('::check()');
try
{
  $d->check(array('culture' => 'ru', 'skin' => 'v2', 'host' => 'development'));
  $t->fail('does not throw exception on setting of invalid dimension');
}
catch (Exception $e)
{
  $t->pass('check throws exception when given an invalid dimension');
}

$t->diag('::set() complex dimension');
try
{
  ob_start();
  $d->set(array('culture' => 'ru', 'skin' => 'v2', 'host' => 'development'));
  ob_end_clean();

  $t->fail('does not throw exception on setting of invalid dimension');
}
catch (Exception $e)
{
  $t->pass('can not set an invalid dimension');
}

$d->set(array('culture' => 'en', 'skin' => 'corp', 'host' => 'sp1'));
$t->is($d->get(), array('culture' => 'en', 'skin' => 'corp', 'host' => 'sp1'), 'can set a valid dimension');

$t->diag('::get()');
$t->is($d->get(), array('culture' => 'en',  'skin' => 'corp',  'host' => 'sp1'), 'dimension is valid');
$t->is($d->getCascade(), array('en_corp_sp1', 'en_corp','en', 'sp1', 'corp'), 'cascade is valid with most specific dimensions first');
$t->is($d->__toString(), 'en_corp_sp1', 'dimension string is valid');

$t->diag('::set() simple dimension');
$d->set(array('culture' => 'en'));
$t->is($d->get(), array('culture' => 'en'), 'simple dimension is valid');
$t->is($d->getCascade(), array('en'), 'simple cascade is valid');
$t->is($d->__toString(), 'en', 'simple dimension string is valid');
