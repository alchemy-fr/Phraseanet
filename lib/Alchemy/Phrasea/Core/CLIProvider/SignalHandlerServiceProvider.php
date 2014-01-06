<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\CLIProvider;

use Neutron\SignalHandler\SignalHandler;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SignalHandlerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['signal-handler'] = $app->share(function (Application $app) {
           return SignalHandler::getInstance();
        });
    }

    public function boot(Application $app)
    {
    }
}
