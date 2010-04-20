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

/**
 * ysfApplicationConfiguration represents a configuration for a symfony application.
 *
 * @package    ysymfony
 * @subpackage config
 * @author     Dustin Whittle <dustin.whittle@symfony-project.com>
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfApplicationConfiguration.class.php 7618 2008-02-27 00:02:41Z dwhittle $
 */
abstract class ysfApplicationConfiguration extends sfApplicationConfiguration
{

  /**
   * Configures the current configuration.
   */
  public function configure()
  {
    parent::configure();

    if ($this->hasDimension())
    {
      $this->setCacheDir($this->getRootDir().'/cache/'.$this->dimension->getName());
    }
  }

  /**
   * Initialized the current configuration.
   */
  public function initialize()
  {
    parent::initialize();
  }

  /**
   * Gets directories where controller classes are stored for a given module.
   *
   * @param string The module name
   *
   * @return array An array of directories
   */
  public function getControllerDirs($moduleName)
  {
    // if there is a configuration dimension
    if ($this->hasDimension())
    {
      $cacheKey = sprintf('sf_controller_dirs_%s', $moduleName);

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

        if (is_readable(sfConfig::get('sf_app_module_dir').'/'.$moduleName.'/actions'))
        {
          foreach ($dimensions as $dimension)
          {
            if(is_readable(sfConfig::get('sf_app_module_dir').'/'.$moduleName.'/actions/'.$dimension))
            {
              $dirs[sfConfig::get('sf_app_module_dir').'/'.$moduleName.'/actions/'.$dimension] = false;
            }
          }

          $dirs[sfConfig::get('sf_app_module_dir').'/'.$moduleName.'/actions'] = false;                        // application
        }

        foreach (sfConfig::get('sf_module_dirs', array()) as $key => $value)
        {
          if(is_readable($key.'/'.$moduleName.'/actions'))
          {
            // extend base dirs and add dimension cascade + checking dir exists
            foreach ($dimensions as $dimension)
            {
              if(is_readable($key.'/'.$moduleName.'/actions/'.$dimension))
              {
                $dirs[$key.'/'.$moduleName.'/actions/'.$dimension] = $value;
              }
            }

            $dirs[$key.'/'.$moduleName.'/actions'] = $value;
          }
        }

        if ($pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/modules/'.$moduleName.'/actions'))
        {
          foreach ($pluginDirs as $dir)
          {
            // extend base dirs and add dimension cascade + checking dir exists
            foreach ($dimensions as $dimension)
            {
              if(is_readable($dir.'/'.$dimension))
              {
                array_push($pluginDirs, $dir.'/'.$dimension);
              }
            }
          }

          $dirs = array_merge($dirs, array_combine($pluginDirs, array_fill(0, count($pluginDirs), true)));                // plugins
        }

        if (is_readable(sfConfig::get('sf_symfony_lib_dir').'/controller/'.$moduleName.'/actions'))
        {
          $dirs[sfConfig::get('sf_symfony_lib_dir').'/controller/'.$moduleName.'/actions'] = true;                          // core modules
        }

        // save cache
        $this->dimension->getCache()->set($cacheKey, $dirs);
      }
    }
    else
    {
      $dirs = parent::getControllerDirs($moduleName);
    }

    return $dirs;
  }

  /**
   * Gets directories where template files are stored for a given module.
   *
   * @param string The module name
   *
   * @return array An array of directories
   */
  public function getTemplateDirs($moduleName)
  {
    // if there is a configuration dimension
    if ($this->hasDimension())
    {
      $cacheKey = sprintf('sf_template_dirs_%s', $moduleName);

      // if there is a cache return it
      if($this->dimension->getCache()->has($cacheKey))
      {
        $dirs = $this->dimension->getCache()->get($cacheKey);
      }
      else
      {
        $dirs = array();

        $dimensions = $this->dimension->getCascade();

        foreach (sfConfig::get('sf_module_dirs', array()) as $key => $value)
        {
          if (is_readable($key.'/'.$moduleName.'/templates'))
          {
            foreach ($dimensions as $dimension)
            {
              if (is_readable($key.'/'.$moduleName.'/templates/'.$dimension))
              {
                array_push($dirs, $key.'/'.$moduleName.'/templates/'.$dimension);
              }
            }
            array_push($dirs, $key.'/'.$moduleName.'/templates');
          }
        }

        if(is_readable(sfConfig::get('sf_app_module_dir').'/'.$moduleName.'/templates'))
        {
          foreach ($dimensions as $dimension)
          {
            if(is_readable(sfConfig::get('sf_app_module_dir').'/'.$moduleName.'/templates/'.$dimension))
            {
              array_push($dirs, sfConfig::get('sf_app_module_dir').'/'.$moduleName.'/templates/'.$dimension);
            }
          }
          array_push($dirs, sfConfig::get('sf_app_module_dir').'/'.$moduleName.'/templates');              // application module
        }

        if ($pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/modules/'.$moduleName.'/templates'))
        {
          foreach ($pluginDirs as $dir)
          {
            foreach($dimensions as $dimension)
            {
              if (is_readable($dir.'/'.$dimension))
              {
                array_push($dirs, $dir.'/'.$dimension);
              }
            }
            array_push($dirs, $dir);                                                                      // plugins
          }
        }

        $dirs[] = sfConfig::get('sf_module_cache_dir').'/auto'.ucfirst($moduleName.'/templates');         // generated templates in cache
        $dirs[] = sfConfig::get('sf_symfony_lib_dir').'/controller/'.$moduleName.'/templates';            // core modules

        // save cache
        $this->dimension->getCache()->set($cacheKey, $dirs);
      }
    }
    else
    {
      $dirs = parent::getTemplateDirs($moduleName);
    }

    return $dirs;
  }

  /**
   * Gets the template directory to use for a given module and template file.
   *
   * @param string The module name
   * @param string The template file
   *
   * @return string A template directory
   */
  public function getTemplateDir($moduleName, $templateFile)
  {
    // if there is a configuration dimension
    if ($this->hasDimension())
    {
      $cacheKey = sprintf('sf_template_dirs_%s_%s', $moduleName, $templateFile);

      // if there is a cache return it
      if($this->dimension->getCache()->has($cacheKey))
      {
        $dirs = $this->dimension->getCache()->get($cacheKey);
      }
      else
      {
        $dirs = null;

        $paths = $this->getTemplateDirs($moduleName);
        foreach ($paths as $dir)
        {
          if (is_readable($dir.'/'.$templateFile))
          {
            $dirs = $dir;
            break;
          }
        }

        /*
        if($dirs === null)
        {
          throw new sfException(sprintf('Could not find template "%s" in paths "%s"', $templateFile, var_export($paths, true)));
        }
        */

        // save cache
        $this->dimension->getCache()->set($cacheKey, $dirs);
      }
    }
    else
    {
      $dirs = parent::getTemplateDir($moduleName, $templateFile);
    }

    return $dirs;
  }

  /**
   * Gets the template to use for a given module and template file.
   *
   * @param string The module name
   * @param string The template file
   *
   * @return string A template path
   */
  public function getTemplatePath($moduleName, $templateFile)
  {
    $dir = $this->getTemplateDir($moduleName, $templateFile);

    return $dir ? $dir.'/'.$templateFile : null;
  }

  /**
   * Gets the decorator directories.
   *
   * @param  string The template file
   *
   * @return array  An array of the decorator directories
   *
   */
  public function getDecoratorDirs()
  {
    // if there is a configuration dimension
    if ($this->hasDimension())
    {
      $cacheKey = 'sf_decorator_dirs';

      // if there is a cache return it
      if($this->dimension->getCache()->has($cacheKey))
      {
        $dirs = $this->dimension->getCache()->get($cacheKey);
      }
      else
      {
        $dirs = array();
        $dimensions = $this->dimension->getCascade();

        $dir = sfConfig::get('sf_app_template_dir');
        foreach ($dimensions as $dimension)
        {
          if (is_readable($dir.'/'.$dimension))
          {
            array_push($dirs, $dir.'/'.$dimension);
          }
        }
        array_push($dirs, $dir);

        // save cache
        $this->dimension->getCache()->set($cacheKey, $dirs);
      }
    }
    else
    {
      $dirs = parent::getDecoratorDirs();
    }

    return $dirs;
  }

  /**
   * Gets the decorator directory for a given template.
   *
   * @param  string The template file
   *
   * @return string A template directory
   */
  public function getDecoratorDir($template)
  {
    // if there is a configuration dimension
    if ($this->hasDimension())
    {
      $cacheKey = sprintf('sf_decorator_dir_%s', $template);

      // if there is a cache return it
      if($this->dimension->getCache()->has($cacheKey))
      {
        $decoratorDir = $this->dimension->getCache()->get($cacheKey);
      }
      else
      {
        $dirs = $this->getDecoratorDirs();
        foreach ($dirs as $dir)
        {
          if (is_readable($dir.'/'.$template))
          {
            $decoratorDir = $dir;
            break; // find most specific and then break
          }
        }

        // save cache
        $this->dimension->getCache()->set($cacheKey, $decoratorDir);
      }
    }
    else
    {
      $decoratorDir = parent::getDecoratorDir($template);
    }

    return $decoratorDir;
  }

  /**
   * Gets the i18n directories to use globally.
   *
   * @return array An array of i18n directories
   */
  public function getI18NGlobalDirs()
  {
    // if there is a configuration dimension
    if ($this->hasDimension())
    {
      $cacheKey = 'sf_i18n_global_dirs';

      // if there is a cache return it
      if($this->dimension->getCache()->has($cacheKey))
      {
        $dirs = $this->dimension->getCache()->get($cacheKey);
      }
      else
      {
        $dirs = array();

        // application
        if (is_dir($dir = sfConfig::get('sf_app_dir').'/i18n'))
        {
          array_push($dirs, $dir);
        }

        // plugins
        $pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/i18n');
        if (isset($pluginDirs[0]))
        {
          array_push($dirs, $pluginDirs[0]);
        }

        // save cache
        $this->dimension->getCache()->set($cacheKey, $dirs);
      }
    }
    else
    {
      $dirs = parent::getI18NGlobalDirs();
    }

    return $dirs;
  }

  /**
   * Gets the i18n directories to use for a given module.
   *
   * @param string The module name
   *
   * @return array An array of i18n directories
   */
  public function getI18NDirs($moduleName)
  {
    // if there is a configuration dimension
    if ($this->hasDimension())
    {
      $cacheKey = sprintf('sf_i18n_dirs_%s', $moduleName);

      // if there is a cache return it
      if($this->dimension->getCache()->has($cacheKey))
      {
        $dirs = $this->dimension->getCache()->get($cacheKey);
      }
      else
      {
        $dirs = array();

        // module
        if (is_dir($dir = sfConfig::get('sf_app_module_dir').'/'.$moduleName.'/i18n'))
        {
          array_push($dirs, $dir);
        }

        // application
        if (is_dir($dir = sfConfig::get('sf_app_dir').'/i18n'))
        {
          array_push($dirs, $dir);
        }

        // module in plugins
        $pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/modules/'.$moduleName.'/i18n');
        if (isset($pluginDirs[0]))
        {
          array_push($dirs, $pluginDirs[0]);
        }

        // plugins
        $pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/i18n');
        if (isset($pluginDirs[0]))
        {
          array_push($dirs, $pluginDirs[0]);
        }

        // save cache
        $this->dimension->getCache()->set($cacheKey, $dirs);
      }
    }
    else
    {
      $dirs = parent::getI18NDirs($moduleName);
    }

    return $dirs;
  }

  /**
   * Gets the configuration file paths for a given relative configuration path.
   *
   * @param string The configuration path
   *
   * @return array An array of paths
   */
  public function getConfigPaths($configPath)
  {
    // returned in reverse order cascade

    // if there is a configuration dimension
    if ($this->hasDimension())
    {
      $cacheKey = sprintf('sf_config_dirs_%s', $configPath);

      // if there is a cache return it
      if($this->dimension->getCache()->has($cacheKey))
      {
        $configs = $this->dimension->getCache()->get($cacheKey);
      }
      else
      {
        // reverse cascade
        $dimensions = array_reverse($this->dimension->getCascade());

        // $configPath = modules/blah/config/config.yml | config/config.yml

        $configDirName = dirname($configPath);
        $configFileName = basename($configPath);

        $globalConfigPath = basename($configDirName).'/'.$configFileName; // config/config.yml

        $files = array(
        sfConfig::get('sf_symfony_lib_dir').'/config/'.$globalConfigPath,                   // symfony
        );

        if ($bundledPluginConfigs = glob(sfConfig::get('sf_symfony_lib_dir').'/plugins/*/'.$globalConfigPath))
        {
          $files = array_merge($files, $bundledPluginConfigs);                             // bundled plugins
        }

        if ($pluginConfigs = glob(sfConfig::get('sf_plugins_dir').'/*/'.$globalConfigPath))
        {
          foreach ($pluginConfigs as $pluginConfig)
          {
            $configDir = dirname($pluginConfig);

            array_push($files, $pluginConfig);                                             // plugins

            foreach ($dimensions as $dimension)
            {
              array_push($files, $configDir.'/'.$dimension.'/'.basename($pluginConfig));   // plugin dimensions
            }
          }
        }

        if (is_readable(sfConfig::get('sf_root_dir').'/'.$globalConfigPath))
        {
          $configDir = dirname(sfConfig::get('sf_root_dir').'/'.$globalConfigPath);
          array_push($files, sfConfig::get('sf_root_dir').'/'.$globalConfigPath);  	       // project
          foreach ($dimensions as $dimension)
          {
            array_push($files, $configDir.'/'.$dimension.'/'.basename($globalConfigPath)); // project dimensions
          }
        }

        if (is_readable(sfConfig::get('sf_root_dir').'/'.$configPath))
        {
          $configDir = dirname(sfConfig::get('sf_root_dir').'/'.$configPath);
          array_push($files, sfConfig::get('sf_root_dir').'/'.$configPath);  	             // project
          foreach ($dimensions as $dimension)
          {
            array_push($files, $configDir.'/'.$dimension.'/'.basename($configPath));       // project dimensions
          }
        }

        if (is_readable(sfConfig::get('sf_app_dir').'/'.$globalConfigPath))
        {
          $configDir = dirname(sfConfig::get('sf_app_dir').'/'.$globalConfigPath);
          array_push($files, sfConfig::get('sf_app_dir').'/'.$globalConfigPath);  	       // application
          foreach ($dimensions as $dimension)
          {
            array_push($files, $configDir.'/'.$dimension.'/'.basename($globalConfigPath)); // application dimensions
          }
        }

        array_push($files, sfConfig::get('sf_app_cache_dir').'/'.$configPath);             // generated modules

        if ($pluginConfigs = glob(sfConfig::get('sf_plugins_dir').'/*/'.$configPath))
        {
          foreach ($pluginConfigs as $pluginConfig)
          {
            $configDir = dirname($pluginConfig);

            array_push($files, $pluginConfig);                                             // plugins

            foreach ($dimensions as $dimension)
            {
              array_push($files, $configDir.'/'.$dimension.'/'.basename($pluginConfig));   // plugin dimensions
            }
          }
        }

        if (is_readable(sfConfig::get('sf_app_dir').'/'.$configPath))
        {
          $configDir = dirname(sfConfig::get('sf_app_dir').'/'.$configPath);
          array_push($files, sfConfig::get('sf_app_dir').'/'.$configPath);  	       // module
          foreach ($dimensions as $dimension)
          {
            array_push($files, $configDir.'/'.$dimension.'/'.basename($configPath)); // module
          }
        }

        $configs = array();
        $files = array_unique($files);
        foreach ($files as $file)
        {
          if (is_readable($file))
          {
            $configs[] = $file;
          }
        }

        // save cache
        $this->dimension->getCache()->set($cacheKey, $configs);
      }
    }
    else
    {
      $configs = parent::getConfigPaths($configPath);
    }

    return $configs;
  }
}
