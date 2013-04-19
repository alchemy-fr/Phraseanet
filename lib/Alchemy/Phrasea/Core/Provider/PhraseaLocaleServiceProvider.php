<?php

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class PhraseaLocaleServiceProvider implements ServiceProviderInterface
{
    private $app;
    private $locale;

    public function register(Application $app)
    {
        $this->app = $app;
    }

    public function boot(Application $app)
    {
        // make locale available asap
        $app['dispatcher']->addListener(KernelEvents::REQUEST, array($this, 'addLocale'), 255);
        // symfony locale is set on 16 priority, let's override it
        $app['dispatcher']->addListener(KernelEvents::REQUEST, array($this, 'addLocale'), 17);
        $app['dispatcher']->addListener(KernelEvents::REQUEST, array($this, 'addLocale'), 15);
        $app['dispatcher']->addListener(KernelEvents::REQUEST, array($this, 'removePhraseanetLocale'), 14);
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
            $event->getRequest()->setDefaultLocale(
                $app['phraseanet.registry']->get('GV_default_lng', 'en_GB')
            );
            $event->getRequest()->setLocale(
                $app['phraseanet.registry']->get('GV_default_lng', 'en_GB')
            );

            $languages = $app->getAvailableLanguages();
            if ($event->getRequest()->cookies->has('locale')
                && isset($languages[$event->getRequest()->cookies->get('locale')])) {
                $event->getRequest()->setLocale($event->getRequest()->cookies->get('locale'));

                return $event->getRequest()->getLocale();
            }

            foreach ($app['bad-faith']->headerLists['accept_language']->items as $language) {
                $code = $language->lang.'_'.$language->sublang;
                if (isset($languages[$code])) {

                    $event->getRequest()->setLocale($code);

                    return $event->getRequest()->getLocale();
                    break;
                }
            }

            return $event->getRequest()->getLocale();
        });

        \phrasea::use_i18n($this->app['locale']);
    }
}
