<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\Factory;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FactoryTest extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideValidClasses')]
    public function testWithValidClass($fqn)
    {
        $factory = new Factory($this->createDispatcherMock(), $this->createLoggerMock(), $this->createTranslatorMock());
        $this->assertInstanceOf($fqn, $factory->create($fqn));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideValidIds')]
    public function testWithValidId($id, $fqn)
    {
        $factory = new Factory($this->createDispatcherMock(), $this->createLoggerMock(), $this->createTranslatorMock());
        $this->assertInstanceOf($fqn, $factory->create($id));
    }

    public static function provideValidClasses()
    {
        return [
            ['Alchemy\Phrasea\TaskManager\Job\ArchiveJob'],
            ['Alchemy\Phrasea\TaskManager\Job\BridgeJob'],
            ['Alchemy\Phrasea\TaskManager\Job\NullJob'],
        ];
    }

    public static function provideValidIds()
    {
        return [
            ['Archive', 'Alchemy\Phrasea\TaskManager\Job\ArchiveJob'],
            ['Bridge', 'Alchemy\Phrasea\TaskManager\Job\BridgeJob'],
            ['Null', 'Alchemy\Phrasea\TaskManager\Job\NullJob'],
        ];
    }

    public function testWithInvalidClass()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class `Alchemy\Phrasea\Application` does not implement JobInterface.');
        $factory = new Factory($this->createDispatcherMock(), $this->createLoggerMock(), $this->createTranslatorMock());
        $factory->create('Alchemy\Phrasea\Application');
    }

    public function testWithNonExistentClass()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Job `I\Dont\Know\This\Class` not found.');
        $factory = new Factory($this->createDispatcherMock(), $this->createLoggerMock(), $this->createTranslatorMock());
        $factory->create('I\Dont\Know\This\Class');
    }

    private function createDispatcherMock()
    {
        return $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    }
}
