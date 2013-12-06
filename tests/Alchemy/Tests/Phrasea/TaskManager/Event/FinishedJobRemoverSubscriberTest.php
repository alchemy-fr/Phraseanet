<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Event;

use Alchemy\Phrasea\TaskManager\Event\FinishedJobRemoverSubscriber;
use Alchemy\Phrasea\TaskManager\Event\JobFinishedEvent;
use Alchemy\Phrasea\Model\Entities\Task;

class FinishedJobRemoverSubscriberTest extends \PhraseanetTestCase
{
    public function testOnJobFinish()
    {
        $task = new Task();
        $task
            ->setName('task')
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');
        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();
        $taskId = $task->getId();

        $subscriber = new FinishedJobRemoverSubscriber(self::$DI['app']['EM']);
        $subscriber->onJobFinish(new JobFinishedEvent($task));

        $this->assertNull(self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Task')->find($taskId));
    }
}
