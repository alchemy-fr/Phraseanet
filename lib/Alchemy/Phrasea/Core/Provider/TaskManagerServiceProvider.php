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
use Monolog\Logger;
use Monolog\Handler\NullHandler;

class TaskManagerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['task-manager.logger'] = $app->share(function(Application $app) {
            $logger = new Logger('task-manager logger');
            $logger->pushHandler(new NullHandler());

            return $logger;
        });

        $app['task-manager'] = $app->share(function(Application $app) {
            return new \task_manager($app, $app['task-manager.logger']);
        });
    }

    public function boot(Application $app)
    {
    }
}
