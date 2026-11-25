<?php

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager\Job;

use Psr\Log\LoggerAwareInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A Job Interface
 */
interface JobInterface extends LoggerAwareInterface
{
    const STATUS_STARTED = 'started';
    const STATUS_STOPPING = 'stopping';
    const STATUS_STOPPED = 'stopped';

    /**
     * Adds a listener to the job.
     *
     * @param string   $eventName
     * @param callable $listener
     *
     * @return JobInterface
     */
    public function addListener($eventName, $listener);

    /**
     * Adds an event subscriber to the job.
     *
     * @param EventSubscriberInterface $subscriber
     *
     * @return JobInterface
     */
    public function addSubscriber(EventSubscriberInterface $subscriber);

    /**
     * Stops the job.
     *
     * @return JobInterface
     *
     * @api
     */
    public function stop(?JobDataInterface $data = null);

    /**
     * Runs the job.
     *
     * If a callback is provided, the it will be call with the job itself as
     * first argument an the return value of the doRun method as second
     * argument.
     *
     * @param JobDataInterface $data
     * @param callable         $callback
     *
     * @return JobInterface
     *
     * @api
     */
    public function run(?JobDataInterface $data = null, $callback = null);

    /**
     * Runs the job implementation a single time.
     *
     * @param JobDataInterface $data
     *
     * @return JobInterface
     */
    public function singleRun(?JobDataInterface $data = null);

    /**
     * Checks if the job is running.
     *
     * Please note that a stopping job is considered running. To check if the
     * status is exactly in the started status, use the isStarted method.
     *
     * @return Boolean
     *
     * @api
     */
    public function isRunning();

    /**
     * Checks if the job is stopping.
     *
     * @return Boolean
     *
     * @api
     */
    public function isStopping();

    /**
     * Checks if the job is started.
     *
     * @return Boolean
     *
     * @api
     */
    public function isStarted();

    /**
     * Gets the current status of the job, one of the STATUS_* constant.
     *
     * @return string
     *
     * @api
     */
    public function getStatus();
}
