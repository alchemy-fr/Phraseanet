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

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Cache\Manager;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\Setup;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ORMServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (! $app instanceof PhraseaApplication) {
            throw new \LogicException('Application must be an instance of Alchemy\Phrasea\Application');
        }

        $app['orm.em'] = $app->share(function (PhraseaApplication $app) {
            $devMode = $app->getEnvironment() == PhraseaApplication::ENV_DEV;
            $proxiesDirectory = $app['root.path'] . '/resources/proxies';

            $driver = new AnnotationDriver(new AnnotationReader(), [
                $app['root.path'] . '/vendor/gedmo/doctrine-extensions/lib/Gedmo/Translatable/Entity/MappedSuperclass',
                $app['root.path'] . '/vendor/gedmo/doctrine-extensions/lib/Gedmo/Loggable/Entity/MappedSuperclass',
                $app['root.path'] . '/vendor/gedmo/doctrine-extensions/lib/Gedmo/Tree/Entity/MappedSuperclass',
                $app['root.path'] . '/lib/Alchemy/Phrasea/Model/Entities'
            ]);

            $configuration = Setup::createConfiguration($devMode, $proxiesDirectory, $this->buildCache($app, 'EntityManager'));

            $configuration->setMetadataDriverImpl($driver);
        });
    }

    private function buildCache(PhraseaApplication $app, $cacheType)
    {
        /** @var Cache $cache */
        static $cache;

        if ($cache !== null) {
            return $cache;
        }

        /** @var Manager $cacheManager */
        $cacheManager = $app['phraseanet.cache-service'];

        $cacheDriver = $this->getCacheDriver($app);
        $cacheOptions = $this->getCacheOptions($app);

        $cache = $cacheManager->factory($cacheType, $cacheDriver, $cacheOptions);

        return $cache;
    }

    private function getCacheDriver(PhraseaApplication $app)
    {
        return 'ArrayCache';
    }

    private function getCacheOptions(PhraseaApplication $app)
    {
        return [];
    }

    public function boot(Application $app)
    {
        // @todo Bootstrap all databox entity managers
    }
}
