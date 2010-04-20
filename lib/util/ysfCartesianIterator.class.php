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
 *
 * Iterates through the cartesian product of all iterators added.
 * Values returned are an array of the current values of each iterator
 * in the order they were added.  If an iterator you pass in is
 * a keyed iterator (we presume any non-int key is relevant),
 * the items in the 'current' array will be single-valued
 * key=>value arrays for each such source iterator.
 *
 * @package    ysymfony
 * @subpackage config
 * @author     Dustin Whittle <dustin.whittle@symfony-project.com>
 * @version    SVN: $Id: sfFinder.class.php 5582 2007-10-18 18:10:32Z dwhittle $
 *
 * @example
 *
 * $arr1 = array('a', 'b', 'c');
 * $arr2 = array('y', 'z');
 * $it = new ysfCartesianIterator($arr1, $arr2);
 * foreach ($it as $values)
 * {
 *   print implode("/", $values)."\n";
 * }
 *
 * produces:
 *
 * a/y
 * b/y
 * c/y
 * a/z
 * b/z
 * c/z
 */
class ysfCartesianIterator implements Iterator
{
  private $iterators;
  private $done;

  /**
   * Adds iterators for each array passed into constructor
   */
  public function __construct()
  {
    $this->iterators = array();
    $this->done = true;

    foreach (func_get_args() as $arr)
    {
      $this->addArray($arr);
    }
  }

  /**
   * Adds an array to be iterated over.
   * $name will show up as key for this iterator's values.
   */
  public function addArray($arr, $name='')
  {
    if (is_array($arr))
    {
      $this->addIterator(new ArrayIterator($arr), $name);
    } else
    {
      error_log("Non-array argument to CartesianIterator::addArray");
    }
  }

  /**
   * Adds an iterator to be iterated over.
   */
  public function addIterator(Iterator $it, $name='')
  {
    if (empty($name))
    {
      $name = count($this->iterators);
    }
    $this->iterators[$name] = $it;  // these stay in insertion order
    $this->done = false;
  }

  public function rewind()
  {
    foreach ($this->iterators as $iterator)
    {
      $iterator->rewind();
    }
    $this->done = (empty($this->iterators));
  }

  public function valid()
  {
    return !$this->done;
  }

  public function current()
  {
    if (!$this->valid())
    {
      return NULL;
    }
    $cur = array();
    foreach ($this->iterators as $name=>$iterator)
    {
      $cur[$name] = $iterator->current();
    /*
      // if we want to allow iterations over key=>value arrays,
      // we could do this, but it seems ugly
      $key = $iterator->key();
      if (is_int($key))
      {
        $cur[$name] = $iterator->current();
      }
      else
      {
        $cur[$name] = array($key=>$iterator->current());
      }
    */
    }
    return $cur;
  }

  public function key()
  {
    return NULL;
  }

  public function next()
  {
    if (!$this->valid())
    {
      return; // all done
    }
    foreach ($this->iterators as $iterator)
    {
      $iterator->next();
      if ($iterator->valid())
      {
        return;  // we're done
      }
      else
      {
        // this iterator is at end.  reset and bump next iterator
        $iterator->rewind();
      }
    }
    // fell through.  all iterators are done.
    $this->done = true;
  }

}
