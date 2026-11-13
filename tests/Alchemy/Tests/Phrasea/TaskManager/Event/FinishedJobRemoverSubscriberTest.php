<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Event;

use Alchemy\Phrasea\TaskManager\Event\FinishedJobRemoverSubscriber;
use Alchemy\Phrasea\TaskManager\Event\JobFinishedEvent;
use Alchemy\Phrasea\Model\Entities\Task;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FinishedJobRemoverSubscriberTest extends \PhraseanetTestCase
{
    public function testOnJobFinish()
    {
        $task = self::$DI['app']['orm.em']->find('Alchemy\Phrasea\Model\Entities\Task', 1);
        $taskId = $task->getId();

        $subscriber = new FinishedJobRemoverSubscriber(self::$DI['app']['orm.em']);
        $subscriber->onJobFinish(new JobFinishedEvent($task));

        $this->assertNull(self::$DI['app']['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\Task')->find($taskId));
    }
}
