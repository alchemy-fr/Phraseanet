<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\TaskManagerServiceProvider
 */
class TaskManagerServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\CLIProvider\TaskManagerServiceProvider',
                'task-manager.logger',
                'Monolog\Logger'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\TaskManagerServiceProvider',
                'task-manager',
                'Alchemy\TaskManager\TaskManager'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\TaskManagerServiceProvider',
                'task-manager.task-list',
                'Alchemy\Phrasea\TaskManager\TaskList'
            ],
        ];
    }
}
