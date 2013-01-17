<?php

namespace Alchemy\Phrasea\Core\Provider;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

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
