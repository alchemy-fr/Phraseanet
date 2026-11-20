<?php

namespace Alchemy\Test\TaskManager\Event\JobSubscriber;

use Alchemy\TaskManager\Event\JobSubscriber\MemoryLimitSubscriber;
use Alchemy\TaskManager\Event\JobEvent;
use Alchemy\TaskManager\Job\MessageJobData;

class MemoryLimitSubscriberTest extends SubscriberTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidMemoryLimits')]
    public function testWithInvalidLimits($limit)
    {
        $this->expectException(\Alchemy\TaskManager\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum memory should be a positive value.');

        new MemoryLimitSubscriber($limit);
    }

    public static function provideInvalidMemoryLimits()
    {
        return array(array(0), array(-5));
    }

    public function testOnJobTickWithLogger()
    {
        $job = $this->createJobMock();
        $job->expects($this->once())->method('stop');
        $job->expects($this->any())->method('isStarted')->willReturn(true);

        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects($this->once())->method('notice')->with('Max memory reached for romain (1 o.), stopping.');

        $subscriber = new MemoryLimitSubscriber(1, $logger);
        $subscriber->onJobTick(new JobEvent($job, new MessageJobData('romain')));
    }

    public function testOnJobTickWithoutLogger()
    {
        $job = $this->createJobMock();
        $job->expects($this->once())->method('stop');
        $job->expects($this->any())->method('isStarted')->willReturn(true);

        $subscriber = new MemoryLimitSubscriber(1);
        $subscriber->onJobTick(new JobEvent($job, $this->createDataMock()));
    }

    public function testOnJobTickDoesNothingIfJobIsNotStarted()
    {
        $job = $this->createJobMock();
        $job->expects($this->never())->method('stop');
        $job->expects($this->any())->method('isStarted')->willReturn(false);

        $subscriber = new MemoryLimitSubscriber(1);
        $subscriber->onJobTick(new JobEvent($job, $this->createDataMock()));
    }

    public function testOnJobTickWhenMemoryIsQuiteOk()
    {
        $job = $this->createJobMock();
        $job->expects($this->never())->method('stop');
        $job->expects($this->any())->method('isStarted')->willReturn(true);

        $subscriber = new MemoryLimitSubscriber(memory_get_usage() + 1<<20);
        $subscriber->onJobTick(new JobEvent($job, $this->createDataMock()));
    }

    protected function getSubscriber()
    {
        return new MemoryLimitSubscriber();
    }
}
