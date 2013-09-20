<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\Factory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideValidClasses
     */
    public function testWithValidClass($fqn)
    {
        $factory = new Factory($this->createDispatcherMock(), $this->createLoggerMock());
        $this->assertInstanceOf($fqn, $factory->create($fqn));
    }

    public function provideValidClasses()
    {
        return array(
            array('Alchemy\Phrasea\TaskManager\Job\ArchiveJob'),
            array('Alchemy\Phrasea\TaskManager\Job\BridgeJob'),
            array('Alchemy\Phrasea\TaskManager\Job\NullJob'),
        );
    }

    /**
     * @expectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     * @expectedExceptionMessage Class `Alchemy\Phrasea\Application` does not implement JobInterface.
     */
    public function testWithInvalidClass()
    {
        $factory = new Factory($this->createDispatcherMock(), $this->createLoggerMock());
        $factory->create('Alchemy\Phrasea\Application');
    }

    /**
     * @expectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     * @expectedExceptionMessage Job `I\Dont\Know\This\Class` not found.
     */
    public function testWithNonExistentClass()
    {
        $factory = new Factory($this->createDispatcherMock(), $this->createLoggerMock());
        $factory->create('I\Dont\Know\This\Class');
    }

    private function createDispatcherMock()
    {
        return $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    }

    private function createLoggerMock()
    {
        return $this->getMock('Psr\Log\LoggerInterface');
    }
}
