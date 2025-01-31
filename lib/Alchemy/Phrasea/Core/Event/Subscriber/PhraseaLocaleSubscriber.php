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

use Silex\Application;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class PhraseaLocaleSubscriber implements EventSubscriberInterface
{
    private $app;
    private $locale;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['addLocale', 255],
                // symfony locale is set on 16 priority, let's override it
                ['addLocale', 17],
                ['addLocale', 15],
            ],
            KernelEvents::RESPONSE => [
                ['addLocaleCookie', 8],
            ],
            KernelEvents::FINISH_REQUEST => [
                ['unsetLocale', -255],
            ]
        ];
    }

    public function unsetLocale()
    {
        $this->locale = null;
    }

    public function addLocale(GetResponseEvent $event)
    {
        if (isset($this->locale)) {
            $this->app['locale'] = $this->locale;

            return;
        }

        $event->getRequest()->setLocale($this->app['locale']);

        if ($event->getRequest()->cookies->has('locale')
            && isset($this->app['locales.available'][$event->getRequest()->cookies->get('locale')])) {
            $event->getRequest()->setLocale($event->getRequest()->cookies->get('locale'));
        }

        $this->locale = $this->app['locale'] = $event->getRequest()->getLocale();
    }

    public function addLocaleCookie(FilterResponseEvent $event)
    {
        $cookies = $event->getRequest()->cookies;

        if (isset($this->locale) && (false === $cookies->has('locale') || $cookies->get('locale') !== $this->locale)) {
            $cookiePath = ini_get('session.cookie_path');
            $cookieDomain = ini_get('session.cookie_domain');
            $cookieHttpOnly = ini_get('session.cookie_httponly');
            $cookieSecure = ini_get('session.cookie_secure');

            $event->getResponse()->headers->setCookie(new Cookie(
                'locale',
                $this->locale,
                0,
                $cookiePath,
                empty($cookieDomain)? null : $cookieDomain,
                $cookieSecure ? true: false,
                $cookieHttpOnly ? true : false
            ));
        }
    }
}
