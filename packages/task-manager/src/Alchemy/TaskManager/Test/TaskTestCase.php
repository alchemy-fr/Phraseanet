<?php

namespace Alchemy\TaskManager\Test;

use Alchemy\TaskManager\TaskInterface;

abstract class TaskTestCase extends \PHPUnit\Framework\TestCase
{
    public function testThatCreateProcessReturnsAProcessableInterface()
    {
        $task = $this->getTask();
        $this->assertInstanceOf('Symfony\Component\Process\Process', $task->createProcess());
    }

    public function testThatTheNameIsString()
    {
        $task = $this->getTask();
        $this->assertIsString($task->getName());
    }

    public function testThatTheIterationsIsPositiveValue()
    {
        $task = $this->getTask();
        $this->assertIsInt($task->getIterations());
        $this->assertGreaterThan(0, $task->getIterations());
    }

    /**
     * @return TaskInterface
     */
    abstract protected function getTask();
}
