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
use Alchemy\Phrasea\Application as PhraseaApplication;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

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
        return array(
            KernelEvents::REQUEST => array(
                array('addLocale', 255),
                // symfony locale is set on 16 priority, let's override it
                array('addLocale', 17),
                array('addLocale', 15),
                array('removePhraseanetLocale', 14),
            ),
        );
    }

    public function removePhraseanetLocale(GetResponseEvent $event)
    {
        if (isset($this->locale)) {
            unset($this->locale);
        }
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

        $this->app['locale'] = $this->locale = $this->app->share(function(Application $app) use ($event) {
            if (isset($app['phraseanet.registry'])) {
                $event->getRequest()->setDefaultLocale(
                    $app['phraseanet.registry']->get('GV_default_lng', 'en_GB')
                );
                $event->getRequest()->setLocale(
                    $app['phraseanet.registry']->get('GV_default_lng', 'en_GB')
                );
            }

            $languages = PhraseaApplication::getAvailableLanguages();
            if ($event->getRequest()->cookies->has('locale')
                && isset($languages[$event->getRequest()->cookies->get('locale')])) {
                $event->getRequest()->setLocale($event->getRequest()->cookies->get('locale'));

                return $event->getRequest()->getLocale();
            }

            $localeSet = false;

            foreach ($app['bad-faith']->headerLists['accept_language']->items as $language) {
                $code = $language->lang . ($language->sublang ? '_' . $language->sublang : null);
                if (isset($languages[$code])) {
                    $event->getRequest()->setLocale($code);
                    $localeSet = true;

                    return $event->getRequest()->getLocale();
                    break;
                }
            }

            if (!$localeSet) {
                $event->getRequest()->setLocale($app['phraseanet.registry']->get('GV_default_lng'));
            }

            return $event->getRequest()->getLocale();
        });

        \phrasea::use_i18n($this->app['locale']);
    }
}
