<?php

namespace Alchemy\Phrasea\Core\MetaProvider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Cache\Manager;
use Alchemy\Phrasea\Core\Provider\ORMServiceProvider;
use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Doctrine\DBAL\Events;
use Doctrine\ORM\Configuration;
use Gedmo\DoctrineExtensions as GedmoExtension;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\ServiceProviderInterface;

class DatabaseMetaProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app->register(new DoctrineServiceProvider());
        $this->setupDBAL($app);
        $app->register(new DoctrineOrmServiceProvider());
        $this->setupOrms($app);
        $app->register(new ORMServiceProvider());
    }

    private function setupDBAL(PhraseaApplication $app)
    {
        $app['dbs.config'] = $app->share($app->extend('dbs.config', function ($configs, $app) {
            if (! $app->isDebug()) {
                return $configs;
            }

            foreach($configs->keys() as $service) {
                $app['dbal.config.register.loggers']($configs[$service]);
            }

            return $configs;
        }));

        $app['dbs.event_manager'] = $app->share($app->extend('dbs.event_manager', function ($eventManagers, $app) {
            foreach ($eventManagers->keys() as $name) {
                /** @var EventManager $eventManager */
                $eventManager = $eventManagers[$name];
                $app['dbal.evm.register.listeners']($eventManager);

                $eventManager->addEventListener(Events::postConnect, $app);
            }

            return $eventManagers;
        }));
    }

    private function setupOrms(PhraseaApplication $app)
    {
        // Override "orm.cache.configurer" service provided for benefiting
        // of "phraseanet.cache-service"
        $app['orm.cache.configurer'] = $app->protect(function($name, Configuration $config, $options) use ($app)  {
            /** @var Manager $service */
            $service = $app['phraseanet.cache-service'];

            $config->setMetadataCacheImpl(
                $service->factory('ORM_metadata', $app['orm.cache.driver'], $app['orm.cache.options'])
            );
            $config->setQueryCacheImpl(
                $service->factory('ORM_query', $app['orm.cache.driver'], $app['orm.cache.options'])
            );
            $config->setResultCacheImpl(
                $service->factory('ORM_result', $app['orm.cache.driver'], $app['orm.cache.options'])
            );
            $config->setHydrationCacheImpl(
                $service->factory('ORM_hydration', $app['orm.cache.driver'], $app['orm.cache.options'])
            );
        });

        $app['orm.proxies_dir'] = $app['root.path'].'/resources/proxies';
        $app['orm.auto_generate_proxies'] = $app['debug'];
        $app['orm.proxies_namespace'] = 'Alchemy\Phrasea\Model\Proxies';

        $app['orm.ems'] = $app->share($app->extend('orm.ems', function (\Pimple $ems, $app) {
            GedmoExtension::registerAnnotations();

            foreach ($ems->keys() as $key) {
                $app['orm.annotation.register']($key);
                $connection = $ems[$key]->getConnection();

                $app['connection.pool.manager']->add($connection);

                $types = $app['orm.ems.options'][$key]['types'];
                $app['dbal.type.register']($connection, $types);
            }

            return $ems;
        }));
    }

    public function boot(Application $app)
    {
        // no-op
    }
}
