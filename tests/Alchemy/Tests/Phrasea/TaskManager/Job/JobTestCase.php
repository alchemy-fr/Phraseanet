<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\JobInterface;
use Alchemy\TaskManager\JobDataInterface;

abstract class JobTestCase extends \PhraseanetPHPUnitAbstract
{
    public function testGetClassnameReturnsTheClassname()
    {
        $job = $this->getJob();
        $this->assertSame(get_class($job), $job->getClassname());
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
        $this->assertInternalType('string', $job->getName());
    }

    public function testGetDescription()
    {
        $job = $this->getJob();
        $this->assertInternalType('string', $job->getDescription());
    }

    /**
     * @expectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     * @expectedExceptionMessage JobData must be passed to a JobInterface::Run command.
     */
    public function testRunningTheJobWithWrongValueThrowsAnException()
    {
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
}
