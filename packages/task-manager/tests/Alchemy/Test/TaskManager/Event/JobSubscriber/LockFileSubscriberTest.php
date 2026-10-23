<?php

namespace Alchemy\Test\TaskManager\Event\JobSubscriber;

use Alchemy\TaskManager\Event\JobSubscriber\LockFileSubscriber;
use Alchemy\TaskManager\Event\JobEvent;
use Symfony\Component\Finder\Finder;

class LockFileSubscriberTest extends SubscriberTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidParams')]
    public function testWithInvalidParams($id, $directory, $message)
    {
        $this->expectException(\Alchemy\TaskManager\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        new LockFileSubscriber($id, null, $directory);
    }

    public static function provideInvalidParams()
    {
        return array(
            array('', __DIR__, 'The id can not be empty.'),
            array(null, __DIR__, 'The id can not be empty.'),
            array('id', '/path/to/no/dir', '/path/to/no/dir does not seem to be a directory.')
        );
    }

    public function testCreatesFileOnStartup()
    {
        $lockDir = __DIR__ . '/LockDir';

        $job = $this->createJobMock();

        $subscriber = $this->getSubscriber();
        $finder = Finder::create();
        $this->assertCount(0, $finder->files()->in($lockDir));
        $subscriber->onJobStart(new JobEvent($job, $this->createDataMock()));
        $finder = Finder::create();
        $this->assertCount(1, $finder->files()->in($lockDir));
        $subscriber->onJobStop(new JobEvent($job, $this->createDataMock()));
        $this->assertCount(0, $finder->files()->in($lockDir));
    }

    protected function getSubscriber()
    {
        $lockDir = __DIR__ . '/LockDir';
        if (!is_dir($lockDir)) {
            mkdir($lockDir);
        }

        $finder = Finder::create();

        $finder->files()->in($lockDir);

        foreach ($finder as $file) {
            unlink($file->getPathname());
        }

        return new LockFileSubscriber('id', null, $lockDir);
    }
}
