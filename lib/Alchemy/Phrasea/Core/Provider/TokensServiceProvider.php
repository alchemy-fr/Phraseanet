<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class TokensServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['tokens'] = $app->share(function ($app) {
            return new \random($app);
        });
    }

    public function boot(Application $app)
    {
    }
}
