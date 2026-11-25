<?php
declare(ticks=1);

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager\Job;

use Alchemy\TaskManager\Event\JobEvent;
use Alchemy\TaskManager\Event\JobExceptionEvent;
use Alchemy\TaskManager\Event\JobEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractJob implements JobInterface
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;
    /** @var null|string */
    private $status;
    /** @var null|LoggerInterface */
    private $logger;

    public function __construct(?EventDispatcherInterface $dispatcher = null, ?LoggerInterface $logger = null)
    {
        if (null === $dispatcher) {
            $dispatcher = new EventDispatcher();
        }
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    /**
     * {@inheritdoc}
     */
    public function addListener($eventName, $listener)
    {
        $this->dispatcher->addListener($eventName, $listener);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->dispatcher->addSubscriber($subscriber);

        return $this;
    }

    /**
     * Sets the logger for the Job.
     *
     * @param LoggerInterface $logger
     *
     * @api
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * {@inheritdoc}
     */
    public function isRunning()
    {
        return in_array($this->status, array(static::STATUS_STARTED, static::STATUS_STOPPING), true);
    }

    /**
     * {@inheritdoc}
     */
    public function isStopping()
    {
        return static::STATUS_STOPPING === $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return static::STATUS_STARTED === $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return null === $this->status ? static::STATUS_STOPPED : $this->status;
    }

    /**
     * {@inheritdoc}
     */
    final public function run(?JobDataInterface $data = null, $callback = null)
    {
        $this->prepareRun($callback, $data);
        while (static::STATUS_STARTED === $this->status) {
            $this->doRunOrCleanup($data, $callback);
            $this->pause($this->getPauseDuration());
        }
        $this->cleanRun($data);

        return $this;
    }

    /**
     * Runs the job implementation a single time.
     *
     * If a callback is provided, the it will be call with the job itself as
     * first argument an the return value of the doRun method as second
     * argument.
     *
     * @param JobDataInterface $data
     * @param null|callable    $callback A callback
     *
     * @return AbstractJob
     */
    final public function singleRun(?JobDataInterface $data = null, $callback = null)
    {
        $this->prepareRun($callback, $data);
        $this->doRunOrCleanup($data, $callback);
        $this->cleanRun($data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function stop(?JobDataInterface $data = null)
    {
        if ($this->isStarted()) {
            $this->status = static::STATUS_STOPPING;
            $this->dispatcher->dispatch(new JobEvent($this, $data ?: new NullJobData()), JobEvents::STOP_REQUEST);
        }

        return $this;
    }

    /**
     * Tick handler for the job.
     */
    public function tickHandler(JobDataInterface $data)
    {
        if (!$this->isRunning()) {
            return;
        }
        $this->dispatcher->dispatch(new JobEvent($this, $data), JobEvents::TICK);
    }

    /**
     * Logs a message.
     *
     * @param string $method  A valid LoggerInterface method
     * @param string $message The message to be logged
     */
    protected function log($method, $message, array $context = array())
    {
        if (null !== $this->logger) {
            call_user_func(array($this->logger, $method), $message, $context);
        }
    }

    /**
     * The time to pause between two iterations.
     *
     * @return float
     */
    protected function getPauseDuration()
    {
        return 0.005;
    }

    /**
     * The actual run method to implement.
     */
    abstract protected function doRun(JobDataInterface $data);

    /**
     * Pauses the execution of the job for the given duration.
     *
     * @param float $duration
     *
     * @return JobInterface
     */
    protected function pause($duration)
    {
        $time = microtime(true) + $duration;

        while (microtime(true) < $time && static::STATUS_STARTED === $this->status) {
            // 50 ms is a good compromise between performance and reactivity
            usleep(50000);
        }

        return $this;
    }

    /**
     * Sets up the job and register tick handlers.
     *
     * @return JobInterface
     */
    private function setup(JobDataInterface $data)
    {
        $this->status = static::STATUS_STARTED;
        register_tick_function(array($this, 'tickHandler'), $data);

        return $this;
    }

    /**
     * Cleanups a job.
     *
     * @return JobInterface
     */
    private function cleanup()
    {
        unregister_tick_function(array($this, 'tickHandler'));
        $this->status = static::STATUS_STOPPED;

        return $this;
    }

    /**
     * Does execute the doRun method. In case an exception is thrown, cleans up
     * and forward the exception.
     *
     * @param JobDataInterface $data     The data to pass to the doRun method.
     * @param callable    $callback A callback
     *
     * @return JobInterface
     *
     * @throws \Exception The caught exception is forwarded in case it occured.
     */
    private function doRunOrCleanup(JobDataInterface $data, $callback)
    {
        try {
            call_user_func($callback, $this, $this->doRun($data));
        } catch (\Exception $e) {
            $this->cleanup();
            $this->log('error', sprintf('Error while running %s : %s', (string) $data, $e->getMessage()), array('exception' => $e));
            $this->dispatcher->dispatch(new JobExceptionEvent($this, $e, $data), JobEvents::EXCEPTION);
            throw $e;
        }

        return $this;
    }

    /**
     * Returns a callback given a callable or null argument.
     *
     * @param null|callable $callback
     *
     * @return callable
     */
    private function createCallback($callback)
    {
        if (is_callable($callback)) {
            return $callback;
        }

        return function () {};
    }

    /**
     * @param null|callable $callback
     * @param null|JobDataInterface $data
     */
    private function prepareRun(&$callback, ?JobDataInterface &$data = null)
    {
        $data = $data ?: new NullJobData();
        $callback = $this->createCallback($callback);

        $this->dispatcher->dispatch(new JobEvent($this, $data), JobEvents::START);
        $this->setup($data);
    }

    /**
     * @param JobDataInterface $data
     */
    private function cleanRun(JobDataInterface $data)
    {
        $this->dispatcher->dispatch(new JobEvent($this, $data), JobEvents::STOP);
        $this->cleanup();
    }
}
