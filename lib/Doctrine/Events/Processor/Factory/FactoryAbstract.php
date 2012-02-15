<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Events\Processor\Factory;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class FactoryAbstract
{

  /**
   * @var Events\Processor\Processor  
   */
  private $processor;

  public function __construct($processor)
  {
    $this->processor = static::create($processor);
  }

  /**
   * @return Events\Processor\Processor 
   */
  public function getProcessor()
  {
    return $this->processor;
  }

  /**
   * Static function which create the proper processor
   * @param type $element
   * @throws \Exception 
   */
  abstract public static function create($processor);

}

