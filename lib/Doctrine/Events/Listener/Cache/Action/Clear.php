<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Events\Listener\Cache\Action;

use Entities;
use Doctrine\ORM\Event\LifecycleEventArgs, Doctrine\Common\Cache\Cache;
use Events\Processor\Factory, Events\Processor\Factory\Exception as ProcessorNotFound;

/**
 * Clear event used to delete entries in cache
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Clear
{

  public function postUpdate(LifecycleEventArgs $eventArgs)
  {
    $this->clear($eventArgs);
  }

  public function postRemove(LifecycleEventArgs $eventArgs)
  {
    $this->clear($eventArgs);
  }

  public function postPersist(LifecycleEventArgs $eventArgs)
  {
    $this->clear($eventArgs);
  }

  private function clear(LifecycleEventArgs $eventArgs)
  {
    try
    {
      //get proper cache action processor for the processed entity
      $factory = new Factory\CacheAction(get_class($eventArgs->getEntity()));
      $factory->getProcessor()->process($eventArgs);
    }
    catch (ProcessorNotFound $e)
    {
      
    }
  }

}