<?php

namespace Alchemy\Test\TaskManager\Event\JobSubscriber;

use Alchemy\TaskManager\Event\JobSubscriber\StopSignalSubscriber;
use Alchemy\TaskManager\Event\JobEvent;
use Neutron\SignalHandler\SignalHandler;

class StopSignalSubscriberTest extends SubscriberTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideHandledSignals')]
    public function testSignalWithLogger($signal)
    {
        $job = $this->createJobMock();
        $job->expects($this->once())->method('stop');
        $job->expects($this->any())->method('isStarted')->willReturn(true);

        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects($this->once())->method('notice');

        $subscriber = new StopSignalSubscriber(SignalHandler::getInstance(), $logger);
        $subscriber->onJobStart(new JobEvent($job, $this->createDataMock()));
        declare(ticks=1);
        posix_kill(getmypid(), $signal);
        // required as the job is a mock, not running.
        // the handler must be removed after the signal (normally done by event).
        $subscriber->onJobStop(new JobEvent($job, $this->createDataMock()));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideHandledSignals')]
    public function testSignalWithoutLogger($signal)
    {
        $job = $this->createJobMock();
        $job->expects($this->once())->method('stop');
        $job->expects($this->any())->method('isStarted')->willReturn(true);

        $subscriber = new StopSignalSubscriber(SignalHandler::getInstance());
        $subscriber->onJobStart(new JobEvent($job, $this->createDataMock()));
        declare(ticks=1);
        posix_kill(getmypid(), $signal);
        // required as the job is a mock, not running.
        // the handler must be removed after the signal (normally done by event).
        $subscriber->onJobStop(new JobEvent($job, $this->createDataMock()));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideHandledSignals')]
    public function testOnJobTickDoesNothingIfJobIsNotStarted($signal)
    {
        $job = $this->createJobMock();
        $job->expects($this->never())->method('stop');
        $job->expects($this->any())->method('isStarted')->willReturn(false);

        $subscriber = new StopSignalSubscriber(SignalHandler::getInstance());
        $subscriber->onJobStart(new JobEvent($job, $this->createDataMock()));
        declare(ticks=1);
        posix_kill(getmypid(), $signal);
        // required as the job is a mock, not running.
        // the handler must be removed after the signal (normally done by event).
        $subscriber->onJobStop(new JobEvent($job, $this->createDataMock()));
    }

    public static function provideHandledSignals()
    {
        return array(array(SIGINT), array(SIGTERM));
    }

    protected function getSubscriber()
    {
        return new StopSignalSubscriber(SignalHandler::getInstance());
    }
}
