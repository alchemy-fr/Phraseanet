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

use Alchemy\Phrasea\TaskManager\Job\FtpJob;
use Alchemy\Phrasea\TaskManager\Job\ArchiveJob;
use Alchemy\Phrasea\TaskManager\Job\BridgeJob;
use Alchemy\Phrasea\TaskManager\Job\FtpPullJob;
use Alchemy\Phrasea\TaskManager\Job\PhraseanetIndexerJob;
use Alchemy\Phrasea\TaskManager\Job\RecordMoverJob;
use Alchemy\Phrasea\TaskManager\Job\SubdefsJob;
use Alchemy\Phrasea\TaskManager\Job\WriteMetadataJob;
use Alchemy\Phrasea\TaskManager\Job\Factory as JobFactory;
use Alchemy\Phrasea\TaskManager\TaskManagerStatus;
use Silex\Application;
use Silex\ServiceProviderInterface;

class TasksServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['task-manager.job-factory'] = $app->share(function(Application $app) {
            return new JobFactory($app['dispatcher'],isset($app['task-manager.logger']) ? $app['task-manager.logger'] : $app['logger']);
        });

        $app['task-manager.status'] = $app->share(function(Application $app) {
            return new TaskManagerStatus($app['phraseanet.configuration']);
        });

        $app['task-manager.available-jobs'] = $app->share(function(Application $app) {
            return array(
                new FtpJob(),
                new ArchiveJob(),
                new BridgeJob(),
                new FtpPullJob(),
                new PhraseanetIndexerJob(),
                new RecordMoverJob(),
                new SubdefsJob(),
                new WriteMetadataJob(),
            );
        });
    }

    public function boot(Application $app)
    {
    }
}
