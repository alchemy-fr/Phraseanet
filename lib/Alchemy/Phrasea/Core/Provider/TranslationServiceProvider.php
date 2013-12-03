<?php

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Utilities\CachedTranslator;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\ArrayLoader;
use JMS\TranslationBundle\Translation\Loader\Symfony\XliffLoader;

class TranslationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['translator.cache-options'] = array();

        $app['translator'] = $app->share(function ($app) {
            $app['translator.cache-options'] = array_replace(
                array(
                    'debug' => $app['debug'],
                ), $app['translator.cache-options']
            );

            $translator = new CachedTranslator($app, $app['translator.message_selector'], $app['translator.cache-options']);

            // Handle deprecated 'locale_fallback'
            if (isset($app['locale_fallback'])) {
                $app['locale_fallbacks'] = (array) $app['locale_fallback'];
            }

            $translator->setFallbackLocales($app['locale_fallbacks']);

            $translator->addLoader('array', new ArrayLoader());
            $translator->addLoader('xliff', new XliffLoader());

            foreach ($app['translator.domains'] as $domain => $data) {
                foreach ($data as $locale => $messages) {
                    $translator->addResource('array', $messages, $locale, $domain);
                }
            }

            return $translator;
        });

        $app['translator.message_selector'] = $app->share(function () {
            return new MessageSelector();
        });

        $app['translator.domains'] = array();
        $app['locale_fallbacks'] = array('en');
    }

    public function boot(Application $app)
    {
    }
}
