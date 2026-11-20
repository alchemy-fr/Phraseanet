<?php

/*
 * This file is part of alchemy/worker-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Worker;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;

class ProcessPool implements LoggerAwareInterface
{

    /**
     * @var int
     */
    private $maxProcesses = 4;

    /**
     * @var Process[]
     */
    private $processes = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param int $maxProcesses
     */
    public function __construct($maxProcesses = 4)
    {
        $this->logger = new NullLogger();
        $this->maxProcesses = max(1, $maxProcesses);
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param array $processArguments
     * @param string|null $workingDirectory
     * @return Process
     */
    public function getWorkerProcess(array $processArguments, $workingDirectory = null)
    {
        $this->detachFinishedProcesses();
        $this->waitForNextSlot();

        $process = new Process($processArguments, $workingDirectory ?: getcwd());

        return ($this->processes[] = $process);
    }

    private function detachFinishedProcesses()
    {
        $runningProcesses = [];

        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                $runningProcesses[] = $process;
            } else {
                $process->stop(0);
            }
        }

        $this->processes = $runningProcesses;
    }

    private function waitForNextSlot()
    {
        $this->logger->debug(
            sprintf('Checking for available process slot: %d processes found.', count($this->processes))
        );

        $interval = 1;

        while (count($this->processes) >= $this->maxProcesses) {
            $this->logger->debug(sprintf('Max process count reached, will retry in %d second.', $interval));

            sleep($interval);

            $this->detachFinishedProcesses();
            $interval = min(10, $interval + 1);
        }
    }
}
