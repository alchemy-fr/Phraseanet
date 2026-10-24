<?php

namespace Alchemy\Functional\TaskManager;

use Alchemy\Test\TaskManager\PhpProcess;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;

class JobTest extends FunctionalTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideVariousMemoryValues')]
    public function testMaxMemory($max, $megPerSeconds, $expectedDuration)
    {
        $script = $this->getNonStoppingScript(1, ' $this->data .= str_repeat("x", '.$megPerSeconds.'*1024*1024);', '$job->addSubscriber(new \Alchemy\TaskManager\Event\JobSubscriber\MemoryLimitSubscriber('.$max.'*1024*1024));');
        $process1 = new PhpProcess($script);

        $start = microtime(true);
        $process1->run();

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.30, abs($expectedDuration-$duration));
    }

    public static function provideVariousMemoryValues()
    {
        return array(
            array(10, 10, 1),
            array(10, 5, 2),
            array(20, 20, 1),
            array(20, 10, 2),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideVariousDurationValues')]
    public function testMaxDuration($max)
    {
        $script = $this->getNonStoppingScript(0.1, '', '$job->addSubscriber(new \Alchemy\TaskManager\Event\JobSubscriber\DurationLimitSubscriber('.$max.'));');
        $process1 = new PhpProcess($script);

        $start = microtime(true);
        $process1->run();

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.2, abs($max-$duration));
    }

    public static function provideVariousDurationValues()
    {
        return array(array(0.3), array(0.5), array(0.7));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideVariousPeriods')]
    public function testPeriodicSignal($periodMilliseconds)
    {
        $script = $this->getNonStoppingScript(0.1, '', '$job->addSubscriber(new \Alchemy\TaskManager\Event\JobSubscriber\SignalControlledSubscriber(\Neutron\SignalHandler\SignalHandler::getInstance(), '.($periodMilliseconds / 1000).'));');

        $process1 = new PhpProcess($script);
        $process1->start();

        $end = microtime(true) + (7 * $periodMilliseconds / 1000);

        while (microtime(true) < $end) {
            usleep((int) ($periodMilliseconds * 1000 * 2 / 3));
            $process1->signal(SIGCONT);
            $this->assertTrue($process1->isRunning());
        }

        usleep((int) ($periodMilliseconds * 1000 * 3 / 2));
        $this->assertFalse($process1->isRunning());
    }

    public static function provideVariousPeriods()
    {
        return array(
            array(150),
            array(450),
        );
    }

    public function testLockingShouldPreventRunningTheSameProcessWIthSameIdTwice()
    {
        $script = $this->getSelfStoppingScript();

        $process1 = new PhpProcess($script);
        $process2 = new PhpProcess($script);

        $process1->start();
        usleep(300000);
        $process2->run();
        $process1->wait();

        $this->assertTrue($process1->isSuccessful());
        $this->assertFalse($process2->isSuccessful());

        $this->assertEquals(0, $process1->getExitCode());
        $this->assertEquals(255, $process2->getExitCode());
    }

    public function testLockFilesAreRemovedOnStop()
    {
        $finder = Finder::create();

        $process1 = new PhpProcess($this->getSelfStoppingScript());
        $process1->start();

        usleep(100000);
        $finder->files()->in($this->lockDir);
        $this->assertCount(1, $finder);

        $process1->wait();

        $this->assertCount(0, $finder);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSignals')]
    public function testLockFilesAreRemovedOnStopSignal($signal)
    {
        $process1 = new PhpProcess($this->getSelfStoppingScript());
        $process1->start();

        usleep(100000);
        $process1->signal($signal);
        $start = microtime(true);
        try {
            $process1->wait();
        } catch (ProcessRuntimeException $e) {

        }
        $this->assertLessThan(0.1, microtime(true) - $start);

        $finder = Finder::create();
        $finder->files()->in($this->lockDir);
        $this->assertCount(0, $finder);
    }

    public static function provideSignals()
    {
        return array(
            array(SIGTERM),
            array(SIGINT),
        );
    }
}
