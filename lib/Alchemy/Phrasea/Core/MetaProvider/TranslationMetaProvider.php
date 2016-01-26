<?php

namespace Alchemy\Phrasea\Core\MetaProvider;

use Alchemy\Phrasea\Core\Provider\TranslationServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class TranslationMetaProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app->register(new TranslationServiceProvider(), [
            'locale_fallbacks' => ['fr'],
            'translator.resources' => [
                [ 'xlf', __DIR__.'/../../../../../resources/locales/messages.fr.xlf', 'fr', 'messages' ],
                [ 'xlf', __DIR__.'/../../../../../resources/locales/validators.fr.xlf', 'fr', 'validators' ],
                [ 'xlf', __DIR__.'/../../../../../resources/locales/messages.en.xlf', 'en', 'messages' ],
                [ 'xlf', __DIR__.'/../../../../../resources/locales/validators.en.xlf', 'en', 'validators' ],
                [ 'xlf', __DIR__.'/../../../../../resources/locales/messages.de.xlf', 'de', 'messages' ],
                [ 'xlf', __DIR__.'/../../../../../resources/locales/validators.de.xlf', 'de', 'validators' ],
                [ 'xlf', __DIR__.'/../../../../../resources/locales/messages.nl.xlf', 'nl', 'messages' ],
                [ 'xlf', __DIR__.'/../../../../../resources/locales/validators.nl.xlf', 'nl', 'validators' ]
            ],
            'translator.cache-options' => [
                'debug' => $app['debug'],
                'cache_dir' => $app->share(function($app) {
                    return $app['cache.path'] . '/translations';
                }),
            ],
        ]);
    }

    public function boot(Application $app)
    {
        // no-op
    }
}
