<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use JMS\Serializer\SerializerBuilder;

class JMSSerializerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['serializer.cache-directory'] = $app->share(function (Application $app) {
            return $app['root.path'] . '/tmp/serializer/';
        });
        $app['serializer.metadata_dirs'] = $app->share(function () {
            return [];
        });
        $app['serializer'] = $app->share(function (Application $app) {
            $builder = SerializerBuilder::create()
                ->setCacheDir($app['serializer.cache-directory'])
                ->setDebug($app['debug']);

            if (!empty($app['serializer.metadata_dirs'])) {
                $builder->addMetadataDirs($app['serializer.metadata_dirs']);
            }

            return $builder->build();
        });
    }

    public function boot(Application $app)
    {
    }
}
