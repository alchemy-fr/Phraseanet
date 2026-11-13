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
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;

class SignalHandlerServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['signal-handler'] = function (Application $app) {
           return SignalHandler::getInstance();
        };
    }
}
