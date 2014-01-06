<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Exception\RuntimeException;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration as ORMConfiguration;
use Doctrine\DBAL\Types\Type;
use Doctrine\Logger\MonologSQLLogger;
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

        $app['EM'] = $app->share(function (Application $app) {

            $config = new ORMConfiguration();

            if ($app['debug']) {
                $config->setSQLLogger($app['EM.sql-logger']);
            }

            $opCodeCacheType = $app['phraseanet.configuration']['main']['opcodecache']['type'];
            $opCodeCacheOptions = $app['phraseanet.configuration']['main']['opcodecache']['options'];

            $cacheType = $app['phraseanet.configuration']['main']['cache']['type'];
            $cacheOptions = $app['phraseanet.configuration']['main']['cache']['options'];

            $config->setMetadataCacheImpl($app['phraseanet.cache-service']->factory(
                'ORMmetadata', $opCodeCacheType, $opCodeCacheOptions
            ));
            $config->setQueryCacheImpl($app['phraseanet.cache-service']->factory(
                'ORMquery', $opCodeCacheType, $opCodeCacheOptions
            ));
            $config->setResultCacheImpl($app['phraseanet.cache-service']->factory(
                'ORMresult', $cacheType, $cacheOptions
            ));

            //define autoregeneration of proxies base on debug mode
            $config->setAutoGenerateProxyClasses($app['debug']);

            $chainDriverImpl = new DriverChain();
            $driverYaml = new YamlDriver(array($app['root.path'] . '/lib/conf.d/Doctrine'));
            $chainDriverImpl->addDriver($driverYaml, 'Entities');
            $chainDriverImpl->addDriver($driverYaml, 'Gedmo\Timestampable');
            $config->setMetadataDriverImpl($chainDriverImpl);

            $config->setProxyDir($app['root.path'] . '/lib/Doctrine/Proxies');
            $config->setProxyNamespace('Proxies');

            if ('test' === $app->getEnvironment()) {
                $dbalConf = $app['phraseanet.configuration']['main']['database-test'];
            } else {
                $dbalConf = $app['phraseanet.configuration']['main']['database'];
            }

            $evm = new EventManager();
            $evm->addEventSubscriber(new TimestampableListener());

            try {
                $em = EntityManager::create($dbalConf, $config, $evm);
            } catch (\Exception $e) {
                throw new RuntimeException("Unable to create database connection", $e->getCode(), $e);
            }

            $platform = $em->getConnection()->getDatabasePlatform();

            $types = array(
                'blob' => 'Types\Blob',
                'enum' => 'Types\Blob',
                'longblob' => 'Types\LongBlob',
                'varbinary' => 'Types\VarBinary',
                'binary' => 'Types\Binary',
            );

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
