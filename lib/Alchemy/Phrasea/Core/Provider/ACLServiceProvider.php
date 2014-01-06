<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\ACL\BasketACL;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ACLServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['acl.basket'] = $app->share(function ($app) {
            return new BasketACL();
        });
    }

    public function boot(Application $app)
    {
    }
}
