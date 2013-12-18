<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\TasksServiceProvider;
use Alchemy\Tests\Tools\TranslatorMockTrait;

class TasksServiceProviderTest extends ServiceProviderTestCase
{
    use TranslatorMockTrait;

    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\TasksServiceProvider',
                'task-manager.job-factory',
                'Alchemy\Phrasea\TaskManager\Job\Factory'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\TasksServiceProvider',
                'task-manager.status',
                'Alchemy\Phrasea\TaskManager\TaskManagerStatus'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\TasksServiceProvider',
                'task-manager.log-file.factory',
                'Alchemy\Phrasea\TaskManager\Log\LogFileFactory'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\TasksServiceProvider',
                'task-manager.notifier',
                'Alchemy\Phrasea\TaskManager\Notifier'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\TasksServiceProvider',
                'task-manager.live-information',
                'Alchemy\Phrasea\TaskManager\LiveInformation'
            ],
        ];
    }

    public function testGetAvailableJobs()
    {
        $app = $this->loadApp();
        $app['translator'] = $this->createTranslatorMock();
        $app->register(new TasksServiceProvider());
        $app->boot();

        $this->assertInternalType('array', $app['task-manager.available-jobs']);
        foreach ($app['task-manager.available-jobs'] as $job) {
            $this->assertInstanceOf('Alchemy\Phrasea\TaskManager\Job\JobInterface', $job);
        }
    }
}
