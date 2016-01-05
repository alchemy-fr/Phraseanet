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

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FirewallSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE  => ['onKernelResponse', 0],
            KernelEvents::EXCEPTION => ['onSilexError', 20],
        ];
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($event->getResponse()->headers->has('X-Phraseanet-Redirect')) {
            $event->getResponse()->headers->remove('X-Phraseanet-Redirect');
        }
    }

    public function onSilexError(GetResponseForExceptionEvent $event)
    {
        $e = $event->getException();

        if ($e instanceof HttpExceptionInterface) {
            $headers = $e->getHeaders();

            if (isset($headers['X-Phraseanet-Redirect'])) {
                $event->setResponse(new RedirectResponse($headers['X-Phraseanet-Redirect'], 302, ['X-Status-Code' => 302]));
            }
        }
    }
}
