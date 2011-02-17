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

require_once(dirname(__FILE__).'/ysfConfigDimension.class.php');

/**
 * ysfProjectConfiguration represents a configuration for a symfony project.
 *
 * This class adds support for configuration dimensions and caches all results.
 *
 * @package    ysymfony
 * @subpackage config
 * @author     Dustin Whittle <dustin.whittle@symfony-project.com>
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfApplicationConfiguration.class.php 7618 2008-02-27 00:02:41Z dwhittle $
 */
class ysfProjectConfiguration extends sfProjectConfiguration
{
  protected $dimension = null;

  /**
   * Setup project configuration.
   */
  public function setup()
  {
    // subscribe to cache clear event
    $this->dispatcher->connect('task.cache.clear', array($this, 'listenToClearCache'));

    parent::setup();
  }

  /**
   * Listener for the task.cache.clear event.
   */
  public static function listenToClearCache(sfEvent $event)
  {
    // some applications could not define dimensions for project
    if ($projectConfigurationDimension = sfProjectConfiguration::getActive()->getDimension())
    {
      foreach($projectConfigurationDimension->getAllowed() as $dimensionName => $dimensions)
      {
        foreach($dimensions as $dimension)
        {
          $sf_cache_dir = sfConfig::get('sf_root_dir').'/cache/'.$dimension;
          if(is_dir($sf_cache_dir))
          {
            sfToolkit::clearDirectory($sf_cache_dir);
          }
        }
      }
    }
  }


  /**
   * Sets the project dimension.
   *
   * @param array The configuration dimension as an array
   */
  public function setDimension($dimension)
  {
    if ($dimension === null || $dimension === false)
    {
      $this->dimension = null;

      // if dimension changes, change cache dir
      $this->setCacheDir($this->getRootDir().'/cache');
    }
    else
    {
      try
      {
        if(!$this->hasDimension())
        {
          $cache_options = array('prefix' => 'symfony.dimensions.config.default:'.md5($this->rootDir).':'.$this->application.':'.$this->environment, 'automatic_cleaning_factor' => 0, 'lifetime' => 86400);

          if (!isset($this->debug) || (isset($this->debug) && $this->debug === true))
          {
            $cache = new sfNoCache();
          }
          else if (function_exists('apc_store') && ini_get('apc.enabled'))
          {
            $cache = new sfAPCCache($cache_options);
          }
          else if (function_exists('eaccelerator_put') && ini_get('eaccelerator.enable'))
          {
            $cache = new sfEAcceleratorCache($cache_options);
          }
          else
          {
            $cache = new sfNoCache();
          }

          $this->dimension = new ysfConfigDimension($this->getEventDispatcher(), $cache);
        }

        // set dimension
        $this->dimension->set($dimension);

        sfConfig::set('sf_dimension', $this->dimension->getName());

        // if dimension changes, change cache dir
        $this->setCacheDir($this->getRootDir().'/cache/'.$this->dimension->getName());
      }
      catch (sfException $e)
      {
        if(method_exists($e, 'asResponse'))
        {
          // handle exception early on and exit if something bad happens
          $e->asResponse()->send(); exit;
        }
        else
        {
          echo $e->getMessage(); exit;
        }
      }
    }
  }

  /**
   * Returns the project dimension.
   *
   * @return ysfConfigDimension The configuration dimension
   */
  public function getDimension()
  {
    return $this->dimension;
  }

  /**
   * Has a dimension been set?
   *
   * @return boolean Returns true or false depending on whether a dimension has been set or not.
   */
  public function hasDimension()
  {
    return !is_null($this->dimension);
  }

  /**
   * Gets directories where model classes are stored.
   *
   * @return array An array of directories
   */
  public function getModelDirs()
  {
    // if there is a configuration dimension
    if ($this->hasDimension())
    {
      // if there is a cache return it
      if($this->dimension->getCache()->has('sf_model_dirs'))
      {
        return $this->dimension->getCache()->get('sf_model_dirs');
      }
      else
      {
        $dimensions = array_reverse($this->dimension->getCascade());

        $dirs = array(sfConfig::get('sf_lib_dir').'/model');                       // project

        // extend base dirs and add dimension cascade, checking dir exists
        foreach ($dimensions as $dimension)
        {
          if(is_readable($dirs[0].'/'.$dimension))
          {
            array_unshift($dirs, $dirs[0].'/'.$dimension);
          }
        }

        if ($pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/lib/model'))
        {
          foreach ($pluginDirs as $dir)
          {
            // extend base dirs and add dimension cascade, checking dir exists
            foreach ($dimensions as $dimension)
            {
              if(is_readable($dir.'/'.$dimension))
              {
                array_unshift($pluginDirs, $dir.'/'.$dimension);
              }
            }
          }
          $dirs = array_merge($dirs, $pluginDirs);                               // plugins
        }

        // save cache
        $this->dimension->getCache()->set('sf_model_dirs', $dirs);
      }
    }
    else
    {
      $dirs = parent::getModelDirs();
    }

    return $dirs;
  }

  /**
   * Gets directories where template files are stored for a generator class and a specific theme.
   *
   * @param string The generator class name
   * @param string The theme name
   *
   * @return array An array of directories
   */
  public function getGeneratorTemplateDirs($class, $theme)
  {
    // if there is a configuration dimension
    if ($this->hasDimension())
    {
      $cacheKey = sprintf('sf_generator_template_dirs_%s_%s', $class, $theme);

      // if there is a cache return it
      if($this->dimension->getCache()->has($cacheKey))
      {
       $dirs = $this->dimension->getCache()->get($cacheKey);
      }
      else
      {
        $dimensions = $this->dimension->getCascade();

        // otherwise create and store
        $dirs = array();

        if (is_readable(sfConfig::get('sf_data_dir').'/generator/'.$class.'/'.$theme.'/template'))
        {
          $dir = sfConfig::get('sf_data_dir').'/generator/'.$class.'/'.$theme.'/template';                       // project

          // extend base dirs and add dimension cascade + checking dir exists
          foreach ($dimensions as $dimension)
          {
            if(is_readable($dir.'/'.$dimension))
            {
              array_push($dirs, $dir.'/'.$dimension);
            }
          }

          array_push($dirs, $dir);
        }

        if ($pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/data/generator/'.$class.'/'.$theme.'/template'))
        {
          foreach ($pluginDirs as $dir)
          {
            foreach ($dimensions as $dimension)
            {
              if(is_readable($dir.'/'.$dimension))
              {
                array_push($dirs, $dir.'/'.$dimension);
              }
            }
            array_push($dirs, $dir);                                                                              // plugin
          }
        }

        if ($bundledPluginDirs = glob(sfConfig::get('sf_symfony_lib_dir').'/plugins/*/data/generator/'.$class.'/'.$theme.'/template'))
        {
          $dirs = array_merge($dirs, $bundledPluginDirs);                                                         // bundled plugin
        }

        // save cache
        $this->dimension->getCache()->set($cacheKey, $dirs);
      }
    }
    else
    {
      $dirs = parent::getGeneratorTemplateDirs($class, $theme);
    }

    return $dirs;
  }

  /**
   * Gets directories where the skeleton is stored for a generator class and a specific theme.
   *
   * @param string The generator class name
   * @param string The theme name
   *
   * @return array An array of directories
   */
  public function getGeneratorSkeletonDirs($class, $theme)
  {
    // if there is a configuration dimension
    if ($this->hasDimension())
    {
      $cacheKey = sprintf('sf_generator_skeleton_dirs_%s_%s', $class, $theme);

      // if there is a cache return it
      if($this->dimension->getCache()->has($cacheKey))
      {
       $dirs = $this->dimension->getCache()->get($cacheKey);
      }
      else
      {
        $dimensions = $this->dimension->getCascade();

        // otherwise create and store
        $dirs = array();                  // project

        if(is_readable(sfConfig::get('sf_data_dir').'/generator/'.$class.'/'.$theme.'/skeleton'))
        {
          $dir = sfConfig::get('sf_data_dir').'/generator/'.$class.'/'.$theme.'/skeleton';

          // extend base dirs and add dimension cascade + checking dir exists
          foreach ($dimensions as $dimension)
          {
            if(is_readable($dir.'/'.$dimension))
            {
              array_push($dirs, $dir.'/'.$dimension);
            }
          }
          array_push($dirs, $dir);                                                                              // project
        }

        if ($pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/data/generator/'.$class.'/'.$theme.'/skeleton'))
        {
          foreach ($pluginDirs as $dir)
          {
            foreach ($dimensions as $dimension)
            {
              if(is_readable($dir.'/'.$dimension))
              {
                array_push($dirs, $dir.'/'.$dimension);
              }
            }
            array_push($dirs, $dir);                                                                              // plugin
          }
        }

        if ($bundledPluginDirs = glob(sfConfig::get('sf_symfony_lib_dir').'/plugins/*/data/generator/'.$class.'/'.$theme.'/skeleton'))
        {
          $dirs = array_merge($dirs, $bundledPluginDirs);                                                         // bundled plugin
        }

        // save cache
        $this->dimension->getCache()->set($cacheKey, $dirs);
      }
    }
    else
    {
      $dirs = parent::getGeneratorSkeletonDirs($class, $theme);
    }

    return $dirs;
  }

  /**
   * Gets the template to use for a generator class.
   *
   * @param string The generator class name
   * @param string The theme name
   * @param string The template path
   *
   * @return string A template path
   *
   * @throws sfException
   */
  public function getGeneratorTemplate($class, $theme, $path)
  {
    // if there is a configuration dimension
    if ($this->hasDimension())
    {
      $cacheKey = sprintf('sf_generator_templates_%s_%s_%s', $class, $theme, $path);

      // if there is a cache return it
      if($this->dimension->getCache()->has($cacheKey))
      {
       $dirs = $this->dimension->getCache()->get($cacheKey);
      }
      else
      {
        // otherwise create and store
        $dirs = $this->getGeneratorTemplateDirs($class, $theme);

        foreach ($dirs as $dir)
        {
          if (is_readable($dir.'/'.$path))
          {
            // save cache
            $this->dimension->getCache()->set($cacheKey, $dir.'/'.$path);

            return $dir.'/'.$path;
          }
        }

        throw new sfException(sprintf('Unable to load "%s" generator template in: %s.', $path, implode(', ', $dirs)));
      }
    }
    else
    {
      $dirs = parent::getGeneratorTemplate($class, $theme, $path);
    }

    return $dirs;
  }
}
