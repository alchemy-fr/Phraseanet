<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PhraseaExceptionHandlerSubscriber implements EventSubscriberInterface
{
    protected $enabled;
    protected $handler;

    public function __construct(ExceptionHandler $handler)
    {
        $this->enabled = true;
        $this->handler = $handler;
    }

    public function disable()
    {
        $this->enabled = false;
    }

    public function onSilexError(GetResponseForExceptionEvent $event)
    {
        if (!$this->enabled) {
            return;
        }

        $event->setResponse($this->handler->createResponseBasedOnRequest($event->getRequest(), $event->getException()));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::EXCEPTION => ['onSilexError', 0]];
    }
}
