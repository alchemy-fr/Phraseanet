<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Events\Processor\Cache\Action;

use Doctrine\Common\Cache;
use Events\Processor\Processor;
use Doctrine\Common\EventArgs;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class AbstractClear implements Processor
{

  /**
   * Return the current used result cache adapter
   * @param EventArgs $args
   * @return \Doctrine\Common\Cache
   */
  protected function getCacheAdapter(EventArgs $args)
  {
    $em = $args->getEntityManager();

    return $em->getConfiguration()->getResultCacheImpl();
  }

  /**
   * Return the processed entity cache suffix
   * @param EventArgs $args
   * @return string
   * @throws \Exception
   */
  protected function getEntityCacheSuffix(EventArgs $args)
  {
    $entity = new \ReflectionClass(get_class($args->getEntity()));

    if (!$entity->hasConstant("CACHE_SUFFIX"))
    {
      throw new \Exception(sprintf("Missing cache suffix for %s entity", $entity->getName()));
    }

    return $entity->getConstant("CACHE_SUFFIX");
  }

}
