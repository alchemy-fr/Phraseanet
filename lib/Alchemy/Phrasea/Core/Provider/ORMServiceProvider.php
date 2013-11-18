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

use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\MonologSQLLogger;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\FileCacheReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration as ORMConfiguration;
use Doctrine\DBAL\Types\Type;
use Gedmo\DoctrineExtensions;
use Gedmo\Timestampable\TimestampableListener;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ORMServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['EM.sql-logger.file'] = $app['root.path'] . '/logs/doctrine-log.log';
        $app['EM.sql-logger.max-files'] = 5;

        $app['EM.sql-logger'] = $app->share(function (Application $app) {
            $logger = new Logger('doctrine-logger');
            $logger->pushHandler(new RotatingFileHandler($app['EM.sql-logger.file'], $app['EM.sql-logger.max-files']));

            return new MonologSQLLogger($logger, 'yaml');
        });

        $app['EM.driver'] = $app->share(function (Application $app) {
            AnnotationRegistry::registerFile(
                $app['root.path'].'/vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'
            );

            $annotationReader = new AnnotationReader();
            $fileCacheReader = new FileCacheReader(
                $annotationReader,
                $app['root.path']."/tmp/doctrine",
                $app['debug']
            );

            $driverChain = new DriverChain();
            DoctrineExtensions::registerAbstractMappingIntoDriverChainORM(
                $driverChain,
                $fileCacheReader
            );

            $annotationDriver = new AnnotationDriver(
                $annotationReader,
                [$app['root.path'].'/lib/Alchemy/Phrasea/Model/Entities']
            );

            $driverChain->addDriver($annotationDriver, 'Alchemy\Phrasea\Model\Entities');

            return $driverChain;
        });

        $app['EM.config'] = $app->share(function (Application $app) {
            $config = new ORMConfiguration();

            if ($app['debug']) {
                $config->setSQLLogger($app['EM.sql-logger']);
            }

            $config->setMetadataCacheImpl($app['phraseanet.cache-service']->factory(
                'ORMmetadata', $app['EM.opcode-cache-type'], $app['EM.opcode-cache-options']
            ));

            $config->setQueryCacheImpl($app['phraseanet.cache-service']->factory(
                'ORMquery', $app['EM.opcode-cache-type'], $app['EM.opcode-cache-options']
            ));
            $config->setResultCacheImpl($app['phraseanet.cache-service']->factory(
                'ORMresult', $app['EM.cache-type'], $app['EM.cache-options']
            ));

            $config->setAutoGenerateProxyClasses($app['debug']);

            $config->setMetadataDriverImpl($app['EM.driver']);

            $config->setProxyDir($app['root.path'] . '/lib/Alchemy/Phrasea/Model/Proxies');
            $config->setProxyNamespace('Alchemy\Phrasea\Model\Proxies');

            return $config;
        });

        $app['EM.opcode-cache-type'] = $app->share(function (Application $app) {
            return $app['configuration']['main']['opcodecache']['type'];
        });
        $app['EM.opcode-cache-options'] = $app->share(function (Application $app) {
            return $app['configuration']['main']['opcodecache']['options'];
        });

        $app['EM.cache-type'] = $app->share(function (Application $app) {
            return $app['configuration']['main']['cache']['type'];
        });
        $app['EM.cache-options'] = $app->share(function (Application $app) {
            return $app['configuration']['main']['cache']['options'];
        });
        $app['EM.events-manager'] = $app->share(function (Application $app) {
            $evm = new EventManager();
            $evm->addEventSubscriber(new TimestampableListener());

            return $evm;
        });

        $app['EM.dbal-conf'] = $app->share(function (Application $app) {
            if ('test' === $app->getEnvironment()) {
                return $app['configuration']['main']['database-test'];
            }

            return $app['configuration']['main']['database'];
        });

        $app['EM'] = $app->share(function (Application $app) {
            try {
                $em = EntityManager::create($app['EM.dbal-conf'], $app['EM.config'], $app['EM.events-manager']);
            } catch (\Exception $e) {
                throw new RuntimeException("Unable to create database connection", $e->getCode(), $e);
            }

            $platform = $em->getConnection()->getDatabasePlatform();

            $types = [
                'blob' => 'Alchemy\Phrasea\Model\Types\Blob',
                'enum' => 'Alchemy\Phrasea\Model\Types\Blob',
                'longblob' => 'Alchemy\Phrasea\Model\Types\LongBlob',
                'varbinary' => 'Alchemy\Phrasea\Model\Types\VarBinary',
                'binary' => 'Alchemy\Phrasea\Model\Types\Binary',
            ];

            foreach ($types as $type => $class) {
                if (!Type::hasType($type)) {
                    Type::addType($type, $class);
                }
                $platform->registerDoctrineTypeMapping($type, $type);
            }

            return $em;
        });
    }

    public function boot(Application $app)
    {
    }
}
