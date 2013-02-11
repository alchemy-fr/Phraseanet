<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\TaskManagerServiceProvider
 */
class TaskManagerServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\TaskManagerServiceProvider', 'task-manager', '\task_manager'),
        );
    }
}
