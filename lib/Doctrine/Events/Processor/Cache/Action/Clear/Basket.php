<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Events\Processor\Cache\Action\Clear;

use Doctrine\Common\EventArgs;
use Events\Processor\Cache\Action\AbstractClear;
use Repositories\BasketRepository;
use Entities;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Basket extends AbstractClear
{

  public function process(EventArgs $args)
  {
    $cache = $this->getCacheAdapter($args);
    $cache->deleteBySuffix(Entities\Basket::CACHE_SUFFIX);
    $cache->deleteBySuffix(Entities\BasketElement::CACHE_SUFFIX);
  }

}
