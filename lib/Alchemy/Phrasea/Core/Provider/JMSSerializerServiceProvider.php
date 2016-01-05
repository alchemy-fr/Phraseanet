<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Doctrine\Common\Annotations\AnnotationRegistry;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\SerializerBuilder;
use Silex\Application;
use Silex\ServiceProviderInterface;

class JMSSerializerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['serializer.cache-directory'] = $app->share(function () use ($app) {
            return $app['cache.path'].'/serializer/';
        });
        $app['serializer.metadata_dirs'] = $app->share(function () {
            return [];
        });
        $app['serializer.handlers'] = $app->share(function () {
            return [];
        });
        $app['serializer'] = $app->share(function (Application $app) {
            // Register JMS annotation into Doctrine's registry
            AnnotationRegistry::registerAutoloadNamespace(
                'JMS\Serializer\Annotation',
                $app['root.path'] . '/vendor/jms/serializer/src/'
            );

            $builder = SerializerBuilder::create()
                ->setCacheDir($app['serializer.cache-directory'])
                ->setDebug($app['debug']);

            if (!empty($app['serializer.metadata_dirs'])) {
                $builder->addMetadataDirs($app['serializer.metadata_dirs']);
            }

            if (!empty($app['serializer.handlers'])) {
                $builder->configureHandlers(function (HandlerRegistryInterface $registry) use ($app) {
                    foreach ($app['serializer.handlers'] as $handler) {
                        $registry->registerSubscribingHandler($handler);
                    }
                });
            }

            return $builder->build();
        });
    }

    public function boot(Application $app)
    {
    }
}
