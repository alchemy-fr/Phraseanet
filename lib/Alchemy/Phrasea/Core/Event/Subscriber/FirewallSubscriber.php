<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FirewallSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE  => ['onKernelResponse', 0],
            KernelEvents::EXCEPTION => ['onSilexError', 20],
        ];
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        if ($event->getResponse()->headers->has('X-Phraseanet-Redirect')) {
            $event->getResponse()->headers->remove('X-Phraseanet-Redirect');
        }
    }

    public function onSilexError(ExceptionEvent $event)
    {
        $e = $event->getThrowable();

        if ($e instanceof HttpExceptionInterface) {
            $headers = $e->getHeaders();

            if (isset($headers['X-Phraseanet-Redirect'])) {
                $event->setResponse(new RedirectResponse($headers['X-Phraseanet-Redirect'], 302, ['X-Status-Code' => 302]));
            }
        }
    }
}
