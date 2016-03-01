<?php

namespace Alchemy\Tests\Phrasea\TaskManager;

use Alchemy\TaskManager\Test\TaskTestCase;
use Alchemy\Phrasea\TaskManager\Task;

class TaskTest extends TaskTestCase
{
    private $process;

    public function testThatCreatedProcessAreDifferents()
    {
        $task = $this->getTask();

        $created1 = $task->createProcess();

        $this->assertEquals($this->process, $created1);
        $this->assertNotSame($this->process, $created1);

        $created2 = $task->createProcess();

        $this->assertEquals($created1, $created2);
        $this->assertNotSame($this->process, $created2);
        $this->assertNotSame($created1, $created2);
    }

    protected function getTask()
    {
        $this->process = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $taskEntity = $this->getMock('Alchemy\Phrasea\Model\Entities\Task');

        return new Task($taskEntity, 'task number', 42, $this->process);
    }
}
