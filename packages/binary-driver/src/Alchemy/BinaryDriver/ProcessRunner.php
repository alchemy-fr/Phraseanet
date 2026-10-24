<?php

/*
 * This file is part of Alchemy\BinaryDriver.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\BinaryDriver;

use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Psr\Log\LoggerInterface;
use SplObjectStorage;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class ProcessRunner implements ProcessRunnerInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $name;

    public function __construct(LoggerInterface $logger, $name)
    {
        $this->logger = $logger;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * {@inheritdoc}
     */
    public function run(Process $process, SplObjectStorage $listeners, $bypassErrors)
    {
        $this->logger->info(sprintf(
            '%s running command %s', $this->name, $process->getCommandLine()
        ));

        try {
            $process->run($this->buildCallback($listeners));
        } catch (RuntimeException $e) {
            if (!$bypassErrors) {
                $this->doExecutionFailure($process->getCommandLine(), $this->getErrorOutput($process), $e);
            }
        }

        if (!$bypassErrors && !$process->isSuccessful()) {
            $this->doExecutionFailure($process->getCommandLine(), $this->getErrorOutput($process));
        } elseif (!$process->isSuccessful()) {
            $this->logger->error($this->createErrorMessage($process->getCommandLine(), $this->getErrorOutput($process)));

            return;
        } else {
            $this->logger->info(sprintf('%s executed command successfully', $this->name));

            return $process->getOutput();
        }
    }

    private function buildCallback(SplObjectStorage $listeners)
    {
        return function ($type, $data) use ($listeners) {
            foreach ($listeners as $listener) {
                $listener->handle($type, $data);
            }
        };
    }

    private function doExecutionFailure($command, $errorOutput, ?\Exception $e = null)
    {
        $this->logger->error($this->createErrorMessage($command, $errorOutput));

        throw new ExecutionFailureException($this->name, $command, $errorOutput, $e ? $e->getCode() : 0, $e);
    }

    private function getErrorOutput(Process $process)
    {
        try {
            return $process->getErrorOutput();
        } catch (LogicException $e) {
            return '';
        }
    }

    private function createErrorMessage($command, $errorOutput)
    {
        return sprintf('%s failed to execute command %s: %s', $this->name, $command, $errorOutput);
    }
}
