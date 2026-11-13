<?php

namespace Neutron\ProcessManager;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Process\Exception\RuntimeException;

class ManagedProcess
{
    /** @var Process */
    private $process;
    /** @var integer */
    private $executions;
    /** @var integer */
    private $initialExecutions;
    /** @var array */
    private $failures = 0;
    /** @var Boolean */
    private $hasRun = false;

    public function __construct(Process $process, $executions = 1)
    {
        $this->process = $process;
        $this->setExecutions($executions);
    }

    public function __clone()
    {
        $this->process = clone $this->process;
    }

    /**
     * Gets the remaining number of executions.
     *
     * @return integer
     */
    public function getExecutions()
    {
        return $this->executions;
    }

    /**
     * Sets the number of executions.
     *
     * @param integer $executions
     *
     * @return ManagedProcess
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function setExecutions($executions)
    {
        if ($this->hasRun) {
            throw new RuntimeException('Can not modify executions once the process started. Reset the process before modifying this.');
        }

        if ($executions < 1) {
            throw new InvalidArgumentException('Executions must be a positive value.');
        }

        $this->executions = $this->initialExecutions = $executions;

        return $this;
    }

    /**
     * Retries the last execution.
     *
     * @return ManagedProcess
     */
    public function retry()
    {
        if (!$this->hasRun) {
            throw new RuntimeException('Process must have run at least once to retry.');
        }

        $this->executions++;

        return $this;
    }

    /**
     * Resets the process.
     *
     * @return ManagedProcess
     */
    public function reset()
    {
        $this->hasRun = false;
        $this->executions = $this->initialExecutions;
        $this->failures = 0;

        return $this;
    }

    /**
     * Checks if the process has run.
     *
     * @return Boolean
     */
    public function hasRun()
    {
        return $this->hasRun;
    }

    /**
     * Checks if the process can still run.
     *
     * If the number of remaining executions is 0, it returns false.
     *
     * @return Boolean
     */
    public function canRun()
    {
        return $this->executions > 0;
    }

    /**
     * Increments execution failure.
     *
     * @return ManagedProcess
     */
    public function incrementFailures()
    {
        $this->failures++;

        return $this;
    }

    /**
     * Returns execution failures count.
     *
     * @return integer
     */
    public function getFailuresCount()
    {
        return $this->failures;
    }

    /**
     * Returns the managed process.
     *
     * @return Process
     */
    public function getManagedProcess()
    {
        return $this->process;
    }

    /**
     * {@inheritdoc}
     */
    public function run(?callable $callback = null)
    {
        if (!$this->canRun()) {
            throw new RuntimeException('No remaining executions for the managed process.');
        }

        $this->hasRun = true;
        $this->executions--;

        return $this->process->run($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function start(?callable $callback = null)
    {
        if (!$this->canRun()) {
            throw new RuntimeException('No remaining executions for the managed process.');
        }

        $this->hasRun = true;
        $this->process->start($callback);
        $this->executions--;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function restart(?callable $callback = null)
    {
        $this->hasRun = true;
        $process = clone $this;
        $process->setManagedProcess($this->process->restart($callback));

        return $process;
    }

    /**
     * {@inheritdoc}
     */
    public function wait(?callable $callback = null)
    {
        return $this->process->wait($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function signal($signal)
    {
        $this->process->signal($signal);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        return $this->process->isSuccessful();
    }

    /**
     * {@inheritdoc}
     */
    public function isRunning()
    {
        return $this->process->isRunning();
    }

    /**
     * Symfony Process stops synchronously, so a managed process is never
     * observed in a stopping state.
     *
     * @return Boolean
     */
    public function isStopping()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return $this->process->isStarted();
    }

    /**
     * {@inheritdoc}
     */
    public function isTerminated()
    {
        return $this->process->isTerminated();
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return $this->process->getStatus();
    }

    /**
     * {@inheritdoc}
     */
    public function stop($timeout = 10, $signal = null)
    {
        return $this->process->stop($timeout, $signal);
    }

    /**
     * {@inheritdoc}
     */
    public function checkTimeout()
    {
        $this->process->checkTimeout();
    }

    /**
     * Sets the managed process
     *
     * @param Process $process
     *
     * @return ManagedProcess
     */
    private function setManagedProcess(Process $process)
    {
        $this->process = $process;

        return $this;
    }
}
