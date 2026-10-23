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
use Alchemy\Phrasea\Core\Connection\Connection as PhraseanetConnection;
use Alchemy\Phrasea\Core\Connection\ConnectionPoolManager;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\NativeQueryProvider;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration as ORMConfig;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Gedmo\Timestampable\TimestampableListener;
use Monolog\Handler\RotatingFileHandler;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class ORMServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // Provides DSN string using database information
        $app['db.dsn'] = $app->protect(function (array $params) use ($app) {
            $params = $app['db.info']($params);

            switch ($params['driver'])
            {
                case 'pdo_mysql':
                    return sprintf('%s://%s:%s@%s:%s/%s',
                        $params['driver'],
                        $params['user'],
                        $params['password'],
                        $params['host'],
                        $params['port'],
                        $params['dbname']
                    );
                case 'pdo_sqlite':
                    return sprintf('%s:%s',
                        $params['driver'],
                        $params['path']
                    );
                default:
                    throw new \UnexpectedValueException(sprintf('Unknown driver "%s"', $params['driver']));
            }
        });

        // Hash a DSN string
        $app['hash.dsn'] = $app->protect(function ($dsn) {
            return md5($dsn);
        });

        // Return database test configuration
        $app['db.test.info'] = function () use ($app) {
            return $app['conf']->get(['main', 'database-test'], array());
        };

        // Return application box database configuration
        $app['db.appbox.info'] = function () use ($app) {
            return $app['conf']->get(['main', 'database'], array());
        };

        // Return database fixture configuration
        $app['db.fixture.info'] = function () use ($app) {
            return [
                'driver'  => 'pdo_sqlite',
                'path'    => sprintf('%s/%s', $app['tmp.path'], 'db-ref.sqlite'),
                'charset' => 'UTF8',
            ];
        };

        // Return databox database configuration
        $app['db.databox.info'] = function () use ($app) {
            if (false === $app['phraseanet.configuration']->isSetup()) {
                return array();
            }

            try {
                /** @var Connection $connection */
                $connection = $app['dbal.provider']($app['db.appbox.info']);

                $sql = "SELECT"
                    . " host, port, `user`, COALESCE(pwd, '') AS password, dbname, 'utf8' AS charset, 'pdo_mysql' AS driver"
                    . " FROM sbas";

                return $connection->fetchAllAssociative($sql);
            } catch (\Exception $e) {
                return [];
            }
        };

        // Return unique key for fixture database
        $app['db.fixture.hash.key'] = function () use ($app) {
            $info = $app['db.fixture.info'];

            return $app['hash.dsn']($app['db.dsn']($info));
        };

        // Return unique key for test database
        $app['db.test.hash.key'] = function () use ($app) {
            $info = $app['db.test.info'];

            return $app['hash.dsn']($app['db.dsn']($info));
        };

        // Return unique for appbox database
        $app['db.appbox.hash.key'] = function () use ($app) {
            $info = $app['db.appbox.info'];

            return $app['hash.dsn']($app['db.dsn']($info));
        };

        // Return configuration option for test database in DoctrineServiceProvider
        $app['db.test.options'] = function () use ($app) {
            return array($app['db.test.hash.key'] => $app['db.test.info']);
        };

        // Return configuration option for test database in DoctrineServiceProvider
        $app['db.fixture.options'] = function () use ($app) {
            return array($app['db.fixture.hash.key'] => $app['db.fixture.info']);
        };

        // Return configuration option for appbox database in DoctrineServiceProvider
        $app['db.appbox.options'] = function () use ($app) {
            return array($app['db.appbox.hash.key'] => $app['db.appbox.info']);
        };

        // Return configuration option for databox databases in DoctrineServiceProvider
        $app['dbs.databox.options'] = function () use ($app) {
            $options = array();

            foreach ($app['db.databox.info'] as $info) {
                $info = $app['db.info']($info);

                $key = $app['hash.dsn']($app['db.dsn']($info));

                $options[$key] = $info;
            }

            return $options;
        };

        // Return DoctrineServiceProvider database options, it merges all previous
        // set database configuration
        $app['dbs.options'] = function () use ($app) {
            if (false === $app['phraseanet.configuration']->isSetup()) {
                return [];
            }

            return array_merge(
                $app['db.appbox.options'],
                $app['dbs.databox.options'],
                $app['db.fixture.options'],
                $app['db.test.options']
            );
        };

        // Return DoctrineORMServiceProvider information for a database from its parameters
        $app['orm.em.options.from_info'] = $app->protect(function (array $info) use ($app) {
            $info = $app['db.info']($info);

            $key = $app['hash.dsn']($app['db.dsn']($info));

            return array($key => $app['orm.options']($key));
        });

        //Return DoctrineServiceProvider information for a database from its parameters
        $app['db.options.from_info'] = $app->protect(function (array $info) use ($app) {
            $info = $app['db.info']($info);

            $key = $app['hash.dsn']($app['db.dsn']($info));

            return array($key => $info);
        });

        /**
         * Add orm on the fly, used only when a new databox is mounted.
         * This allow to use new EM instance right after the database is mounted.
         */
        $app['orm.add'] = $app->protect(function ($info) use ($app) {
            $info = $app['db.info']($info);

            $key = $app['hash.dsn']($app['db.dsn']($info));

            // a database mounted again replaces its previous definitions; the containers are built
            // first, as building "dbs" reads the definitions of the default database
            $containers = array_map(function ($containerId) use ($app) {
                return $app[$containerId];
            }, ['dbs', 'orm.ems', 'dbs.event_manager', 'dbs.config']);
            foreach ($containers as $container) {
                unset($container[$key]);
            }

            $evm = new EventManager();
            $app['dbal.evm.register.listeners']($evm);
            $app['dbs.event_manager'][$key] = $evm;

            $app['dbs.config'][$key] = new Configuration();

            $app['dbs'][$key] = function () use ($app, $info, $key) {
                return DriverManager::getConnection($info, $app['dbs.config'][$key], $app['dbs.event_manager'][$key]);
            };

            $options = $app['orm.options']($key);
            $config = $app['orm.config.new']($key, $options);

            $app['orm.ems'][$key] = function ($ems) use ($app, $key, $options, $config) {
                $connection = $app['dbs'][$key];
                $app['connection.pool.manager']->add($connection);

                $types = $options['types'];
                $app['dbal.type.register']($connection, $types);

                return new EntityManager(
                    $connection,
                    $config,
                    $app['dbs.event_manager'][$options['connection']]
                );
            };

            return $key;
        });

        // Listeners should be attached with their events as info.
        $app['dbal.evm.listeners'] = function () {
            return new \SplObjectStorage();
        };

        $app['dbal.evm.register.listeners'] = $app->protect(function (EventManager $evm) use ($app) {
            $evm->addEventSubscriber(new TimestampableListener());
            /** @var \SplObjectStorage $listeners */
            $listeners = $app['dbal.evm.listeners'];
            foreach ($listeners as $listener) {
                $evm->addEventListener($listeners[$listener], $listener);
            }
        });


        $app['dbal.type.register'] = $app->protect(function (Connection $connection, $types) {
            $platform = $connection->getDatabasePlatform();

            foreach (array_keys((array) $types) as $type) {
                $platform->registerDoctrineTypeMapping($type, $type);
            }
        });

        $app['orm.config.new'] = $app->protect(function ($key, $options) use($app) {
            $config = new ORMConfig();
            $app['orm.cache.configurer']($key, $config, $options);

            $config->setProxyDir($app['orm.proxies_dir']);
            $config->setProxyNamespace($app['orm.proxies_namespace']);
            $config->setAutoGenerateProxyClasses($app['orm.auto_generate_proxies']);

            $config->setCustomStringFunctions($app['orm.custom.functions.string']);
            $config->setCustomNumericFunctions($app['orm.custom.functions.numeric']);
            $config->setCustomDatetimeFunctions($app['orm.custom.functions.datetime']);
            $config->setCustomHydrationModes($app['orm.custom.hydration_modes']);

            $config->setClassMetadataFactoryName($app['orm.class_metadata_factory_name']);
            $config->setDefaultRepositoryClassName($app['orm.default_repository_class']);

            $config->setEntityListenerResolver($app['orm.entity_listener_resolver']);
            $config->setRepositoryFactory($app['orm.repository_factory']);

            $config->setNamingStrategy($app['orm.strategy.naming']);
            $config->setQuoteStrategy($app['orm.strategy.quote']);

            $chain = $app['orm.mapping_driver_chain.locator']($key);

            foreach ((array) $options['mappings'] as $entity) {
                if (!is_array($entity)) {
                    throw new \InvalidArgumentException(
                        "The 'orm.em.options' option 'mappings' should be an array of arrays."
                    );
                }

                if (!empty($entity['resources_namespace'])) {
                    $entity['path'] = $app['psr0_resource_locator']->findFirstDirectory($entity['resources_namespace']);
                }

                if (isset($entity['alias'])) {
                    $config->addEntityNamespace($entity['alias'], $entity['namespace']);
                }

                switch ($entity['type'])
                {
                    case 'attribute':
                        $driver = new AttributeDriver((array) $entity['path']);
                        $chain->addDriver($driver, $entity['namespace']);
                        break;
                    case 'yml':
                        $driver = new YamlDriver($entity['path']);
                        $chain->addDriver($driver, $entity['namespace']);
                        break;
                    case 'simple_yml':
                        $driver = new SimplifiedYamlDriver(array($entity['path'] => $entity['namespace']));
                        $chain->addDriver($driver, $entity['namespace']);
                        break;
                    case 'xml':
                        $driver = new XmlDriver($entity['path']);
                        $chain->addDriver($driver, $entity['namespace']);
                        break;
                    case 'simple_xml':
                        $driver = new SimplifiedXmlDriver(array($entity['path'] => $entity['namespace']));
                        $chain->addDriver($driver, $entity['namespace']);
                        break;
                    case 'php':
                        $driver = new StaticPHPDriver($entity['path']);
                        $chain->addDriver($driver, $entity['namespace']);
                        break;
                    default:
                        throw new \InvalidArgumentException(sprintf('"%s" is not a recognized driver', $entity['type']));
                        break;
                }
            }
            $config->setMetadataDriverImpl($chain);

            foreach ((array) $options['types'] as $typeName => $typeClass) {
                if (Type::hasType($typeName)) {
                    Type::overrideType($typeName, $typeClass);
                } else {
                    Type::addType($typeName, $typeClass);
                }
            }

            return $config;
        });

        $app['orm.ems.options'] = function () use ($app) {
            if (false === $app['phraseanet.configuration']->isSetup()) {
                return [];
            }

            return array_merge(
                $app['orm.em.appbox.options'],
                $app['orm.ems.databox.options'],
                $app['orm.em.fixture.options'],
                $app['orm.em.test.options']

            );
        };

        /**
         * Check database connection information
         */
        $app['db.info'] = $app->protect(function (array $info) {
            if (!isset($info['driver'])) {
                $info['driver'] = 'pdo_mysql';
            }

            if (!isset($info['charset'])) {
                $info['charset'] = 'utf8';
            }

            if (!isset($info['wrapperClass'])) {
                $info['wrapperClass'] = PhraseanetConnection::class;
            }

            switch ($info['driver'])
            {
                case 'pdo_mysql':
                    foreach (array('user', 'password', 'host', 'dbname', 'port') as $param) {
                        if (!array_key_exists($param, $info)) {
                            throw new InvalidArgumentException(sprintf('Missing "%s" argument for database connection using driver %s', $param, $info['driver']));
                        }
                    }
                break;
                case 'pdo_sqlite':
                    if (!array_key_exists('path', $info)) {
                        throw new InvalidArgumentException(sprintf('Missing "path" argument for database connection using driver %s', $info['driver']));
                    }
                break;
            }

            return $info;
        });

        /**
         * Return configuration option for appbox database in DoctrineORMServiceProvider
         */
        $app['orm.em.appbox.options'] = function () use ($app) {
            $key = $app['db.appbox.hash.key'];

            return array($key => $app['orm.options']($key));
        };

        /**
         * Return configuration option for fixture database in DoctrineORMServiceProvider
         */
        $app['orm.em.fixture.options'] = function () use ($app) {
            $key = $app['db.fixture.hash.key'];

            return array($key => $app['orm.options']($key));
        };

        /**
         * Return configuration option for test database in DoctrineORMServiceProvider
         */
        $app['orm.em.test.options'] = function () use ($app) {
            $key = $app['db.test.hash.key'];

            return array($key => $app['orm.options']($key));
        };

        /**
         * Return configuration option for databox databases in DoctrineORMServiceProvider
         */
        $app['orm.ems.databox.options'] = function () use ($app) {
            $options = array();

            foreach ($app['db.databox.info'] as $base) {
                $info = $app['db.info']($base);

                $key = $app['hash.dsn']($app['db.dsn']($info));

                $options[$key] = $app['orm.options']($key);
            }

            return $options;
        };

        $app['orm.options.mappings'] = function (PhraseaApplication $app) {
            return array(
                array(
                    "type" => "attribute",
                    "namespace" => 'Alchemy\Phrasea\Model\Entities',
                    "path" => $app['root.path'] . '/lib/Alchemy/Phrasea/Model/Entities',
                )
            );
        };

        // Return orm configuration for a connection given its unique id
        $app['orm.options'] = $app->protect(function ($connection) use ($app) {
            return array(
                "connection" => $connection,
                "mappings" => $app['orm.options.mappings'],
                "types" => array(
                    'blob' => 'Alchemy\Phrasea\Model\Types\Blob',
                    'enum' => 'Alchemy\Phrasea\Model\Types\Enum',
                    'longblob' => 'Alchemy\Phrasea\Model\Types\LongBlob',
                    'varbinary' => 'Alchemy\Phrasea\Model\Types\VarBinary',
                    'binary' => 'Alchemy\Phrasea\Model\Types\Binary',
                    'binary_string' => 'Alchemy\Phrasea\Model\Types\BinaryString',
                )
            );
        });

        /**
         * Path to doctrine log file
         */
        $app['orm.monolog.handler.file'] = function (Application $app) {
            return $app['log.path'] . '/doctrine.log';
        };

        /**
         * Maximum files of logs
         */
        $app['orm.monolog.handler.file.max-files'] = 5;

        /**
         * Monolog handler for doctrine
         */
        $app['orm.monolog.handler'] = function (Application $app) {
            return new RotatingFileHandler($app['orm.monolog.handler.file'], $app['orm.monolog.handler.file.max-files']);
        };

        /**
         * Monolog instance for doctrine
         */
        $app['orm.monolog.logger'] = function (Application $app) {
            $logger = new $app['monolog.logger.class']('doctrine-logger');

            $logger->pushHandler($app['orm.monolog.handler']);

            return $logger;
        };

        /**
         * Return cache driver
         */
        $app['orm.cache.driver'] = function (Application $app) {
            if ($app['configuration.store']->isSetup()) {
                return $app['conf']->get(['main', 'cache', 'type']);
            }

            return 'array';
        };

        /**
         * Return cache options
         */
        $app['orm.cache.options'] = function (Application $app) {
            if ($app['configuration.store']->isSetup()) {
                return $app['conf']->get(['main', 'cache', 'options']);
            }

            return [];
        };

        /**
         * Retrieve a registered DBALConnection using configuration parameters
         */
        $app['db.provider'] = $app->protect(function (array $info) use ($app) {
            $info = $app['db.info']($info);

            $key = $app['hash.dsn']($app['db.dsn']($info));

            if (!isset($app['dbs'][$key])) {
                return $app['dbal.provider']($info);
            }

            return $app['dbs'][$key];
        });

        // Returns a new DBALConnection instance using configuration parameters
        $app['dbal.provider'] = $app->protect(function (array $info) use ($app) {
            $info = $app['db.info']($info);

            /** @var ConnectionPoolManager $manager */
            $manager = $app['connection.pool.manager'];
            return $manager->get($info);
        });

        $app['connection.pool.manager'] = function () {
            return new ConnectionPoolManager();
        };

        /**
         * Return an instance of native cache query for default ORM
         * @todo return an instance of NativeQueryProvider for given orm;
         */
        $app['orm.em.native-query'] = function ($app) {
            return new NativeQueryProvider($app['orm.em']);
        };

    }
}
