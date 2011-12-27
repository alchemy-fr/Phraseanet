<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service;

use Doctrine\DBAL\Types\Type;

/**
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Doctrine
{

  protected $entityManager;

  public function __construct(Array $conf)
  {
    require_once __DIR__ . '/../../../../vendor/doctrine2-orm/lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

    static::loadClasses();

    $config = new \Doctrine\ORM\Configuration();

    if ($conf["debug"]["enable"])
    {
      //Force Array
      $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
      $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
    }
    else
    {
      if ($conf["cache"]["query_cache"]["enable"])
      {
        //define query cache
        switch ($conf["cache"]["query_cache"]["type"])
        {
          case 'memcached':
            $config->setQueryCacheImpl(new \Doctrine\Common\Cache\MemcacheCache());
            break;
          case 'apc':
            $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ApcCache());
            break;
          case 'array':
          default:
            $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
            break;
        }
      }

      if ($conf["cache"]["metadatas_cache"]["enable"])
      {
        //define metadatas cache
        switch ($conf["cache"]["metadatas_cache"]["type"])
        {
          case 'memcached':
            $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\MemcacheCache());
            break;
          case 'apc':
            $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ApcCache());
            break;
          case 'array':
          default:
            $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
            break;
        }
      }
    }

    //define autoregeneration of proxies
    if (!$conf["debug"]["enable"])
    {
      $config->setAutoGenerateProxyClasses(false);
    }
    else
    {
      $config->setAutoGenerateProxyClasses(true);
    }

    //define logger
    if ($conf["debug"]["enable"] && $conf["logger"]["enable"])
    {
      switch ($conf["logger"]["type"])
      {
        case 'monolog':
          $logger = new \Monolog\Logger('query-logger');
          $logger->pushHandler(new \Monolog\Handler\RotatingFileHandler(
                          __DIR__ . '/../../../../../logs/doctrine-query.log')
                  , $conf["logger"]["max_day"]
          );
          $config->setSQLLogger(new \Doctrine\Logger\MonologSQLLogger(
                          $logger,
                          $conf["logger"]["output"])
          );
          break;
        case 'echo':
          $config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
          break;
        default:
          $config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
          break;
      }
    }

    $chainDriverImpl = new \Doctrine\ORM\Mapping\Driver\DriverChain();

    $driverYaml = new \Doctrine\ORM\Mapping\Driver\YamlDriver(
                    array(__DIR__ . '/../../../../conf.d/Doctrine')
    );

    $chainDriverImpl->addDriver($driverYaml, 'Entities');

    $chainDriverImpl->addDriver($driverYaml, 'Gedmo\Timestampable');

    $config->setMetadataDriverImpl($chainDriverImpl);

    $config->setProxyDir(realpath(__DIR__ . '/../../../../Doctrine/Proxies'));

    $config->setProxyNamespace('Proxies');

    require __DIR__ . '/../../../../../config/connexion.inc';

    $connectionOptions = array(
        'dbname' => $dbname,
        'user' => $user,
        'password' => $password,
        'host' => $hostname,
        'driver' => 'pdo_mysql',
    );

    $evm = new \Doctrine\Common\EventManager();

    $evm->addEventSubscriber(new \Gedmo\Timestampable\TimestampableListener());

    $this->entityManager = \Doctrine\ORM\EntityManager::create($conf["credentials"], $config, $evm);

    $this->addTypes();

    return $this;
  }

  public function getEntityManager()
  {
    return $this->entityManager;
  }

  public function getVersion()
  {
    return \Doctrine\Common\Version::VERSION;
  }

  protected static function loadClasses()
  {

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Doctrine\ORM'
                    , realpath(__DIR__ . '/../../../../vendor/doctrine2-orm/lib')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Doctrine\DBAL'
                    , realpath(__DIR__ . '/../../../../vendor/doctrine2-orm/lib/vendor/doctrine-dbal/lib')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Doctrine\Common'
                    , realpath(__DIR__ . '/../../../../vendor/doctrine2-orm/lib/vendor/doctrine-common/lib')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Entities'
                    , realpath(__DIR__ . '/../../../../Doctrine')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Repositories'
                    , realpath(__DIR__ . '/../../../../Doctrine')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Proxies'
                    , realpath(__DIR__ . '/../../../../Doctrine')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Symfony'
                    , realpath(__DIR__ . '/../../../../vendor/doctrine2-orm/lib/vendor')
    );

    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Doctrine\Logger'
                    , realpath(__DIR__ . '/../../../../')
    );

    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Monolog'
                    , realpath(__DIR__ . '/../../../../vendor/Silex/vendor/monolog/src')
    );

    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Types'
                    , realpath(__DIR__ . '/../../../../Doctrine')
    );

    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Gedmo'
                    , __DIR__ . "/../../../../vendor/doctrine2-gedmo/lib"
    );
    $classLoader->register();


    return;
  }

  protected function addTypes()
  {

    $platform = $this->entityManager->getConnection()->getDatabasePlatform();

    Type::addType('blob', 'Types\Blob');
    Type::addType('enum', 'Types\Enum');
    Type::addType('longblob', 'Types\LongBlob');
    Type::addType('varbinary', 'Types\VarBinary');

    $platform->registerDoctrineTypeMapping('enum', 'enum');
    $platform->registerDoctrineTypeMapping('blob', 'blob');
    $platform->registerDoctrineTypeMapping('longblob', 'longblob');
    $platform->registerDoctrineTypeMapping('varbinary', 'varbinary');

    return;
  }

}