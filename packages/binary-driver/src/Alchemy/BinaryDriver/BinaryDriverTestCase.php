<?php

namespace Alchemy\BinaryDriver;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Convenient PHPUnit methods for testing BinaryDriverInterface implementations.
 */
abstract class BinaryDriverTestCase extends TestCase
{
    /**
     * @return ProcessBuilderFactoryInterface&MockObject
     */
    public function createProcessBuilderFactoryMock()
    {
        return $this->createMock(ProcessBuilderFactoryInterface::class);
    }

    /**
     * @param integer $runs        The number of runs expected
     * @param Boolean $success     True if the process expects to be successfull
     * @param string  $commandLine The commandline executed
     * @param string  $output      The process output
     * @param string  $error       The process error output
     *
     * @return Process&MockObject
     */
    public function createProcessMock($runs = 1, $success = true, $commandLine = null, $output = null, $error = null, $callback = false)
    {
        $process = $this->createMock(Process::class);

        $builder = $process->expects($this->exactly($runs))
            ->method('run');

        if (true === $callback) {
            $builder->with($this->isInstanceOf('Closure'));
        }

        $process->expects($this->any())
            ->method('isSuccessful')
            ->willReturn($success);

        foreach (array(
            'getOutput' => (string) $output,
            'getErrorOutput' => (string) $error,
            'getCommandLine' => (string) $commandLine,
        ) as $command => $value) {
            $process
                ->expects($this->any())
                ->method($command)
                ->willReturn($value);
        }

        return $process;
    }

    /**
     * @return LoggerInterface&MockObject
     */
    public function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return ConfigurationInterface&MockObject
     */
    public function createConfigurationMock()
    {
        return $this->createMock(ConfigurationInterface::class);
    }
}
