<?php

namespace Alchemy\Test\TaskManager\Event;

use Alchemy\TaskManager\Event\JobEvent;
use Alchemy\Test\TaskManager\TestCase;

class JobEventTest extends TestCase
{
    public function testJob()
    {
        $job = $this->createJobMock();
        $data = $this->createDataMock();
        $event = new JobEvent($job, $data);
        $this->assertSame($job, $event->getJob());
        $this->assertSame($data, $event->getData());
    }
}
