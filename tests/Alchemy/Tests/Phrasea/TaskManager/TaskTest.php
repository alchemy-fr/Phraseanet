<?php

namespace Alchemy\Tests\Phrasea\TaskManager;

use Alchemy\TaskManager\Test\TaskTestCase;
use Alchemy\Phrasea\TaskManager\Task;

class TaskTest extends TaskTestCase
{
    public function testThatCreatedProcessAreDifferents()
    {
        $process = $this->getMock('Symfony\Component\Process\ProcessableInterface');
        $task = new Task('task number', 42, $process);
        
        $created1 = $task->createProcess();
        
        $this->assertEquals($process, $created1);
        $this->assertNotSame($process, $created1);
        
        $created2 = $task->createProcess();
        
        $this->assertEquals($created1, $created2);
        $this->assertNotSame($process, $created2);
        $this->assertNotSame($created1, $created2);
    }
    
    protected function getTask()
    {
        $process = $this->getMock('Symfony\Component\Process\ProcessableInterface');
        
        return new Task('task number', 42, $process);
    }
}
