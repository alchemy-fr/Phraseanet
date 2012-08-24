<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class SearchENgineServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['phraseanet.SE'] = $app->share(function($app) {
                return $app['phraseanet.core']['SearchEngine'];
            });
    }

    public function boot(Application $app)
    {

    }
}
