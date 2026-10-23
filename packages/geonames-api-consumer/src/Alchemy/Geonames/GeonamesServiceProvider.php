<?php

/*
 * This file is part of Geonames API Connector.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Geonames;

use GuzzleHttp\Client;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class GeonamesServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['geonames.connector'] = function (Container $app) {
            if (!isset($app['geonames.server-uri'])) {
                throw new \RuntimeException('You must set `geonames.server-uri`.');
            }

            return new Connector($app['geonames.guzzle-client'], $app['geonames.server-uri']);
        };

        $app['geonames.guzzle-client'] = function () {
            return new Client();
        };
    }
}
