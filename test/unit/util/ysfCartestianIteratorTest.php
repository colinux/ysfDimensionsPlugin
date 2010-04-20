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

$t = new lime_test(2, new lime_output_color());

require_once(dirname(__FILE__).'/../../../lib/util/ysfCartesianIterator.class.php');

$test = array();

// test data
$data1 = array('symfony', 'dimensions');
$data2 = array('works', 'properly');

$cartestianExpression = new ysfCartesianIterator();
$cartestianExpression->addArray($data1);
$cartestianExpression->addArray($data2);
foreach($cartestianExpression as $catesianProduct)
{
  array_push($test, $catesianProduct);
}

$t->is($cartestianExpression instanceof Iterator, 'true', 'is subclass of Iterator');

$t->diag('->addArray()');
$t->is($test, array(array('symfony', 'works'), array('dimensions', 'works'), array('symfony', 'properly'), array('dimensions', 'properly')), 'cartestian expression is expanded correctly');
