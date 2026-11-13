<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\JobInterface;
use Alchemy\TaskManager\Job\JobDataInterface;
use Alchemy\Phrasea\TaskManager\Job\Factory;

abstract class JobTestCase extends \PhraseanetTestCase
{
    public function testGetJobIdReturnsAvalidId()
    {
        $dispatcher = $this->createStub('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $logger = $this->createStub('Psr\Log\LoggerInterface');

        $factory = new Factory($dispatcher, $logger, $this->createTranslatorMock());
        $job = $this->getJob();
        $this->assertEquals(get_class($job), get_class($factory->create($job->getJobId())));
    }

    public function testGetSetPauseDuration()
    {
        $job = $this->getJob();
        $this->assertEquals(0.05, $job->getPauseDuration());
        $job->setPauseDuration(24);
        $this->assertEquals(24, $job->getPauseDuration());
    }

    public function testGetEditor()
    {
        $job = $this->getJob();
        $this->assertInstanceof('Alchemy\Phrasea\TaskManager\Editor\EditorInterface', $job->getEditor());
    }

    public function testGetName()
    {
        $job = $this->getJob();
        $this->assertIsString($job->getName());
    }

    public function testGetDescription()
    {
        $job = $this->getJob();
        $this->assertIsString($job->getDescription());
    }

    public function testRunningTheJobWithWrongValueThrowsAnException()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Phraseanet jobs require Phraseanet JobData, got Alchemy\Tests\Phrasea\TaskManager\Job\WrongJobDataTest.');
        $job = $this->getJob();
        $job->run(new WrongJobDataTest());
    }

    /**
     * @return JobInterface
     */
    abstract protected function getJob();
}

class WrongJobDataTest implements JobDataInterface
{
    public function __toString()
    {
        return 'wrong data';
    }
}
