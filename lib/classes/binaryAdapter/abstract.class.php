<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * General abstract object for binaryAdapter
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class binaryAdapter_abstract
{

  /**
   *
   * @var boolean
   */
  protected $debug = false;
  /**
   *
   * @var array
   */
  protected $options = array();
  /**
   *
   * @var registry
   */
  protected $registry;

  /**
   * Options setter
   *
   * @param array $options
   * @return binaryAdapter_abstract
   */
  public function set_options($options)
  {
    foreach ($options as $option_name => $option_value)
    {
      $this->set_option($option_name, $option_value);
    }

    return $this;
  }

  /**
   * Option setter
   *
   * @param array $name
   * @param array $value
   * @return binaryAdapter_abstract
   */
  protected function set_option($name, $value)
  {
    $this->options[$name] = $value;

    return $this;
  }

  /**
   * Logger
   *
   * @param <type> $message
   * @return binaryAdapter_abstract
   */
  public function log($message)
  {
    if ($this->debug)
    {
      echo "\t --> \t" . $message . "\n";
    }

    return $this;
  }

}
