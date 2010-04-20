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

require_once(dirname(__FILE__).'/../util/ysfCartesianIterator.class.php');

/**
 * ysfConfigDimension manages configuration dimensions. A dimension can be any parameter that
 * changes configuration selection, template selection, or action execution. For example, your
 * configuration could depend on a host type (production|development|qa|staging), a culture
 * (en|fr|it|de), and a theme (classic|mybrand). You must specify allowed dimension types and
 * values in the project dimensions.yml:
 *
 * allowed:
 *   host:       [production, development, qa, staging]
 *   culture:    [en, fr, it, de]
 *   skin:       [classic, mybrand]
 *
 * # only allowed is required
 *
 * options:
 *   set_default: true
 *
 * default:
 *   host:       production
 *   culture:    en
 *   theme:      classic
 *
 * If the default is not specified, the default dimension is the first value of each dimension
 * type: host => production, culture => en, theme => classic
 *
 * @package    ysymfony
 * @subpackage config
 * @author     Dustin Whittle <dustin.whittle@symfony-project.com>
 * @version    SVN: $Id: sfConfigCache.class.php 5943 2007-11-09 19:59:05Z dwhittle $
 */
class ysfConfigDimension
{

  protected $dispatcher  = null,    // sfEventDispatcher instance
            $cache       = null,    // sfCache instance

            // dimensions configuration (allowed/default = validation rules)
            $config      = null,

            // current dimension info
            $dimension   = null,    // dimension value
            $name        = null,    // dimension name
            $cascade     = array(); // cartesian expansion of dimension

  /**
   * Class constructor.
   *
   * @see initialize()
   */
  public function __construct(sfEventDispatcher $dispatcher, sfCache $cache, $options = array())
  {
    $this->initialize($dispatcher, $cache, $options);
  }

  /**
   * Initializes a dimensions configuration.
   */
  public function initialize(sfEventDispatcher $dispatcher, sfCache $cache, $options = array())
  {
    $this->dispatcher = $dispatcher;
    $this->cache = $cache;

    $this->loadConfiguration();

    $this->options = array_merge($this->config['options'], $options);

    if(isset($this->options['set_default']))
    {
      $this->set($this->getDefault());
    }
  }

  /**
   * Loads and parses the dimensions.yml and configures acceptable dimensions that can be set.
   * Sets the dimension to the default specified in dimension.yml or to first value of each allowed dimension.
   */
  public function loadConfiguration()
  {
    if($this->cache->has('sf_dimensions_configuration'))
    {
      $this->config = $this->cache->get('sf_dimensions_configuration');
    }
    else
    {
      // configure automatically based on dimension.yml
      if($dimensions = sfYaml::load(sfConfig::get('sf_config_dir').'/dimensions.yml'))
      {
        // normalize key values
        $dimensions = array_change_key_case($dimensions, CASE_LOWER);

        if(!isset($dimensions['allowed']) || (isset($dimensions['allowed']) && !is_array($dimensions['allowed'])))
        {
          throw new sfException(sprintf('You must defined allowed dimensions in %s', $sf_dimension_config_file));
        }
        else
        {
          // set allowed dimensions with normalized keys/values
          foreach ($dimensions['allowed'] as $key => $values)
          {
            if(is_array($values))
            {
              $values = array_map('strtolower', $values);
            }
            elseif(is_string($values))
            {
              $values = array(strtolower($values));
            }
            else
            {
              throw new sfException(sprintf('Allowed dimensions in %s must be of type array or string.', $sf_dimension_config_file));
            }

            $this->config['allowed'][strtolower($key)] = $values;
          }
        }

        if(isset($dimensions['default']) && is_array($dimensions['default']))
        {
          // set default dimensions with normalized keys/values
          foreach ($dimensions['default'] as $key => $value)
          {
            if(is_string($value))
            {
              $this->config['default'][strtolower($key)] = strtolower($value);
            }
            else
            {
              throw new sfException(sprintf('Default dimensions in %s must be of type string.', $sf_dimension_config_file));
            }
          }
        }
        else
        {
          // if a default was not set, take the first value of each allowed dimensions and create the default
          $default = array();
          foreach($this->config['allowed'] as $key => $values)
          {
            $default[$key] = $values[0];
          }
          $this->config['default'] = $default;
        }

        // setup any configured options
        if(isset($dimensions['options']) && is_array($dimensions['options']))
        {
          $this->config['options'] = array_merge(array('set_default' => true), $dimensions['options']);
        }
        else
        {
          $this->config['options'] = array();
        }

        $this->cache->set('sf_dimensions_configuration', $this->config);
      }
      else
      {
        throw new sfException(sprintf('Could not find or load dimensions configuration at %s', $sf_dimension_config_file));
      }
    }
  }

  /**
   * Sets an option value.
   *
   * @param string The option name
   * @param mixed  The option value
   */
  public function setOption($name, $value)
  {
    return $this->options[$name] = $value;
  }

  /**
   * Gets an option value.
   *
   * @param  string The option name
   *
   * @return mixed  The option value
   */
  public function getOption($name, $default = null)
  {
    return isset($this->options[$name]) ? $this->options[$name] : $default;
  }

  /**
   * Returns the event dispatcher.
   *
   * @return sfEventDispatcher A sfEventDispatcher instance
   */
  public function getEventDispatcher()
  {
    return $this->dispatcher;
  }

  /**
   * Returns the cache.
   *
   * @return sfCache A sfCache instance
   */
  public function getCache()
  {
    return $this->cache;
  }

  /**
   * Validates the the input dimension against the allowed dimensions configuration
   *
   * @param array the dimension to check
   *
   * @return boolean true if dimension is valid
   */
  public function check($dimension)
  {
    if(!is_array($dimension))
    {
      return false;
    }
    else
    {
      $allowed = array_keys($dimension);
      foreach($allowed as $name)
      {
        if(!isset($this->config['allowed'][$name]) || !in_array($dimension[$name], $this->config['allowed'][$name]))
        {
          throw new sfException(sprintf('The dimension %s is not an allowed dimension.', var_export($dimension, true)));
        }
      }
    }

    return true;
  }

  /**
   * cleans dimensions by normalizing names/values + removing dimensions with null values (reduce lookups)
   *
   * @param array the dimension to clean
   *
   * @return array the cleaned dimension
   */
  public function clean($dimension)
  {
    foreach($dimension as $name => $value)
    {
      if(is_null($value))
      {
        unset($dimension[$name]);
      }
      else
      {
        $dimension[strtolower($name)] = strtolower($value);
      }
    }
    return $dimension;
  }

  /**
   * sets the current dimension
   *
   * @param array the dimension to set
   *
   */
  public function set($dimension)
  {
    $dimension = $this->clean($dimension);

    if($this->check($dimension))
    {
      // reset current dimension info
      $this->dimension = $dimension;
      $this->name = null;
      $this->cascade = null;

      $cachePrefix = $this->cache->getOption('prefix');

      // dimension has been set before so remove the old dimension name
      $cacheSuffix = substr($cachePrefix, strpos($cachePrefix, ':') + 1);

      // update prefix for cache binding to application/environment/dimension
      $this->cache->setOption('prefix', 'symfony.dimensions.config.'.$this->getName().':'.$cacheSuffix);
    }
  }

  /**
   * Get the current dimension
   *
   * @param string a specific dimension name
   *
   * @return array the current dimension
   */
  public function get($dimensionName = null)
  {
    if(is_null($dimensionName))
    {
      return $this->dimension;
    }
    else
    {
      return isset($this->dimension[$dimensionName]) ? $this->dimension[$dimensionName] : null;
    }
  }

  /**
   * getAllowed returns all allowed dimensions as an array
   *
   * @return array all allowed dimensions
   */
  public function getAllowed($dimensionName = null)
  {
    if(is_null($dimensionName))
    {
      return isset($this->config['allowed']) ? $this->config['allowed'] : false;
    }
    else
    {
      return isset($this->config['allowed'][$dimensionName]) ? $this->config['allowed'][$dimensionName] : false;
    }
  }

  /**
   * setDefault sets the default dimension for when no other dimension is combination is matched
   *
   * @param array the dimension to set as default
   *
   */
  public function setDefault($dimension)
  {
    $this->config['default'] = $dimension;
  }

  /**
   * getDefault returns the default dimension.
   *
   * @return array the default dimension
   */
  public function getDefault()
  {
    return $this->config['default'];
  }

  /**
   * Get the current dimension cascade
   *
   * @return array dimensions cascade
   */
  public function getCascade()
  {
    if(is_array($this->dimension) && empty($this->cascade))
    {
      $this->cascade = array();

      if(count($this->dimension) > 1)
      {
        $this->cascade = array_reverse(array_values($this->dimension));

        // create a cacade of dimensions
        $dimensionsCascade = new ysfCartesianIterator();
        foreach($this->dimension as $name => $values)
        {
          $dimensionsCascade->addArray(array($values));

          foreach($dimensionsCascade as $dimension)
          {
            array_unshift($this->cascade, implode('_', $dimension));
          }
        }

        $this->cascade = array_unique($this->cascade); // give most specific dimensions first;
      }
      else
      {
        $this->cascade = array_values($this->dimension);
      }
    }

    return $this->cascade;
  }

  public function getName()
  {
    if(is_array($this->dimension) && is_null($this->name))
    {
      $this->name = '';
      $i = 0;
      foreach ($this->dimension as $name => $value)
      {
        $seperator = ($i > 0) ? '_' : '';
        $this->name .= $seperator.$value;
        $i++;
      }
    }

    return $this->name;
  }

  /**
   * Gets current dimension as a string
   *
   * @return string the current dimension as a string
   */
  public function __toString()
  {
    return $this->getName();
  }
}
