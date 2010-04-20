<?php

/**
 *
 * Copyright (c) 2008 Yahoo! Inc.  All rights reserved.
 * The copyrights embodied in the content in this file are licensed
 * under the MIT open source license.
 *
 * For the full copyright and license information, please view the LICENSE.yahoo
 * file that was distributed with this source code.
 */

$sf_symfony_lib_dir = '/Users/dustin/projects/symfony/branch/1.1/lib';

$sf_root_dir = realpath(dirname(__FILE__).'/../fixtures/project');

require_once($sf_root_dir.'/config/ProjectConfiguration.class.php');
$configuration = sfProjectConfiguration::getApplicationConfiguration('dimensions', 'test', true, $sf_root_dir);

sfContext::createInstance($configuration);

$sf_symfony_lib_dir = sfConfig::get('sf_symfony_lib_dir');

// remove all cache
sfToolkit::clearDirectory(sfConfig::get('sf_app_cache_dir'));

// load lime
require_once($configuration->getSymfonyLibDir().'/vendor/lime/lime.php');

$b = new sfTestBrowser();
$t = $b->test();

$c = $b->getContext()->getConfiguration();

$t->diag('checking default configuration dimensions');
$b->get('/test2')->isStatusCode(200)->checkResponseElement('title', '/symfony project/')->checkResponseElement('body', '/generic view/')->checkResponseElement('body', '/generic action/');
$t->is($c->getConfigPaths('config/app.yml'), array($sf_root_dir.'/apps/dimensions/config/app.yml', $sf_root_dir.'/apps/dimensions/config/sp1/app.yml'), '->getConfigPaths() returns correct module level configuration files given name');
$t->is($c->getTemplatePath('test', 'indexSuccess.php'), $sf_root_dir.'/apps/dimensions/modules/test/templates/indexSuccess.php', '->getTemplatePath() returns correct path to template given module name and template name');

$t->diag('checking backwards compatability by setting dimension null');
$c->setDimension(null);
$t->is($c->getTemplateDirs('test'), array($sf_root_dir.'/apps/dimensions/modules/test/templates', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/modules/test/templates', $sf_symfony_lib_dir.'/controller/test/templates',$sf_root_dir.'/cache/dimensions/test/modules/autoTest/templates'), '->getTemplateDirs() checking template path returns correct with out dimension');
$t->is($c->getConfigPaths('modules/test/config/view.yml'), array($sf_symfony_lib_dir.'/config/config/view.yml', $sf_root_dir.'/apps/dimensions/config/view.yml', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/modules/test/config/view.yml', $sf_root_dir.'/apps/dimensions/modules/test/config/view.yml'), '->getConfigPaths() checking template path returns correct with out dimension');
$b->get('/test2')->isStatusCode(200)->checkResponseElement('title', '/symfony project/')->checkResponseElement('body', '/generic view/')->checkResponseElement('body', '/generic action/');

// setup 1st test dimension
$c->setDimension(array('culture' => 'fr', 'skin' => 'corp', 'host' => 'sp1'));

$t->diag('checking all project level configuration methods return correct paths with dimensions');

// ysfProjectConfiguration

// model
$t->is($c->getModelDirs(), array($sf_root_dir.'/lib/model/fr', $sf_root_dir.'/lib/model', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/lib/model/fr', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/lib/model'), '->getModelDirs() return correct directory cascade with dimensions');

// generators
$t->is($c->getGeneratorSkeletonDirs('sfPropelAdmin', 'default'), array($sf_root_dir.'/data/generator/sfPropelAdmin/default/skeleton/fr_corp', $sf_root_dir.'/data/generator/sfPropelAdmin/default/skeleton/fr', $sf_root_dir.'/data/generator/sfPropelAdmin/default/skeleton', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/data/generator/sfPropelAdmin/default/skeleton/fr', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/data/generator/sfPropelAdmin/default/skeleton', $sf_symfony_lib_dir.'/plugins/sfPropelPlugin/data/generator/sfPropelAdmin/default/skeleton'), '->getGeneratorSkeletonDirs() return correct directory cascade with dimensions');
$t->is($c->getGeneratorTemplateDirs('sfPropelAdmin', 'default'), array($sf_root_dir.'/data/generator/sfPropelAdmin/default/template/fr_corp', $sf_root_dir.'/data/generator/sfPropelAdmin/default/template/fr', $sf_root_dir.'/data/generator/sfPropelAdmin/default/template', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/data/generator/sfPropelAdmin/default/template/fr', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/data/generator/sfPropelAdmin/default/template', $sf_symfony_lib_dir.'/plugins/sfPropelPlugin/data/generator/sfPropelAdmin/default/template'), '->getGeneratorTemplateDirs() return correct directory cascade with dimensions');
$t->is($c->getGeneratorTemplate('sfPropelAdmin', 'default', ''), $sf_root_dir.'/data/generator/sfPropelAdmin/default/template/fr_corp/', '->getGeneratorTemplate() called without a path validates a theme exists');
$t->is($c->getGeneratorTemplate('sfPropelAdmin', 'default', 'templates/_list.php'), $sf_root_dir.'/data/generator/sfPropelAdmin/default/template/fr_corp/templates/_list.php', '->getGeneratorTemplate() called with path returns correct path for dimension');

$t->diag('checking all application level configuration methods return correct paths with dimensions');

// ysfApplicationConfiguration

// controllers
$t->is($c->getControllerDirs('test'), array($sf_root_dir.'/apps/dimensions/modules/test/actions/fr' => false, $sf_root_dir.'/apps/dimensions/modules/test/actions' => false, $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/modules/test/actions' => true, $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/modules/test/actions/fr' => true), '->getControllerDirs() returns correct directory cascade with dimensions');

// templates
$t->is($c->getTemplateDirs('test'), array($sf_root_dir.'/apps/dimensions/modules/test/templates/fr_corp', $sf_root_dir.'/apps/dimensions/modules/test/templates/fr', $sf_root_dir.'/apps/dimensions/modules/test/templates', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/modules/test/templates/fr_corp', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/modules/test/templates/fr', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/modules/test/templates', $sf_root_dir.'/cache/fr_corp_sp1/dimensions/test/modules/autoTest/templates', $sf_symfony_lib_dir.'/controller/test/templates'), '->getTemplateDirs() returns correct directory cascade with dimensions');
$t->is($c->getTemplateDir('test', 'indexSuccess.php'), $sf_root_dir.'/apps/dimensions/modules/test/templates/fr_corp', '->getTemplateDir() returns correct directory to template given module name and template name');
$t->is($c->getTemplatePath('test', 'indexSuccess.php'), $sf_root_dir.'/apps/dimensions/modules/test/templates/fr_corp/indexSuccess.php', '->getTemplatePath() returns correct path to template given module name and template name');

// decorators
$t->is($c->getDecoratorDirs(), array($sf_root_dir.'/apps/dimensions/templates/corp', $sf_root_dir.'/apps/dimensions/templates'), '->getDecoratorDirs() returns correct paths to decorator templates');
$t->is($c->getDecoratorDir('layout.php'), $sf_root_dir.'/apps/dimensions/templates/corp', '->getDecoratorDir() returns correct path to decorator template given template name');

// i18n
$t->is($c->getI18NGlobalDirs(), array($sf_root_dir.'/apps/dimensions/i18n'), '->getI18NGlobalDirs() returns correct directories to i18n files');
$t->is($c->getI18NDirs('test'), array($sf_root_dir.'/apps/dimensions/i18n', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/modules/test/i18n'), '->getI18NDirs() returns correct directories to i18n files given module name');

// config paths
$t->is($c->getConfigPaths('config/databases.yml'), array($sf_root_dir.'/config/databases.yml', $sf_root_dir.'/config/corp/databases.yml'), '->getConfigPaths() returns correct project configuration files given name');
$t->is($c->getConfigPaths('config/settings.yml'), array($sf_symfony_lib_dir.'/config/config/settings.yml', $sf_root_dir.'/apps/dimensions/config/settings.yml'), '->getConfigPaths() returns correct application level configuration files given name');
$t->is($c->getConfigPaths('config/view.yml'), array($sf_symfony_lib_dir.'/config/config/view.yml', $sf_root_dir.'/apps/dimensions/config/view.yml', $sf_root_dir.'/apps/dimensions/config/fr/view.yml'), '->getConfigPaths() returns correct module level configuration files given name');
$t->is($c->getConfigPaths('modules/test/config/view.yml'), array($sf_symfony_lib_dir.'/config/config/view.yml', $sf_root_dir.'/apps/dimensions/config/view.yml', $sf_root_dir.'/apps/dimensions/config/fr/view.yml', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/modules/test/config/view.yml', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/modules/test/config/fr/view.yml', $sf_root_dir.'/plugins/ysfDimensionsTestPlugin/modules/test/config/fr_corp/view.yml', $sf_root_dir.'/apps/dimensions/modules/test/config/view.yml', $sf_root_dir.'/apps/dimensions/modules/test/config/corp/view.yml'), '->getConfigPaths() returns correct module level configuration files given name');

$t->diag('checking project content for dimensions configuration');
$b->get('/')->isStatusCode(200)->checkResponseElement('title', '/[fr]/')->checkResponseElement('body > h1', '/corp layout/')->checkResponseElement('body', '/fr localized corporate view/')->checkResponseElement('body', '/fr localized action/');

// setup 2nd test dimension
$c->setDimension(array('culture' => 'fr', 'skin' => null, 'host' => 'sp1'));

$t->diag('checking project content for dimensions configuration');
$b->get('/')->isStatusCode(200)->checkResponseElement('title', '/[fr]/')->checkResponseElement('body', '/fr localized view/')->checkResponseElement('body', '/fr localized action/');

$t->diag('checking project content for dimensions configuration when not localized');

$b->get('/test2')->isStatusCode(200)->checkResponseElement('title', '/symfony project/')->checkResponseElement('body', '/generic view/')->checkResponseElement('body', '/generic action/');