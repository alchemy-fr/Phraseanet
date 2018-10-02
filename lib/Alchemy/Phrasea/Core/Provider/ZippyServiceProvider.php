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

use Alchemy\Zippy\Zippy;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ZippyServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['zippy'] = $app->share(function () {
            return Zippy::load();
        });
    }

    public function boot(Application $app)
    {
    }
}
