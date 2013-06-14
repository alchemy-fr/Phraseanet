<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use JMS\Serializer\SerializerBuilder;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationReader;

class JMSSerializerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['serializer.cache-directory'] = $app['root.path'] . '/tmp/serializer/';
        $app['serializer.src_directory'] = $app['root.path'] . '/vendor/jms/serializer/src/';

        $app['serializer.metadata.annotation_reader'] = $app->share(function () use ($app) {
            AnnotationRegistry::registerAutoloadNamespace("JMS\Serializer\Annotation", $app['serializer.src_directory']);

            return new AnnotationReader();
        });

        $app['serializer'] = $app->share(function (Application $app) {
            return SerializerBuilder::create()->setCacheDir($app['serializer.cache-directory'])
                ->setDebug($app['debug'])
                ->setAnnotationReader($app['serializer.metadata.annotation_reader'])
                ->build();
        });
    }

    public function boot(Application $app)
    {
    }
}
