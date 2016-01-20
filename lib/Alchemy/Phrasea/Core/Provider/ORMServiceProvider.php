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
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\Setup;
use Gedmo\DoctrineExtensions;
use Gedmo\Timestampable\TimestampableListener;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ORMServiceProvider implements ServiceProviderInterface
{

    private static $customTypes = [
        'blob' => '\Alchemy\Phrasea\Model\Types\Blob',
        'enum' => '\Alchemy\Phrasea\Model\Types\Enum',
        'longblob' => '\Alchemy\Phrasea\Model\Types\LongBlob',
        'varbinary' => '\Alchemy\Phrasea\Model\Types\VarBinary',
        'binary' => '\Alchemy\Phrasea\Model\Types\Binary',
        'binary_string' => '\Alchemy\Phrasea\Model\Types\BinaryString',
    ];

    public function register(Application $app)
    {
        if (! $app instanceof PhraseaApplication) {
            throw new \LogicException('Application must be an instance of Alchemy\Phrasea\Application');
        }

        $app['orm.em'] = $app->share(function (PhraseaApplication $app) {
            $connectionParameters = $this->buildConnectionParameters($app);
            $configuration = $this->buildConfiguration($app);
            $eventManager = new EventManager();

            $this->registerCustomTypes();
            $this->registerEventListeners($eventManager);

            return EntityManager::create($connectionParameters, $configuration, $eventManager);
        });

        $app['dbal.provider'] = $app->protect(function (array $parameters) {
            $parameters = $this->validateConnectionSettings($parameters);

            return DriverManager::getConnection($parameters);
        });
    }

    private function registerCustomTypes()
    {
        foreach (self::$customTypes as $name => $type) {
            if (Type::hasType($name)) {
                Type::overrideType($name, $type);
            } else {
                Type::addType($name, $type);
            }
        }
    }

    private function registerEventListeners(EventManager $eventManager)
    {
        $eventManager->addEventSubscriber(new TimestampableListener());
    }

    private function buildConnectionParameters(PhraseaApplication $app)
    {
        var_dump($app->getEnvironment());

        if ($app->getEnvironment() == PhraseaApplication::ENV_TEST) {
            //return $app['conf']->get(['main', 'database-test'], array());
            return [
                'driver'  => 'pdo_sqlite',
                'path'    => sprintf('%s/%s', $app['tmp.path'], 'db-ref.sqlite'),
                'charset' => 'UTF8',
            ];
        }

        return $app['conf']->get(['main', 'database'], array());
    }

    /**
     * @param PhraseaApplication $app
     * @return \Doctrine\ORM\Configuration
     */
    private function buildConfiguration(PhraseaApplication $app)
    {
        $devMode = $app->getEnvironment() == PhraseaApplication::ENV_DEV;
        $proxiesDirectory = $app['root.path'] . '/resources/proxies';
        $doctrineAnnotationsPath = $app['root.path'] . '/vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php';

        $driver = $this->buildMetadataDriver($app, $doctrineAnnotationsPath);
        $cache = $this->buildCache($app, 'EntityManager');

        $configuration = Setup::createConfiguration($devMode, $proxiesDirectory, $cache);
        $configuration->setMetadataDriverImpl($driver);
        $configuration->addEntityNamespace('Phraseanet', 'Alchemy\Phrasea\Model\Entities');

        return $configuration;
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

    /**
     * @param PhraseaApplication $app
     * @param $doctrineAnnotationsPath
     * @return AnnotationDriver
     */
    private function buildMetadataDriver(PhraseaApplication $app, $doctrineAnnotationsPath)
    {
        DoctrineExtensions::registerAnnotations();
        AnnotationRegistry::registerFile($doctrineAnnotationsPath);

        $driver = new AnnotationDriver(new AnnotationReader(), [
            $app['root.path'] . '/vendor/gedmo/doctrine-extensions/lib/Gedmo/Translatable/Entity/MappedSuperclass',
            $app['root.path'] . '/vendor/gedmo/doctrine-extensions/lib/Gedmo/Loggable/Entity/MappedSuperclass',
            $app['root.path'] . '/vendor/gedmo/doctrine-extensions/lib/Gedmo/Tree/Entity/MappedSuperclass',
            $app['root.path'] . '/lib/Alchemy/Phrasea/Model/Entities'
        ]);

        return $driver;
    }

    private function getCacheDriver(PhraseaApplication $app)
    {
        return 'ArrayCache';
    }

    private function getCacheOptions(PhraseaApplication $app)
    {
        return [];
    }

    private function validateConnectionSettings(array $parameters)
    {
        if (!isset($parameters['driver'])) {
            $parameters['driver'] = 'pdo_mysql';
        }

        if (!isset($parameters['charset'])) {
            $parameters['charset'] = 'utf8';
        }

        switch ($parameters['driver']) {
            case 'pdo_mysql':
                foreach (array('user', 'password', 'host', 'dbname', 'port') as $param) {
                    if (!array_key_exists($param, $parameters)) {
                        throw new InvalidArgumentException(sprintf('Missing "%s" argument for database connection using driver %s', $param, $parameters['driver']));
                    }
                }
                break;
            case 'pdo_sqlite':
                if (!array_key_exists('path', $parameters)) {
                    throw new InvalidArgumentException(sprintf('Missing "path" argument for database connection using driver %s', $parameters['driver']));
                }
                break;
        }

        return $parameters;
    }

    public function boot(Application $app)
    {
        // @todo Bootstrap all databox entity managers
    }
}
