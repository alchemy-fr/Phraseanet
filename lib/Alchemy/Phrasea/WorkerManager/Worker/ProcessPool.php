<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class ProcessPool implements LoggerAwareInterface
{
    const MAX_PROCESSES = 4;

    /**
     * @var int
     */
    private $maxProcesses = self::MAX_PROCESSES;

    /**
     * @var Process[]
     */
    private $processes = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function setMaxProcesses($maxProcesses)
    {
        $this->maxProcesses = max(1, $maxProcesses);
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
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

        $builder = new ProcessBuilder($processArguments);

        $builder->setWorkingDirectory($workingDirectory ?: getcwd());

        return ($this->processes[] = $builder->getProcess());
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
            $this->logger->debug(sprintf('%d Max process count reached, will retry in %d second.', $this->maxProcesses, $interval));

            sleep($interval);

            $this->detachFinishedProcesses();
            $interval = min(10, $interval + 1);
        }
    }
}
