<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Requirements\CacheServerRequirement;
use Alchemy\Phrasea\Cache\Cache;

class CacheServerProbe extends CacheServerRequirement implements ProbeInterface
{
    public function __construct(Cache $cache)
    {
        parent::__construct();

        $this->addInformation('Current implementation', get_class($cache));

        $this->addRecommendation(
            'Alchemy\Phrasea\Cache\ArrayCache' !== get_class($cache),
            'ArrayCache should not be used in production',
            'Configure a Cache Server'
        );

        if (null !== $cache->getStats()) {
            foreach ($cache->getStats() as $name => $value) {
                $this->addInformation($name, $value);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return CacheServerProbe
     */
    public static function create(Application $app)
    {
        return new static($app['cache']);
    }
}
