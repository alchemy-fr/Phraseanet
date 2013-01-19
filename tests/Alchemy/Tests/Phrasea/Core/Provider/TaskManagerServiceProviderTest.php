<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\TaskManagerServiceProvider;

class TaskManagerServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @covers Alchemy\Phrasea\Core\Provider\TaskManagerServiceProvider
     */
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new TaskManagerServiceProvider());

        $this->assertInstanceof('task_manager', self::$DI['app']['task-manager']);
    }
}
