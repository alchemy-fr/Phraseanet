<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Event;

use Alchemy\Phrasea\TaskManager\Event\JobFinishedEvent;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class JobFinishedEventTest extends \PhraseanetTestCase
{
    public function testEvent()
    {
        $task = $this->createStub('Alchemy\Phrasea\Model\Entities\Task');
        $event = new JobFinishedEvent($task);
        $this->assertSame($task, $event->getTask());
    }
}
