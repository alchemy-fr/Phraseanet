<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\MetaProvider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Cache\Manager;
use Alchemy\Phrasea\Core\Provider\ORMServiceProvider;
use Dflydev\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\ORM\Configuration;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Provider\DoctrineServiceProvider;

class DatabaseMetaProvider implements ServiceProviderInterface
{

    public function register(Container $app)
    {
        $app->register(new DoctrineServiceProvider());
        $this->setupDBAL($app);
        $app->register(new DoctrineOrmServiceProvider());
        $this->setupOrms($app);
        $app->register(new ORMServiceProvider());
    }

    private function setupDBAL(PhraseaApplication $app)
    {
        $app->extend('dbs.config', function ($configs, $app) {
            if (!isset($app['dbal.config.register.loggers'])) {
                return $configs;
            }

            $loggerRegisterCallable = $app['dbal.config.register.loggers'];

            foreach ($configs->keys() as $service) {
                $loggerRegisterCallable($configs[$service], $service);
            }

            return $configs;
        });

        $app->extend('dbs.event_manager', function ($eventManagers, $app) {
            foreach ($eventManagers->keys() as $name) {
                /** @var EventManager $eventManager */
                $eventManager = $eventManagers[$name];
                $app['dbal.evm.register.listeners']($eventManager);

                $eventManager->addEventListener(Events::postConnect, $this);
            }

            return $eventManagers;
        });
    }

    /**
     * @param ConnectionEventArgs $args
     * @throws \Doctrine\DBAL\Exception
     */
    public function postConnect(ConnectionEventArgs $args)
    {
        $params = $args->getConnection()->getParams();
        if (in_array($params['driver'] ?? null, ['pdo_sqlite', 'sqlite3'], true)) {
            $args->getConnection()->executeStatement('PRAGMA foreign_keys = ON');
        }
    }

    private function setupOrms(PhraseaApplication $app)
    {
        // Override "orm.cache.configurer" service provided for benefiting
        // of "phraseanet.cache-service"
        $app['orm.cache.configurer'] = $app->protect(function ($name, Configuration $config, $options) use ($app) {
            /** @var Manager $service */
            $service = $app['phraseanet.cache-service'];

            $config->setMetadataCacheImpl(
                $app['orm.cache.factory.filesystem'](array(
                    'path' => $app['cache.path'].'/doctrine/metadata',
                ))
            );
            $config->setQueryCacheImpl(
                $app['orm.cache.factory.filesystem'](array(
                    'path' => $app['cache.path'].'/doctrine/query',
                ))
            );
            $config->setResultCacheImpl(
                $service->factory('ORM_result', $app['orm.cache.driver'], $app['orm.cache.options'])
            );
            $config->setHydrationCacheImpl(
                $service->factory('ORM_hydration', $app['orm.cache.driver'], $app['orm.cache.options'])
            );
        });

        $app['orm.proxies_dir'] = $app['root.path'] . '/resources/proxies';
        $app['orm.auto_generate_proxies'] = $app['debug'];
        $app['orm.proxies_namespace'] = 'Alchemy\Phrasea\Model\Proxies';

        $app->extend('orm.ems', function (\Pimple\Container $ems, $app) {
            foreach ($ems->keys() as $key) {
                $connection = $ems[$key]->getConnection();

                $app['connection.pool.manager']->add($connection);

                $types = $app['orm.ems.options'][$key]['types'];
                $app['dbal.type.register']($connection, $types);
            }

            return $ems;
        });
    }
}
