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

use Alchemy\Phrasea\TaskManager\Job\FtpJob;
use Alchemy\Phrasea\TaskManager\Job\ArchiveJob;
use Alchemy\Phrasea\TaskManager\Job\BridgeJob;
use Alchemy\Phrasea\TaskManager\Job\FtpPullJob;
use Alchemy\Phrasea\TaskManager\Job\PhraseanetIndexerJob;
use Alchemy\Phrasea\TaskManager\Job\RecordMoverJob;
use Alchemy\Phrasea\TaskManager\Job\SubdefsJob;
use Alchemy\Phrasea\TaskManager\Job\WriteMetadataJob;
use Alchemy\Phrasea\TaskManager\Job\Factory as JobFactory;
use Alchemy\Phrasea\TaskManager\LiveInformation;
use Alchemy\Phrasea\TaskManager\TaskManagerStatus;
use Alchemy\Phrasea\TaskManager\Log\LogFileFactory;
use Alchemy\Phrasea\TaskManager\Notifier;
use Silex\Application;
use Silex\ServiceProviderInterface;

class TasksServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['task-manager.notifier'] = $app->share(function (Application $app) {
            return Notifier::create($app['task-manager.listener.options']);
        });

        $app['task-manager.listener.options'] = $app->share(function (Application $app) {
            return array_replace([
                'protocol'  => 'tcp',
                'host'      => '127.0.0.1',
                'port'      => 6660,
                'linger'    => 500,
            ], $app['conf']->get(['main', 'task-manager', 'listener'], []));
        });

        $app['task-manager.job-factory'] = $app->share(function (Application $app) {
            return new JobFactory($app['dispatcher'],isset($app['task-manager.logger']) ? $app['task-manager.logger'] : $app['logger'], $app['translator']);
        });

        $app['task-manager.status'] = $app->share(function (Application $app) {
            return new TaskManagerStatus($app['configuration.store']);
        });

        $app['task-manager.live-information'] = $app->share(function (Application $app) {
            return new LiveInformation($app['task-manager.status'], $app['task-manager.notifier']);
        });

        $app['task-manager.log-file.root'] = $app->share(function (Application $app) {
            return $app['root.path'].'/logs';
        });

        $app['task-manager.log-file.factory'] = $app->share(function (Application $app) {
            return new LogFileFactory($app['task-manager.log-file.root']);
        });

        $app['task-manager.available-jobs'] = $app->share(function (Application $app) {
            return [
                new FtpJob($app['dispatcher'], $app['logger'], $app['translator']),
                new ArchiveJob($app['dispatcher'], $app['logger'], $app['translator']),
                new BridgeJob($app['dispatcher'], $app['logger'], $app['translator']),
                new FtpPullJob($app['dispatcher'], $app['logger'], $app['translator']),
                new PhraseanetIndexerJob($app['dispatcher'], $app['logger'], $app['translator']),
                new RecordMoverJob($app['dispatcher'], $app['logger'], $app['translator']),
                new SubdefsJob($app['dispatcher'], $app['logger'], $app['translator']),
                new WriteMetadataJob($app['dispatcher'], $app['logger'], $app['translator']),
            ];
        });
    }

    public function boot(Application $app)
    {
    }
}
