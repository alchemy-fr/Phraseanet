<?php

namespace Alchemy\Test\TaskManager;

use Alchemy\TaskManager\ZMQSocket;
use Alchemy\TaskManager\TaskInterface;
use Alchemy\TaskManager\TaskListInterface;
use Alchemy\TaskManager\TaskManager;
use Alchemy\TaskManager\Event\TaskManagerEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TaskManagerTest extends TestCase
{
    public function testThatItRunsWithoutAnyProcesses()
    {
        $taskList = $this->createMock(\Alchemy\TaskManager\TaskListInterface::class);
        $taskList->expects($this->once())
            ->method('refresh')
            ->willReturn(array());
        $manager = TaskManager::create(new EventDispatcher(), $this->createLoggerMock(), $taskList);
        pcntl_async_signals(true);
        pcntl_alarm(1);
        pcntl_signal(SIGALRM, function () use ($manager) { $manager->stop(); });
        $start = microtime(true);
        $manager->start();
        $this->assertGreaterThanOrEqual(1, microtime(true) - $start);
    }

    public function testThatItRunsProcessesThenStop()
    {
        $testfile = __DIR__ . '/testfile';
        if (is_file($testfile)) {
            unlink($testfile);
        }
        touch($testfile);

        $serverScript = '<?php
            require "'.__DIR__.'/../../../../tests/bootstrap.php";
            '
            .$this->getTaskImplementation().$this->getTaskListImplementation()
            .'
            use Alchemy\TaskManager\TaskManager;
            use Alchemy\TaskManager\ZMQSocket;
            use Alchemy\Test\TaskManager\PhpProcess;
            use Symfony\Component\EventDispatcher\EventDispatcher;

            $taskList = new TaskList(array(new Task("task 1", new PhpProcess("<?php file_put_contents(\''.$testfile.'\', \'hello\n\', FILE_APPEND);"), 3)));
            $logger = new \Monolog\Logger("test");
            $logger->pushHandler(new \Monolog\Handler\StreamHandler("php://stdout"));
            $manager = TaskManager::create(new EventDispatcher(), $logger, $taskList);
            $manager->start();
        ';

        $process = new PhpProcess($serverScript);
        $process->start();
        usleep(TaskManager::DEFAULT_TICK_PERIOD * 1E6 * 4);
        $process->stop();
        $data = file_get_contents($testfile);
        unlink($testfile);
        $this->assertEquals("hello\nhello\nhello\n", $data);
    }

    public function testThatItRespondstoPingCommand()
    {
        $serverScript = '<?php
            require "'.__DIR__.'/../../../../tests/bootstrap.php";
            '
            .$this->getTaskImplementation().$this->getTaskListImplementation()
            .'
            use Alchemy\TaskManager\TaskManager;
            use Alchemy\TaskManager\ZMQSocket;
            use Symfony\Component\EventDispatcher\EventDispatcher;

            $taskList = new TaskList(array());
            $logger = new \Monolog\Logger("test");
            $logger->pushHandler(new \Monolog\Handler\StreamHandler("php://stdout"));
            $manager = TaskManager::create(new EventDispatcher(), $logger, $taskList);
            $manager->start();
        ';

        $server = new PhpProcess($serverScript);
        $server->start();

        $process = new PhpProcess('<?php
            require "'.__DIR__.'/../../../../tests/bootstrap.php";
            use Alchemy\TaskManager\TaskManager;

            $client = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_REQ);
            $client->connect("tcp://127.0.0.1:6660");
            $client->send(TaskManager::MESSAGE_PING);
            $message = $client->recv();
            echo $message;
        ');
        $process->run();
        $server->stop();
        $this->assertEquals('{"request":"PING","reply":"PONG"}', $process->getOutput());
    }

    public function testThatItRespondstoStateCommandWithStatus()
    {
        $serverScript = '<?php
            require "'.__DIR__.'/../../../../tests/bootstrap.php";
            '
            .$this->getTaskImplementation().$this->getTaskListImplementation()
            .'
            use Alchemy\TaskManager\TaskManager;
            use Alchemy\TaskManager\ZMQSocket;
            use Symfony\Component\EventDispatcher\EventDispatcher;

            $taskList = new TaskList(array());
            $logger = new \Monolog\Logger("test");
            $logger->pushHandler(new \Monolog\Handler\StreamHandler("php://stdout"));
            $manager = TaskManager::create(new EventDispatcher(), $logger, $taskList);
            $manager->start();
        ';

        $server = new PhpProcess($serverScript);
        $server->start();

        $process = new PhpProcess('<?php
            require "'.__DIR__.'/../../../../tests/bootstrap.php";
            use Alchemy\TaskManager\TaskManager;

            $client = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_REQ);
            $client->connect("tcp://127.0.0.1:6660");
            $client->send(TaskManager::MESSAGE_STATE);
            $message = $client->recv();
            echo $message;
        ');
        $process->run();
        $server->stop();
        $this->assertMatchesRegularExpression('/\{"request":"STATE","reply":\{"manager":\{"process-id":\d+\},"jobs":\[\]\}\}/', $process->getOutput());
    }

    public function testMultipleStartsAndStops()
    {
        $serverScript = '<?php
            require "'.__DIR__.'/../../../../tests/bootstrap.php";
            '
            .$this->getTaskImplementation().$this->getTaskListImplementation()
            .'
            use Alchemy\TaskManager\TaskManager;
            use Symfony\Component\EventDispatcher\EventDispatcher;

            $taskList = new TaskList(array());
            $logger = new \Monolog\Logger("test");
            $logger->pushHandler(new \Monolog\Handler\StreamHandler("php://stdout"));
            $manager = TaskManager::create(new EventDispatcher(), $logger, $taskList);
            $manager->start();
        ';

        $server = new PhpProcess($serverScript);
        $this->assertFalse($server->isRunning());
        $server->start();
        $this->assertTrue($server->isRunning());
        $server->stop();
        $this->assertFalse($server->isRunning());
        $server->start();
        $this->assertTrue($server->isRunning());
        $server->stop();
        $this->assertFalse($server->isRunning());
    }

    public function testThatTwoTaskManagerCanNotRunOnSamePortAndHostAtTheSameTime()
    {
        $socket = new ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_REP, 'tcp', '127.0.0.1', 6660);
        $socket->bind();

        $taskList = $this->createMock(\Alchemy\TaskManager\TaskListInterface::class);
        $manager = TaskManager::create(new EventDispatcher(), $this->createLoggerMock(), $taskList);
        $this->expectException(\Alchemy\TaskManager\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Unable to bind socket to tcp://127.0.0.1:6660. Is another one already bound ?');
        $manager->start();
    }

    public function testASIGCONTIsRegularlyReceivedByPrograms()
    {
        $testfile = __DIR__ . '/testfile';
        if (is_file($testfile)) {
            unlink($testfile);
        }
        touch($testfile);

        $serverScript = '<?php
            require "'.__DIR__.'/../../../../tests/bootstrap.php";
            '
            .$this->getTaskImplementation().$this->getTaskListImplementation()
            .'
            use Alchemy\TaskManager\TaskManager;
            use Alchemy\Test\TaskManager\PhpProcess;
            use Symfony\Component\EventDispatcher\EventDispatcher;

            $taskList = new TaskList(array(new Task("task 1", new PhpProcess("<?php declare(ticks=1);pcntl_signal(SIGCONT, function () {file_put_contents(\"'.$testfile.'\", \"hello\n\", FILE_APPEND);}); \$n=0; while (\$n<3) { usleep('.(TaskManager::DEFAULT_TICK_PERIOD * 1E6).');\$n++;} "), 2)));
            $logger = new \Monolog\Logger("test");
            $logger->pushHandler(new \Monolog\Handler\StreamHandler("php://stdout"));
            $manager = TaskManager::create(new EventDispatcher(), $logger, $taskList);
            $manager->start();
        ';

        $process = new PhpProcess($serverScript);
        $process->start();
        usleep(TaskManager::DEFAULT_TICK_PERIOD * 1E6 * 7);
        $process->stop();
        $data = file_get_contents($testfile);
        unlink($testfile);
        $this->assertStringContainsString("hello\nhello\nhello\nhello\n", $data);
    }

    public function testThatCallingStopTriggersAStopRequestEvent()
    {
        $taskList = $this->createMock(\Alchemy\TaskManager\TaskListInterface::class);
        $taskList->expects($this->any())
            ->method('refresh')
            ->willReturn(array());
        $manager = TaskManager::create(new EventDispatcher(), $this->createLoggerMock(), $taskList);
        $boolean = false;
        $manager->addListener(TaskManagerEvents::STOP_REQUEST, function () use (&$boolean) {
            $boolean = true;
        });
        pcntl_async_signals(true);
        pcntl_alarm(2);
        pcntl_signal(SIGALRM, function () use ($manager) { $manager->stop(); });

        $process = new PhpProcess('<?php
            require "'.__DIR__.'/../../../../tests/bootstrap.php";
            use Alchemy\TaskManager\TaskManager;

            usleep(100000);
            $client = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_REQ);
            $client->connect("tcp://127.0.0.1:6660");
            $client->send(TaskManager::MESSAGE_STOP);
            $message = $client->recv();
        ');
        $process->start();
        $this->assertFalse($boolean);
        $manager->start();
        pcntl_alarm(0);
        $manager->stop();
        $process->wait();
        $this->assertTrue($boolean);
    }

    public function testThatRefreshIsCalledAsManyTimestheUpdateIsRequested()
    {
        $taskList = $this->createMock(\Alchemy\TaskManager\TaskListInterface::class);
        $taskList->expects($this->exactly(7))
            ->method('refresh')
            ->willReturn(array());
        $manager = TaskManager::create(new EventDispatcher(), $this->createLoggerMock(), $taskList);
        pcntl_async_signals(true);
        pcntl_alarm(1);
        pcntl_signal(SIGALRM, function () use ($manager) { $manager->stop(); });
        $start = microtime(true);

        $process = new PhpProcess('<?php
            require "'.__DIR__.'/../../../../tests/bootstrap.php";
            use Alchemy\TaskManager\TaskManager;

            usleep(100000);
            $client = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_REQ);
            $client->connect("tcp://127.0.0.1:6660");
            $client->send(TaskManager::MESSAGE_PROCESS_UPDATE);
            $message = $client->recv();
            $client->send(TaskManager::MESSAGE_PROCESS_UPDATE);
            $message = $client->recv();
            $client->send(TaskManager::MESSAGE_PROCESS_UPDATE);
            $message = $client->recv();
            $client->send(TaskManager::MESSAGE_PROCESS_UPDATE);
            $message = $client->recv();
            $client->send(TaskManager::MESSAGE_PROCESS_UPDATE);
            $message = $client->recv();
            $client->send(TaskManager::MESSAGE_PROCESS_UPDATE);
            $message = $client->recv();
        ');
        $process->start();
        $manager->start();
        $this->assertGreaterThanOrEqual(1, microtime(true) - $start);
    }

    private function createLoggerMock()
    {
        return $this->createMock(\Psr\Log\LoggerInterface::class);
    }

    private function getTaskListImplementation()
    {
        return '

use Alchemy\TaskManager\TaskListInterface;

class TaskList implements TaskListInterface
{
    private $tasks;

    public function __construct(array $tasks)
    {
        $this->tasks = $tasks;
    }

    public function refresh()
    {
        return $this->tasks;
    }
}';
    }

    private function getTaskImplementation()
    {
        return '
use Alchemy\TaskManager\TaskInterface;

class Task implements TaskInterface
{
    private $name;
    private $process;
    private $iterations;

    public function __construct($name, $process, $iterations)
    {
        $this->name = $name;
        $this->process = $process;
        $this->iterations = $iterations;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getIterations()
    {
        return $this->iterations;
    }

    public function createProcess()
    {
        return clone $this->process;
    }
}
';
    }
}

class TaskList implements TaskListInterface
{
    private $tasks;

    public function __construct(array $tasks)
    {
        $this->tasks = $tasks;
    }

    public function refresh()
    {
        return $this->tasks;
    }
}

class Task implements TaskInterface
{
    private $name;
    private $process;
    private $iterations;

    public function __construct($name, $process, $iterations)
    {
        $this->name = $name;
        $this->process = $process;
        $this->iterations = $iterations;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getIterations()
    {
        return $this->iterations;
    }

    public function createProcess()
    {
        return clone $this->process;
    }
}
