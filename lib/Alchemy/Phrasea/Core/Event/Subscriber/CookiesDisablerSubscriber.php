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

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class CookiesDisablerSubscriber implements EventSubscriberInterface
{
    private static $NOSESSION_ROUTES = '/^((\/api\/v\d+)|(\/api\/?$)|(\/permalink))/';
    private $app;
    private $sessionCookieEnabled = true;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['checkRoutePattern', 512],
            KernelEvents::RESPONSE => ['removeCookies', -512],
        ];
    }

    public function checkRoutePattern(GetResponseEvent $event)
    {
        if (preg_match(static::$NOSESSION_ROUTES, $event->getRequest()->getPathInfo())) {
            $this->app['session.test'] = true;
            $this->sessionCookieEnabled = false;
        }
    }

    public function removeCookies(FilterResponseEvent $event)
    {
        if ($this->sessionCookieEnabled) {
            return;
        }

        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $response = $event->getResponse();

        /** @var Cookie $cookie */
        foreach ($response->headers->getCookies() as $cookie) {
            $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
        }
    }
}
