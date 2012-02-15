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

use Doctrine\Common\ClassLoader;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

class CacheAction extends FactoryAbstract
{
  public static function create($processor)
  {
    $entity = ucfirst(array_pop(explode("\\", $processor)));
    $className = sprintf("Events\Processor\Cache\Action\Clear\%s", $entity);

    if(!class_exists($className))
    {
      throw new Exception(sprintf("Unknow processor %s", $processor));
    }
    
    return new $className();
  }
}