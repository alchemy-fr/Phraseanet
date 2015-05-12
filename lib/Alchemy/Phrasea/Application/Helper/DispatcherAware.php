<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
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
     * Set Locator to use to locate event dispatcher
     *
     * @param callable $locator
     * @return $this
     */
    public function setDispatcherLocator(callable $locator)
    {
        $this->dispatcher = $locator;

        return $this;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher()
    {
        if ($this->dispatcher instanceof EventDispatcherInterface) {
            return $this->dispatcher;
        }

        if (null === $this->dispatcher) {
            throw new \LogicException('Dispatcher locator was not set');
        }

        $dispatcher = call_user_func($this->dispatcher);
        if (!$dispatcher instanceof EventDispatcherInterface) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of "%s", got "%s"',
                EventDispatcherInterface::class,
                is_object($dispatcher) ? get_class($dispatcher) : gettype($dispatcher)
            ));
        }
        $this->dispatcher = $dispatcher;

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
