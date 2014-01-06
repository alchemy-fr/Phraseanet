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

use Alchemy\TaskManager\TaskManager;
use Alchemy\Phrasea\TaskManager\TaskList;
use Monolog\Handler\NullHandler;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Process\PhpExecutableFinder;

class TaskManagerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['task-manager.logger'] = $app->share(function (Application $app) {
            $logger = new $app['monolog.logger.class']('task-manager logger');
            $logger->pushHandler(new NullHandler());

            return $logger;
        });

        $app['task-manager'] = $app->share(function (Application $app) {
            $options = $app['task-manager.listener.options'];

            return TaskManager::create(
                $app['dispatcher'],
                $app['task-manager.logger'],
                $app['task-manager.task-list'],
                [
                    'listener_protocol' => $options['protocol'],
                    'listener_host'     => $options['host'],
                    'listener_port'     => $options['port'],
                ]
            );
        });

        $app['task-manager.task-list'] = $app->share(function (Application $app) {
            $conf = $app['conf']->get(['registry', 'executables', 'php-conf-path']);
            $finder = new PhpExecutableFinder();
            $php = $finder->find();

            return new TaskList($app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Task'), $app['root.path'], $php, $conf);
        });
    }

    public function boot(Application $app)
    {
    }
}
