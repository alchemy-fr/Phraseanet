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

use Alchemy\Phrasea\Model\Converter\BasketConverter;
use Alchemy\Phrasea\Model\Converter\TaskConverter;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ConvertersServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['converter.task'] = $app->share(function ($app) {
            return new TaskConverter($app['EM']);
        });

        $app['converter.basket'] = $app->share(function ($app) {
            return new BasketConverter($app['EM']);
        });
    }

    public function boot(Application $app)
    {
    }
}
