<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
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

        /**
         * add content negotiation here
         */
        $contentTypes = $event->getRequest()->getAcceptableContentTypes();
        $event->getRequest()->setRequestFormat(
            $event->getRequest()->getFormat(
                array_shift(
                    $contentTypes
                )
            )
        );

        $this->app['locale'] = $this->app->share(function (Application $app) use ($event) {
            if (isset($app['phraseanet.registry'])) {
                $event->getRequest()->setDefaultLocale(
                    $app['phraseanet.registry']->get('GV_default_lng', 'en')
                );
                $event->getRequest()->setLocale(
                    $app['phraseanet.registry']->get('GV_default_lng', 'en')
                );
            }

            $languages = $app['locales.available'];
            if ($event->getRequest()->cookies->has('locale')
                && isset($languages[$event->getRequest()->cookies->get('locale')])) {
                $event->getRequest()->setLocale($event->getRequest()->cookies->get('locale'));

                return $event->getRequest()->getLocale();
            }

            $localeSet = false;

            foreach ($event->getRequest()->getLanguages() as $code) {
                $data = preg_split('/[-_]/', $code);
                if (in_array($data[0], array_keys($app['locales.available']), true)) {
                    $event->getRequest()->setLocale($data[0]);
                    $localeSet = true;
                    break;
                }
            }

            if (!$localeSet) {
                $event->getRequest()->setLocale($app['phraseanet.registry']->get('GV_default_lng'));
            }

            return $event->getRequest()->getLocale();
        });

        $this->locale = $this->app['locale'];
    }

    public function addLocaleCookie(FilterResponseEvent $event)
    {
        $cookies = $event->getRequest()->cookies;

        if (isset($this->locale) && (false === $cookies->has('locale') || $cookies->get('locale') !== $this->locale)) {
            $event->getResponse()->headers->setCookie(new Cookie('locale', $this->locale, 0,  '/',  null, false, false));
        }
    }
}
