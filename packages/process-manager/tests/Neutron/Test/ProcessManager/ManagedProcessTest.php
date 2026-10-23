<?php

namespace Neutron\Test\ProcessManager;

use Symfony\Component\Process\Process;
use Neutron\ProcessManager\ManagedProcess;

class ManagedProcessTest extends ProcessableTestCase
{
    public function testExecutionsSetters()
    {
        $process = new ManagedProcess($this->createProcessMock(), 3);
        $this->assertEquals(3, $process->getExecutions());

        $process = new ManagedProcess($this->createProcessMock());
        $this->assertEquals(1, $process->getExecutions());
        $process->setExecutions(42);
        $this->assertEquals(42, $process->getExecutions());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRunMethods')]
    public function testExecutionsCanNotBeModifiedOnceTheProcessHasRun($runMethod)
    {
        $this->expectException(\Symfony\Component\Process\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Can not modify executions once the process started. Reset the process before modifying this.');

        $process = new ManagedProcess($this->createProcessMock(), 3);
        call_user_func(array($process, $runMethod));
        $process->setExecutions(3);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRunMethods')]
    public function testExecutionsCanBeModifiedIfTheProcessIsReset($runMethod)
    {
        $process = new ManagedProcess($this->createProcessMock(), 3);
        call_user_func(array($process, $runMethod));
        $process->reset();
        $process->setExecutions(3);
        $this->assertSame(3, $process->getExecutions());
    }

    public static function provideRunMethods()
    {
        return array(array('start'), array('run'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidExecutionsValues')]
    public function testInvalidExecutionsValuesOnConstruct($executions)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Executions must be a positive value.');

        new ManagedProcess($this->createProcessMock(), $executions);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidExecutionsValues')]
    public function testInvalidExecutionsValuesSetter($executions)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Executions must be a positive value.');

        $process = new ManagedProcess($this->createProcessMock());
        $process->setExecutions($executions);
    }

    protected function getOutput($process)
    {
        return $process->getManagedProcess()->getOutput();
    }

    public static function provideInvalidExecutionsValues()
    {
        return array(array(0), array(-4));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRunMethods')]
    public function testRetryIncrementExecutionsAfterARun($runMethod)
    {
        $process = new ManagedProcess($this->createProcessMock(), 4);
        call_user_func(array($process, $runMethod));
        $process->retry();
        $this->assertEquals(4, $process->getExecutions());
    }

    public function testRetryFailsIfProcessDidNotRunYet()
    {
        $this->expectException(\Symfony\Component\Process\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Process must have run at least once to retry.');

        $process = new ManagedProcess($this->createProcessMock());
        $process->retry();
    }

    public function testResetDoReset()
    {
        $process = new ManagedProcess($this->createProcessMock(), 4);
        $process->run();
        $process->incrementFailures();
        $process->reset();

        $this->assertEquals(4, $process->getExecutions());
        $this->assertEquals(0, $process->getFailuresCount());
        $this->assertFalse($process->hasRun());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRunMethods')]
    public function testHasRun($runMethod)
    {
        $process = new ManagedProcess($this->createProcessMock());
        $this->assertFalse($process->hasRun());
        call_user_func(array($process, $runMethod));
        $this->assertTrue($process->hasRun());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRunMethods')]
    public function testCanRun($runMethod)
    {
        $process = new ManagedProcess($this->createProcessMock(), 2);
        $this->assertTrue($process->canRun());
        call_user_func(array($process, $runMethod));
        $this->assertTrue($process->canRun());
        call_user_func(array($process, $runMethod));
        $this->assertFalse($process->canRun());
        $process->retry();
        $this->assertTrue($process->canRun());
        call_user_func(array($process, $runMethod));
        $this->assertFalse($process->canRun());
    }

    public function testFailuresGetterAndSetters()
    {
        $process = new ManagedProcess($this->createProcessMock());
        $this->assertEquals(0, $process->getFailuresCount());
        $process->incrementFailures();
        $this->assertSame(1, $process->getFailuresCount());
        $process->incrementFailures();
        $this->assertSame(2, $process->getFailuresCount());
    }

    public function testManagedProcessGetters()
    {
        $managed1 = $this->createProcessMock();
        $process = new ManagedProcess($managed1);
        $this->assertSame($managed1, $process->getManagedProcess());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRunMethods')]
    public function testRunDecrementsExecutions($runMethod)
    {
        $process = new ManagedProcess($this->createProcessMock(), 3);
        call_user_func(array($process, $runMethod));
        $this->assertEquals(2, $process->getExecutions());
        call_user_func(array($process, $runMethod));
        $this->assertEquals(1, $process->getExecutions());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRunMethods')]
    public function testRunMethodsFailsIfNotRemainingExecutions($runMethod)
    {
        $process = new ManagedProcess($this->createProcessMock());
        call_user_func(array($process, $runMethod));
        $this->expectException(\Symfony\Component\Process\Exception\RuntimeException::class);
        $this->expectExceptionMessage('No remaining executions for the managed process.');
        call_user_func(array($process, $runMethod));
    }

    public function testRunReturnsManagedProcessExitCode()
    {
        $callback = function () {};
        $managed = $this->createProcessMock();
        $managed->expects($this->once())
            ->method('run')
            ->with($callback)
            ->willReturn(42);
        $process = new ManagedProcess($managed);
        $this->assertEquals(42, $process->run($callback));
    }

    public function testStartReturnsTheObject()
    {
        $callback = function () {};
        $managed = $this->createProcessMock();
        $managed->expects($this->once())
            ->method('start')
            ->with($callback);
        $process = new ManagedProcess($managed);
        $this->assertSame($process, $process->start($callback));
    }

    public function testRestartReturnsACloneContainingACloneOfTheManagedProcess()
    {
        $callback = function () {};
        $managed = $this->createProcessMock();
        $cloned = $this->createProcessMock();
        $managed->expects($this->once())
            ->method('restart')
            ->with($callback)
            ->willReturn($cloned);
        $process = new ManagedProcess($managed);
        $retval = $process->restart($callback);
        $this->assertNotSame($retval, $process);
        $this->assertEquals($retval, $process);
        $this->assertEquals($cloned, $retval->getManagedProcess());
    }

    public function testWaitReturnsManagedProcessExitCode()
    {
        $callback = function () {};
        $managed = $this->createProcessMock();
        $managed->expects($this->once())
            ->method('wait')
            ->with($callback)
            ->willReturn(42);
        $process = new ManagedProcess($managed);
        $this->assertEquals(42, $process->wait($callback));
    }

    public function testSignalReturnsManagedProcessItSelf()
    {
        $signal = 13;
        $managed = $this->createProcessMock();
        $managed->expects($this->once())
            ->method('signal')
            ->with($signal);
        $process = new ManagedProcess($managed);
        $this->assertSame($process, $process->signal($signal));
    }

    public function testIsSuccessfulReturnsTheManagedProcessResult()
    {
        $managed = $this->createProcessMock();
        $managed->expects($this->once())
            ->method('isSuccessful')
            ->willReturn(true);
        $process = new ManagedProcess($managed);
        $this->assertTrue($process->isSuccessful());
    }

    public function testIsStartedReturnsTheManagedProcessResult()
    {
        $managed = $this->createProcessMock();
        $managed->expects($this->once())
            ->method('isStarted')
            ->willReturn(true);
        $process = new ManagedProcess($managed);
        $this->assertTrue($process->isStarted());
    }

    public function testIsRunningReturnsTheManagedProcessResult()
    {
        $managed = $this->createProcessMock();
        $managed->expects($this->once())
            ->method('isRunning')
            ->willReturn(true);
        $process = new ManagedProcess($managed);
        $this->assertTrue($process->isRunning());
    }

    public function testIsTerminatedReturnsTheManagedProcessResult()
    {
        $managed = $this->createProcessMock();
        $managed->expects($this->once())
            ->method('isTerminated')
            ->willReturn(true);
        $process = new ManagedProcess($managed);
        $this->assertTrue($process->isTerminated());
    }

    public function testGetStatusReturnsTheManagedProcessResult()
    {
        $managed = $this->createProcessMock();
        $managed->expects($this->once())
            ->method('getStatus')
            ->willReturn(Process::STATUS_STARTED);
        $process = new ManagedProcess($managed);
        $this->assertSame(Process::STATUS_STARTED, $process->getStatus());
    }

    public function testStopReturnsTheExitCode()
    {
        $timeout = 42;
        $signal = 18;
        $managed = $this->createProcessMock();
        $managed->expects($this->once())
            ->method('stop')
            ->with($timeout, $signal)
            ->willReturn(127);
        $process = new ManagedProcess($managed);
        $this->assertSame(127, $process->stop($timeout, $signal));
    }

    public function testCheckTimeoutChecksThetimeout()
    {
        $managed = $this->createProcessMock();
        $managed->expects($this->once())
            ->method('checkTimeout');
        $process = new ManagedProcess($managed);
        $process->checkTimeout();
    }

    public function createProcess($commandline, $cwd = null, ?array $env = null, $stdin = null, $timeout = 60)
    {
        return new ManagedProcess(Process::fromShellCommandline($commandline, $cwd, $env, $stdin, $timeout));
    }
}
