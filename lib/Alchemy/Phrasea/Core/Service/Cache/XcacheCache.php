<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\Cache;

use Alchemy\Phrasea\Core\Service\ServiceAbstract;
use Alchemy\Phrasea\Cache as CacheDriver;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class XcacheCache extends ServiceAbstract
{
    protected $cache;

    public function getDriver()
    {
        if ( ! extension_loaded('xcache')) {
            throw new \Exception('The XCache cache requires the XCache extension.');
        }

        if (! $this->cache) {
            $this->cache = new CacheDriver\XcacheCache();

            $this->cache->setNamespace(md5(realpath(__DIR__ . '/../../../../../../')));
        }

        return $this->cache;
    }

    public function getType()
    {
        return 'xcache';
    }
}
