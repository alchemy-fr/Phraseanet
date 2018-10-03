<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Application\Helper;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

trait DispatcherAware
{
    private $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @return $this
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher()
    {
        if (null === $this->dispatcher) {
            throw new \LogicException('Dispatcher was not set');
        }

        return $this->dispatcher;
    }

    /**
     * @see \Symfony\Component\EventDispatcher\EventDispatcherInterface::dispatch
     */
    public function dispatch($eventName, Event $event = null)
    {
        return $this->getDispatcher()->dispatch($eventName, $event);
    }
}
