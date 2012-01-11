<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

class ServiceAbstract
{
  protected $name;
  protected $options;
  
  public function __construct($name, Array $options)
  {
    $this->name = $name;
    $this->options = $options;
  }
  
  /**
   *
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   *
   * @return Array
   */
  public function getOptions()
  {
    return $this->options;
  }
  
  /**
   *
   * @return string
   */
  public function getVersion()
  {
    return 'Unknow';
  }
}