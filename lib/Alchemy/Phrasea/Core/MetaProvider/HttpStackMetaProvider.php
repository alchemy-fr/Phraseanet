<?php

namespace Alchemy\Phrasea\Core\MetaProvider;

use Alchemy\Cors\Options\DefaultProvider;
use Alchemy\CorsProvider\CorsServiceProvider;
use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderServiceProvider;
use Alchemy\Phrasea\Core\Provider\ContentNegotiationServiceProvider;
use Alchemy\Phrasea\Core\Provider\SessionHandlerServiceProvider;
use Silex\Application;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RequestContext;

class HttpStackMetaProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        if (! $app instanceof PhraseaApplication) {
            throw new \LogicException('Expected an instance Alchemy\Phrasea\Application');
        }

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new UrlGeneratorServiceProvider());

        $this->setupRequestContext($app);

        $app->register(new SessionHandlerServiceProvider());
        $app->register(new SessionServiceProvider(), [
            'session.test' => $app->getEnvironment() === PhraseaApplication::ENV_TEST,
            'session.storage.options' => ['cookie_lifetime' => 0]
        ]);

        $app['session.storage.test'] = $app->share(function () {
            return new MockArraySessionStorage();
        });

        $app['session.storage.handler'] = $app->share(function (Application $app) {
            if (!$app['phraseanet.configuration-tester']->isInstalled()) {
                return new NullSessionHandler();
            }
            return $app['session.storage.handler.factory']->create($app['conf']);
        });

        $app->register(new ControllerProviderServiceProvider());

        $this->registerCors($app);
    }

    public function setupRequestContext(Application $app)
    {
        $app['request_context'] = $app->share($app->extend('request_context', function (RequestContext $context, Application $app) {
            if ($app['configuration.store']->isSetup()) {
                $data = parse_url($app['conf']->get('servername'));

                if (isset($data['scheme'])) {
                    $context->setScheme($data['scheme']);
                }
                if (isset($data['host'])) {
                    $context->setHost($data['host']);
                }
            }

            return $context;
        }));
    }

    public function registerCors(Application $app)
    {
        $app->register(new ContentNegotiationServiceProvider());
        $app->register(new CorsServiceProvider(), [
            'alchemy_cors.debug' => $app['debug'],
            'alchemy_cors.cache_path' => function (Application $app) {
                return rtrim($app['cache.path'], '/\\') . '/alchemy_cors.cache.php';
            },
        ]);

        $app['phraseanet.api_cors.options_provider'] = function (Application $app) {
            $paths = [];

            if (isset($app['phraseanet.configuration']['api_cors'])) {
                $config = $app['phraseanet.configuration']['api_cors'];

                if (isset($config['enabled']) && $config['enabled']) {
                    unset($config['enabled']);

                    $paths['/api/v\d+/'] = $config;
                    $paths['/download/'] = $config;
                }
                if (isset($app['phraseanet.configuration']['api_cors_paths'])) {
                    foreach ($app['phraseanet.configuration']['api_cors_paths'] as $path) {
                        $paths[$path] = $config;
                    }
                }
            }

            return new DefaultProvider($paths, []);
        };

        $app['alchemy_cors.options_providers'][] = 'phraseanet.api_cors.options_provider';
    }

    public function boot(Application $app)
    {
        // no-op
    }
}
