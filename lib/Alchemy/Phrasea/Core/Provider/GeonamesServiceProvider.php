<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class GeonamesServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['geonames'] = $app->share(function($app) {
            return new \geonames($app);
        });
    }

    public function boot(Application $app)
    {
    }
}
