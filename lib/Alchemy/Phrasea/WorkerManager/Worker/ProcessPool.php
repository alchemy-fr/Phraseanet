<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use PhpAmqpLib\Channel\AMQPChannel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ProcessPool implements LoggerAwareInterface
{
    const MAX_PROCESSES = 1;

    /**
     * Hard wall-clock limit (seconds) for a single child worker process.
     *
     * A child runs the real job (DB writes, Elasticsearch indexing, exiftool,
     * subdef I/O, ...). Any of those can block on a half-open socket or a stuck
     * subprocess with no error. Because waitForNextSlot() only frees a slot when
     * a child is no longer running, a child that hangs forever would freeze the
     * whole queue (prefetch=1). This timeout guarantees a stuck child is killed
     * and its slot reclaimed, so the queue self-heals within at most this delay.
     */
    const DEFAULT_PROCESS_TIMEOUT = 600;

    /**
     * @var int
     */
    private $maxProcesses = self::MAX_PROCESSES;

    /**
     * @var float
     */
    private $processTimeout = self::DEFAULT_PROCESS_TIMEOUT;

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

    public function setProcessTimeout($processTimeout)
    {
        // 0 / null disables the wall-clock limit (not recommended).
        $this->processTimeout = ($processTimeout === null) ? null : max(0, (float) $processTimeout);
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param array $processArguments
     * @param AMQPChannel $channel
     * @param string|null $workingDirectory
     * @return Process
     */
    public function getWorkerProcess(array $processArguments, AMQPChannel $channel, $workingDirectory = null)
    {
        $this->detachFinishedProcesses();
        $this->waitForNextSlot($channel);

        $process = new Process($processArguments);

        $process->setWorkingDirectory($workingDirectory ?: getcwd());

        // Arm the wall-clock limit; it is enforced by checkTimeout() in
        // detachFinishedProcesses(). Without this, a hung child would occupy its
        // slot forever and stall the queue.
        $process->setTimeout($this->processTimeout);

        return ($this->processes[] = $process);
    }

    private function detachFinishedProcesses()
    {
        $runningProcesses = [];

        foreach ($this->processes as $process) {
            try {
                // Kills and reports the child once it exceeds processTimeout.
                // Safe to call on a not-yet-started process (no-op then).
                $process->checkTimeout();
            } catch (ProcessTimedOutException $e) {
                $this->logger->error(sprintf(
                    'Worker child process exceeded the %ss time limit and was killed to free its slot: %s',
                    $this->processTimeout,
                    $process->getCommandLine()
                ));
                // checkTimeout() already stopped it; just drop it from the pool
                // so the slot is reclaimed.
                continue;
            }

            if ($process->isRunning()) {
                $runningProcesses[] = $process;
            } else {
                $process->stop(0);
            }
        }

        $this->processes = $runningProcesses;
    }

    private function waitForNextSlot(AMQPChannel $channel)
    {
        $this->logger->debug(
            sprintf('Checking for available process slot: %d processes found.', count($this->processes))
        );

        $interval = 1;

        while (count($this->processes) >= $this->maxProcesses) {
            $channel->getConnection()->checkHeartBeat();
            $this->logger->debug(sprintf('%d Max process count reached, will retry in %d second.', $this->maxProcesses, $interval));

            sleep($interval);

            $this->detachFinishedProcesses();
            $interval = min(10, $interval + 1);
        }
    }
}
