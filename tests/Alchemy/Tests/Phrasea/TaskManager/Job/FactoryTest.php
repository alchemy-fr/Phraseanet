<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\Factory;

class FactoryTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideValidClasses
     */
    public function testWithValidClass($fqn)
    {
        $factory = new Factory($this->createDispatcherMock(), $this->createLoggerMock(), $this->createTranslatorMock());
        $this->assertInstanceOf($fqn, $factory->create($fqn));
    }

    /**
     * @dataProvider provideValidIds
     */
    public function testWithValidId($id, $fqn)
    {
        $factory = new Factory($this->createDispatcherMock(), $this->createLoggerMock(), $this->createTranslatorMock());
        $this->assertInstanceOf($fqn, $factory->create($id));
    }

    public function provideValidClasses()
    {
        return [
            ['Alchemy\Phrasea\TaskManager\Job\ArchiveJob'],
            ['Alchemy\Phrasea\TaskManager\Job\BridgeJob'],
            ['Alchemy\Phrasea\TaskManager\Job\NullJob'],
        ];
    }

    public function provideValidIds()
    {
        return [
            ['Archive', 'Alchemy\Phrasea\TaskManager\Job\ArchiveJob'],
            ['Bridge', 'Alchemy\Phrasea\TaskManager\Job\BridgeJob'],
            ['Null', 'Alchemy\Phrasea\TaskManager\Job\NullJob'],
        ];
    }

    /**
     * @expectedException \Alchemy\Phrasea\Exception\InvalidArgumentException
     * @expectedExceptionMessage Class `Alchemy\Phrasea\Application` does not implement JobInterface.
     */
    public function testWithInvalidClass()
    {
        $factory = new Factory($this->createDispatcherMock(), $this->createLoggerMock(), $this->createTranslatorMock());
        $factory->create('Alchemy\Phrasea\Application');
    }

    /**
     * @expectedException \Alchemy\Phrasea\Exception\InvalidArgumentException
     * @expectedExceptionMessage Job `I\Dont\Know\This\Class` not found.
     */
    public function testWithNonExistentClass()
    {
        $factory = new Factory($this->createDispatcherMock(), $this->createLoggerMock(), $this->createTranslatorMock());
        $factory->create('I\Dont\Know\This\Class');
    }

    private function createDispatcherMock()
    {
        return $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    }
}
