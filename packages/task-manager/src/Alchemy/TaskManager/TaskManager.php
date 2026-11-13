<?php

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager;

use Alchemy\TaskManager\Event\StateFormater;
use Alchemy\TaskManager\Event\TaskManagerEvent;
use Alchemy\TaskManager\Event\TaskManagerRequestEvent;
use Alchemy\TaskManager\Event\TaskManagerEvents;
use Alchemy\TaskManager\Event\TaskManagerSubscriber\StatusRequestSubscriber;
use Neutron\ProcessManager\ProcessManager;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TaskManager implements LoggerAwareInterface
{
    const MESSAGE_PING = 'PING';
    const MESSAGE_STATE = 'STATE';
    const MESSAGE_STOP = 'STOP';
    const MESSAGE_PROCESS_UPDATE = 'UPDATE';

    const RESPONSE_PONG = 'PONG';
    const RESPONSE_OK = 'OK';
    const RESPONSE_UNHANDLED_MESSAGE = 'UNHANDLED MESSAGE';

    const DEFAULT_POLLING_PERIOD = 1000;
    const DEFAULT_TICK_PERIOD = 0.5;

    /** @var Logger */
    private $logger;
    /** @var TaskListInterface */
    private $list;
    /** @var ProcessManager */
    private $manager;
    /** @var ZMQSocket */
    private $listener;
    /** @var EventDispatcherInterface */
    private $dispatcher;
    /** @var array */
    private $options;

    public function __construct(EventDispatcherInterface $dispatcher, ZMQSocket $listener, LoggerInterface $logger, TaskListInterface $list, array $options = array())
    {
        $this->dispatcher = $dispatcher;
        $this->list = $list;
        $this->logger = $logger;
        $this->listener = $listener;
        $this->manager = new ProcessManager($logger, null, ProcessManager::STRATEGY_IGNORE, ProcessManager::STRATEGY_IGNORE);
        $this->dispatcher->addSubscriber(new StatusRequestSubscriber(new StateFormater()));

        $this->options = array_replace(array(
            'polling_period'     => self::DEFAULT_POLLING_PERIOD,
            'tick_period'        => self::DEFAULT_TICK_PERIOD,
        ), $options);
    }

    public function __destruct()
    {
        if (null === $this->manager) {
            return;
        }

        if ($this->manager->isRunning()) {
            $this->stop();
        }

        if ($this->manager->getStatus() === ProcessManager::STATUS_READY) {
            return;
        }

        while (!$this->manager->isTerminated()) {
            usleep(1000);
        }
    }

    /**
     * Adds a listener to the task manager.
     *
     * @param string   $eventName
     * @param callable $listener
     *
     * @return TaskManager
     */
    public function addListener($eventName, $listener)
    {
        $this->dispatcher->addListener($eventName, $listener);

        return $this;
    }

    /**
     * Adds an event subscriber to the task manager.
     *
     * @param EventSubscriberInterface $subscriber
     *
     * @return TaskManager
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->dispatcher->addSubscriber($subscriber);

        return $this;
    }

    /**
     * Gets the logger.
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Sets the logger.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Returns the underlying process manager.
     *
     * @return ProcessManager
     */
    public function getProcessManager()
    {
        return $this->manager;
    }

    /**
     * Starts the task manager, opens a listener on the given host and port.
     *
     * @return TaskManager
     */
    public function start()
    {
        $this->logger->notice("Starting task manager ...");
        $this->dispatcher->dispatch(new TaskManagerEvent($this), TaskManagerEvents::MANAGER_START);
        $this->listener->bind();

        $this->manager->setDaemon(true);
        $this->updateProcesses();
        $this->manager->start();

        while ($this->manager->isRunning()) {
            $this->manager->signal(SIGCONT);
            $start = microtime(true);
            $this->poll();
            $this->dispatcher->dispatch(new TaskManagerEvent($this), TaskManagerEvents::MANAGER_TICK);
            // sleep at list 10ms, at max 100ms
            usleep((int) (max($this->options['tick_period'] - (microtime(true) - $start), 0.01) * 1E6));
        }

        return $this;
    }

    /**
     * Stops the task manager.
     *
     * @param integer $timeout A timeout
     * @param integer $signal  A signal to send at the end of the timeout.
     *
     * @return TaskManager
     */
    public function stop($timeout = 10, $signal = null)
    {
        if ($this->manager->isRunning()) {
            $this->logger->notice("Stopping task manager ...");
            $this->manager->stop($timeout, $signal);
        }
        if ($this->listener->isBound()) {
            $this->listener->unbind();
        }
        $this->dispatcher->dispatch(new TaskManagerEvent($this), TaskManagerEvents::MANAGER_STOP);

        return $this;
    }

    /**
     * Creates a taskManager.
     *
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface          $logger
     * @param TaskListInterface        $list
     * @param array                    $options
     *
     * @return TaskManager
     */
    public static function create(EventDispatcherInterface $dispatcher, LoggerInterface $logger, TaskListInterface $list, array $options = array())
    {
        $options = array_replace(array(
            'listener_protocol'  => 'tcp',
            'listener_host'      => '127.0.0.1',
            'listener_port'      => 6660,
            'polling_period'     => self::DEFAULT_POLLING_PERIOD,
            'tick_period'        => self::DEFAULT_TICK_PERIOD,
        ), $options);

        $context = new \ZMQContext();
        $listener = new ZMQSocket(
            $context, \ZMQ::SOCKET_REP,
            $options['listener_protocol'],
            $options['listener_host'],
            $options['listener_port']
        );

        return new TaskManager($dispatcher, $listener, $logger, $list, array(
            'polling_period'     => $options['polling_period'],
            'tick_period'        => $options['tick_period'],
        ));
    }

    /**
     * Updates the process, according to the task list.
     */
    private function updateProcesses()
    {
        $names = array_keys($this->manager->getManagedProcesses());

        foreach ($this->list->refresh() as $task) {
            if (!$this->manager->has($task->getName())) {
                $this->manager->add($task->createProcess(), $task->getName(), $task->getIterations());
            }
            if (false !== $offset = array_search($task->getName(), $names, true)) {
                unset($names[$offset]);
            }
        }

        foreach ($names as $name) {
            if ($this->manager->has($name)) {
                $this->manager->remove($name);
            }
        }
    }

    /**
     * Polls ZMQ socket for messages.
     */
    private function poll()
    {
        while (false !== $message = $this->listener->recv(defined('ZMQ::MODE_DONTWAIT') ? \ZMQ::MODE_DONTWAIT : \ZMQ::MODE_NOBLOCK)) {
            $eventName = TaskManagerEvents::MANAGER_REQUEST;
            switch ($message) {
                case static::MESSAGE_PING:
                    $response = static::RESPONSE_PONG;
                    break;
                case static::MESSAGE_STOP:
                    $this->manager->stop();
                    $response = static::RESPONSE_OK;
                    $eventName = TaskManagerEvents::STOP_REQUEST;
                    break;
                case static::MESSAGE_PROCESS_UPDATE:
                    $this->updateProcesses();
                    $response = static::RESPONSE_OK;
                    break;
                default:
                    $response = static::RESPONSE_UNHANDLED_MESSAGE;
                    break;
            }
            $event = new TaskManagerRequestEvent($this, $message, $response);
            $this->logger->info(sprintf('Received message "%s"', $message));
            $this->dispatcher->dispatch($event, $eventName);
            $this->listener->send(json_encode(array("request" => $message, "reply" => $event->getResponse())));
            usleep($this->options['polling_period']);
        }
    }
}
