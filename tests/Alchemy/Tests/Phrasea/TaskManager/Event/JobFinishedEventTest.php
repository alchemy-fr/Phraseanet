<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Event;

use Alchemy\Phrasea\TaskManager\Event\JobFinishedEvent;

class JobFinishedEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $task = $this->getMock('Entities\Task');
        $event = new JobFinishedEvent($task);
        $this->assertSame($task, $event->getTask());
    }
}
