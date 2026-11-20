<?php

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager\Event\JobSubscriber;

use Alchemy\TaskManager\Event\JobEvent;
use Alchemy\TaskManager\Event\JobEvents;
use Neutron\SignalHandler\SignalHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Stops a Job in case a SIGINT or SIGTERM is received.
 */
class StopSignalSubscriber implements EventSubscriberInterface
{
    private $logger;
    private $signalHandler;
    private $namespace;

    public function __construct(SignalHandler $signalHandler, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->signalHandler = $signalHandler;
        $this->namespace = uniqid('stopsignal', true) . microtime(true);
    }

    public static function getSubscribedEvents()
    {
        return array(
            JobEvents::START     => 'onJobStart',
            JobEvents::STOP      => 'onJobStop',
            JobEvents::EXCEPTION => 'onJobStop',
        );
    }

    public function onJobStart(JobEvent $event)
    {
        $logger = $this->logger;
        $callback = function ($signal) use ($event, $logger) {
            if ($event->getJob()->isStarted()) {
                if (null !== $logger) {
                    $logger->notice(sprintf('Caught stop signal `%d` for %s, stopping', $signal, (string) $event->getData()));
                }
                $event->getJob()->stop($event->getData());
            }
        };

        $this->signalHandler->register(array(SIGTERM, SIGINT), $callback, $this->namespace);
    }

    public function onJobStop(JobEvent $event)
    {
        $this->signalHandler->unregisterNamespace($this->namespace);
    }
}
