<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Requirements\OpcodeCacheRequirement;
use Alchemy\Phrasea\Cache\Cache;

class OpcodeCacheProbe extends OpcodeCacheRequirement implements ProbeInterface
{
    public function __construct(Cache $cache)
    {
        parent::__construct();

        $this->addInformation('Current implementation', get_class($cache));

        $this->addRecommendation(
            'Alchemy\Phrasea\Cache\ArrayCache' !== get_class($cache),
            'ArrayCache should not be used in production',
            'Configure an Opcode Cache'
        );

        if (null !== $cache->getStats()) {
            foreach($cache->getStats() as $name => $value) {
                $this->addInformation($name, $value);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return BinariesProbe
     */
    public static function create(Application $app)
    {
        return new static($app['opcode-cache']);
    }
}
