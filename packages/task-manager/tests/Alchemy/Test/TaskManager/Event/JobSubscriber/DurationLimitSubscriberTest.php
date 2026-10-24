<?php

namespace Alchemy\Test\TaskManager\Event\JobSubscriber;

use Alchemy\TaskManager\Event\JobSubscriber\DurationLimitSubscriber;
use Alchemy\TaskManager\Event\JobEvent;
use Alchemy\TaskManager\Job\MessageJobData;

class DurationLimitSubscriberTest extends SubscriberTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidDurationLimits')]
    public function testWithInvalidLimits($limit)
    {
        $this->expectException(\Alchemy\TaskManager\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum duration should be a positive value.');

        new DurationLimitSubscriber($limit);
    }

    public static function provideInvalidDurationLimits()
    {
        return array(array(0), array(-5));
    }

    public function testDoesNothingIfNotStarted()
    {
        $job = $this->createJobMock();
        $job->expects($this->never())->method('stop');
        $job->expects($this->any())->method('isStarted')->willReturn(true);

        $subscriber = new DurationLimitSubscriber(0.1);
        $subscriber->onJobTick(new JobEvent($job, $this->createDataMock()));
    }

    public function testWithLogger()
    {
        $job = $this->createJobMock();
        $job->expects($this->once())->method('stop');
        $job->expects($this->any())->method('isStarted')->willReturn(true);

        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects($this->once())->method('notice')->with('Max duration reached for romain (0.1 s.), stopping.');

        $subscriber = new DurationLimitSubscriber(0.1, $logger);
        $subscriber->onJobStart(new JobEvent($job, $this->createDataMock()));
        usleep(100000);
        $subscriber->onJobTick(new JobEvent($job, new MessageJobData('romain')));
    }

    public function testOnJobTickWithoutLogger()
    {
        $job = $this->createJobMock();
        $job->expects($this->once())->method('stop');
        $job->expects($this->any())->method('isStarted')->willReturn(true);

        $subscriber = new DurationLimitSubscriber(0.1);
        $subscriber->onJobStart(new JobEvent($job, $this->createDataMock()));
        usleep(100000);
        $subscriber->onJobTick(new JobEvent($job, $this->createDataMock()));
    }

    public function testOnJobTickDoesNothingIfJobIsNotStarted()
    {
        $job = $this->createJobMock();
        $job->expects($this->never())->method('stop');
        $job->expects($this->any())->method('isStarted')->willReturn(false);

        $subscriber = new DurationLimitSubscriber(0.1);
        $subscriber->onJobStart(new JobEvent($job, $this->createDataMock()));
        usleep(100000);
        $subscriber->onJobTick(new JobEvent($job, $this->createDataMock()));
    }

    public function testOnJobTickWhenMemoryIsQuiteOk()
    {
        $job = $this->createJobMock();
        $job->expects($this->never())->method('stop');
        $job->expects($this->any())->method('isStarted')->willReturn(false);

        $subscriber = new DurationLimitSubscriber(0.1);
        $subscriber->onJobStart(new JobEvent($job, $this->createDataMock()));
        usleep(50000);
        $subscriber->onJobTick(new JobEvent($job, $this->createDataMock()));
    }

    protected function getSubscriber()
    {
        return new DurationLimitSubscriber();
    }
}
