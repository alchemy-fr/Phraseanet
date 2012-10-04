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

class BrowserServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['browser'] = $app->share(function($app) {
            return new \Browser();
        });
    }

    public function boot(Application $app)
    {
    }
}
