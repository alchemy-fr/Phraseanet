<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\Cache;

use Alchemy\Phrasea\Core,
    Alchemy\Phrasea\Core\Service,
    Alchemy\Phrasea\Core\Service\ServiceAbstract,
    Alchemy\Phrasea\Core\Service\ServiceInterface;

use Doctrine\Common\Cache\ApcCache;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Apc extends ServiceAbstract implements ServiceInterface
{

  public function getScope()
  {
    return 'cache';
  }

  /**
   *
   * @return Cache\ApcCache 
   */
  public function getService()
  {
    if (!extension_loaded('apc'))
    {
      throw new \Exception('The APC cache requires the APC extension.');
    }

    return new ApcCache();
  }

  public function getType()
  {
    return 'apc';
  }

}

