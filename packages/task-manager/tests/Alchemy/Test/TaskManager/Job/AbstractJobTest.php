<?php

namespace Alchemy\Test\TaskManager\Job;

use Alchemy\TaskManager\Job\AbstractJob;
use Alchemy\TaskManager\Job\JobDataInterface;
use Alchemy\TaskManager\Event\JobEvents;
use Alchemy\TaskManager\Event\JobSubscriber\DurationLimitSubscriber;
use Alchemy\Test\TaskManager\PhpProcess;
use Alchemy\Test\TaskManager\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractJobTest extends TestCase
{
    private $lockDir;

    protected function setUp(): void
    {
        $this->lockDir = __DIR__ . '/LockDir';
        if (!is_dir($this->lockDir)) {
            mkdir($this->lockDir);
        }

        $finder = Finder::create();

        $finder->files()->in($this->lockDir);

        foreach ($finder as $file) {
            unlink($file->getPathname());
        }
    }

    private function getPauseScript()
    {
        return $this->createScript('', array(), '', '2');
    }

    public function testCustomEventsAreWelcomed()
    {
        $saidCoucou = false;
        $job = new JobTestWithCustomEvent();
        $job->addListener('coucou', function () use (&$saidCoucou) { $saidCoucou = true; });
        $job->singleRun();
        $this->assertTrue($saidCoucou);
    }

    public function testStopWithinAPauseDoesNotWaitTheEndOfThePause()
    {
        $script = $this->getPauseScript();
        $process = new PhpProcess($script);

        $process->start();
        usleep(500000);
        $start = microtime(true);
        $process->stop();
        $this->assertFalse($process->isRunning());
        $this->assertLessThan(0.1, microtime(true) - $start);
    }

    public function testStopDispatchAnEventOnFirstCall()
    {
        $job = new JobTest();
        $counter = 0;
        $job->addListener(JobEvents::STOP_REQUEST, function () use (&$counter) { $counter++; });
        $job->stop();
        $this->assertEquals(0, $counter);
        $job->addSubscriber(new DurationLimitSubscriber(0.1));
        $job->run();
        $this->assertEquals(1, $counter);
    }

    private function getPauseAndLoopScript()
    {
        return $this->createScript('echo "loop\\n";' . PHP_EOL);
    }

    private function getEventsScript($throwException)
    {
        $body = $throwException ? 'throw new \Exception("failure");' . PHP_EOL : '';
        $additionalUses = array(
            'Alchemy\TaskManager\Event\JobSubscriber\StopSignalSubscriber',
            'Alchemy\TaskManager\Event\JobEvents',
            'Neutron\SignalHandler\SignalHandler',
        );

        $jobInitializer = <<<EOS
\$job->addSubscriber(new StopSignalSubscriber(SignalHandler::getInstance()));
\$job->addListener(JobEvents::START, function () { echo "job-start\\n"; });
\$job->addListener(JobEvents::TICK, function () { echo "job-tick\\n"; });
\$job->addListener(JobEvents::STOP, function () { echo "job-stop\\n"; });
\$job->addListener(JobEvents::EXCEPTION, function () { echo "job-exception\\n"; });

EOS;

        return $this->createScript($body, $additionalUses, $jobInitializer);
    }

    public function testPauseDoesAPause()
    {
        $script = $this->getPauseAndLoopScript();
        $process = new PhpProcess($script);

        $process->start();
        usleep(550000);
        $process->stop();
        $this->assertFalse($process->isRunning());
        $loops = count(explode("loop\n", $process->getOutput()));
        $this->assertGreaterThanOrEqual(6, $loops);
        $this->assertLessThanOrEqual(7, $loops);
    }

    public function testEvents()
    {
        $script = $this->getEventsScript(false);
        $process = new PhpProcess($script);

        $process->start();
        usleep(550000);
        $process->stop();
        $this->assertFalse($process->isRunning());
        $data = array_filter(explode("\n", $process->getOutput()));
        $this->assertGreaterThanOrEqual(3, count($data), 'Expected more events');
        $this->assertSame(JobEvents::START, $data[0]);
        $this->assertSame(JobEvents::TICK, $data[1]);
        $this->assertContains(JobEvents::STOP, $data);
    }

    public function testEventsWithException()
    {
        $script = $this->getEventsScript(true);
        $process = new PhpProcess($script);

        $process->start();
        usleep(550000);
        $process->stop();
        $this->assertFalse($process->isRunning());
        $data = array_filter(explode("\n", $process->getOutput()));
        $this->assertSame(JobEvents::START, $data[0]);
        $this->assertSame(JobEvents::TICK, $data[1]);
        $this->assertContains(JobEvents::EXCEPTION, $data);
    }

    public function testLoggerGettersAndSetters()
    {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        $job = new JobTest();
        $this->assertSame(null, $job->getLogger());
        $job->setLogger($logger);
        $this->assertSame($logger, $job->getLogger());
    }

    public function testDataIsPassedToDoRun()
    {
        $data = $this->createDataMock();

        $job = new JobTest();
        $job->addSubscriber(new DurationLimitSubscriber(0.2));
        $job->run($data);

        $this->assertSame($data, $job->getData());
    }

    public function testSingleRunRunsAndStop()
    {
        $data = $this->createDataMock();

        $job = new JobTest();
        $start = microtime(true);
        $this->assertSame($job, $job->singleRun($data));

        $this->assertLessThan(0.1, microtime(true) - $start);
        $this->assertSame($data, $job->getData());
    }

    public function testAddAListener()
    {
        $dispatcher = $this->createMock(\Symfony\Component\EventDispatcher\EventDispatcherInterface::class);
        $listener = array($this, 'testAddAListener');
        $name = 'event-name';

        $dispatcher->expects($this->once())
                ->method('addListener')
                ->with($name, $listener);

        $job = new JobTest($dispatcher);
        $this->assertSame($job, $job->addListener($name, $listener));
    }

    public function testAddASubscriber()
    {
        $dispatcher = $this->createMock(\Symfony\Component\EventDispatcher\EventDispatcherInterface::class);
        $subscriber = $this->createMock(\Symfony\Component\EventDispatcher\EventSubscriberInterface::class);

        $dispatcher->expects($this->once())
                ->method('addSubscriber')
                ->with($subscriber);

        $job = new JobTest($dispatcher);
        $this->assertSame($job, $job->addSubscriber($subscriber));
    }

    public function testEventsAreDispatchedOnSingleRun()
    {
        $data = $this->createDataMock();

        $job = new JobTest();
        $collector = array();
        $job->addListener(JobEvents::START, function () use (&$collector) {
            $collector[] = JobEvents::START;
        });
        $job->addListener(JobEvents::TICK, function () use (&$collector) {
            $collector[] = JobEvents::TICK;
        });
        $job->addListener(JobEvents::STOP, function () use (&$collector) {
            $collector[] = JobEvents::STOP;
        });

        $this->assertSame($job, $job->singleRun($data));
        $this->assertSame(JobEvents::START, $collector[0]);
        $this->assertSame(JobEvents::TICK, $collector[1]);
        $this->assertContains(JobEvents::STOP, $collector);
    }

    public function testEventsWithExceptionAreDispatchedOnSingleRun()
    {
        $data = $this->createDataMock();

        $job = new JobFailureTest();
        $collector = array();
        $job->addListener(JobEvents::START, function () use (&$collector) {
            $collector[] = JobEvents::START;
        });
        $job->addListener(JobEvents::TICK, function () use (&$collector) {
            $collector[] = JobEvents::TICK;
        });
        $job->addListener(JobEvents::EXCEPTION, function ($event) use (&$collector) {
            $collector[] = JobEvents::EXCEPTION;
        });
        $job->addListener(JobEvents::STOP, function () use (&$collector) {
            $collector[] = JobEvents::STOP;
        });

        try {
            $this->assertSame($job, $job->singleRun($data));
            $this->fail('A job failure exception should have been raised.');
        } catch (JobFailureException $e) {

        }
        $this->assertSame(JobEvents::START, $collector[0]);
        $this->assertSame(JobEvents::TICK, $collector[1]);
        $this->assertContains(JobEvents::EXCEPTION, $collector);
    }

    public function testDoRunWithoutdataIsOk()
    {
        $job = new JobTest();
        $job->addSubscriber(new DurationLimitSubscriber(0.1));
        $this->assertSame($job, $job->run());
    }

    public function testJobCanBeRestartedAfterAFailure()
    {
        $job = new JobFailureTest();
        try {
            $job->run();
            $this->fail('A JobFailureException should have been raised');
        } catch (JobFailureException $e) {

        }
        $job = new JobFailureTest();
        $this->expectException(\Alchemy\Test\TaskManager\Job\JobFailureException::class);
        $this->expectExceptionMessage('Total failure.');
        $job->run();
    }

    /**
     * @param string $doRunBody
     * @param string[] $additionalUses
     * @param string $jobInitializer
     * @param string $pauseDuration
     * @return string
     */
    private function createScript($doRunBody = '', array $additionalUses = array(), $jobInitializer = '', $pauseDuration = '0.1')
    {
        $path = realpath(__DIR__ . '/../../../../../vendor/autoload.php');

        $createUseClosure = function ($useClause) {
            return sprintf("use %s;\n", $useClause);
        };

        $additionalUses[] = 'Alchemy\TaskManager\Job\JobDataInterface';
        $additionalUses = array_unique($additionalUses);
        sort($additionalUses);

        $useClauses = implode('', array_map($createUseClosure, $additionalUses));

        return <<<EOS
<?php

declare(ticks=1);

require "$path";

$useClauses
class Job extends Alchemy\TaskManager\Job\AbstractJob
{
    protected function doRun(JobDataInterface \$data)
    {
$doRunBody
    }

    protected function getPauseDuration()
    {
        return $pauseDuration;
    }
}

\$job = new Job();
$jobInitializer
\$job->run();

EOS;
    }
}

class JobTest extends AbstractJob
{
    private $data;

    public function getData()
    {
        return $this->data;
    }

    protected function doRun(JobDataInterface $data)
    {
        $this->data = $data;
    }
}

class JobTestWithCustomEvent extends AbstractJob
{
    protected function doRun(JobDataInterface $data)
    {
        $this->dispatcher->dispatch(new Event(), 'coucou');
    }
}

class JobFailureTest extends AbstractJob
{
    protected function doRun(JobDataInterface $data)
    {
        throw new JobFailureException('Total failure.');
    }
}

class JobFailureException extends \Exception
{
}
