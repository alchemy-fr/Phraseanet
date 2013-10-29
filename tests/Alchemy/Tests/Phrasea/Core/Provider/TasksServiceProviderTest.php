<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\TasksServiceProvider;
use Silex\Application;

class TasksServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array(
                'Alchemy\Phrasea\Core\Provider\TasksServiceProvider',
                'task-manager.job-factory',
                'Alchemy\Phrasea\TaskManager\Job\Factory'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\TasksServiceProvider',
                'task-manager.status',
                'Alchemy\Phrasea\TaskManager\TaskManagerStatus'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\TasksServiceProvider',
                'task-manager.log-file.factory',
                'Alchemy\Phrasea\TaskManager\Log\LogFileFactory'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\TasksServiceProvider',
                'task-manager.notifier',
                'Alchemy\Phrasea\TaskManager\Notifier'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\TasksServiceProvider',
                'task-manager.live-information',
                'Alchemy\Phrasea\TaskManager\LiveInformation'
            ),
        );
    }

    public function testGetAvailableJobs()
    {
        $app = new Application();
        $app->register(new TasksServiceProvider());
        $app->boot();

        $this->assertInternalType('array', $app['task-manager.available-jobs']);
        foreach ($app['task-manager.available-jobs'] as $job) {
            $this->assertInstanceOf('Alchemy\Phrasea\TaskManager\Job\JobInterface', $job);
        }
    }
}
