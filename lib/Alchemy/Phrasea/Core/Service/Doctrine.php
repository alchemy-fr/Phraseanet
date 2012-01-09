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
use Doctrine\Common\Cache\AbstractCache;

/**
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Doctrine
{
  const MEMCACHED = 'memcached';
  const ARRAYCACHE = 'array';
  const APC = 'apc';

  protected $entityManager;

  public function __construct(Array $doctrineConfiguration = array())
  {
    require_once __DIR__ . '/../../../../vendor/doctrine2-orm/lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

    static::loadClasses();
    
    $config = new \Doctrine\ORM\Configuration();

    /*
     * debug mode
     */
    $debug = isset($doctrineConfiguration["debug"]) ? : false;
    /*
     * doctrine cache
     */
    $cache = isset($doctrineConfiguration["orm"]["cache"]) ? $doctrineConfiguration["orm"]["cache"] : false;
    /*
     * doctrine log configuration
     */
    $log = isset($doctrineConfiguration["log"]) ? $doctrineConfiguration["log"] : false;
    /*
     * service logger configuration
     */
    $logger = !isset($doctrineConfiguration['logger']) ? : $doctrineConfiguration['logger'];

    /*
     * default query cache & meta chache
     */
    $metaCache = $this->getCache();
    $queryCache = $this->getCache();

    //handle cache
    $this->handleCache($metaCache, $queryCache, $cache, $debug);
    //Handle logs
    $this->handleLogs($config, $log, $logger);

    //set caches
    $config->setMetadataCacheImpl($metaCache);
    $config->setQueryCacheImpl($queryCache);

    //define autoregeneration of proxies base on debug mode
    $config->setAutoGenerateProxyClasses($debug);

    $chainDriverImpl = new \Doctrine\ORM\Mapping\Driver\DriverChain();

    $driverYaml = new \Doctrine\ORM\Mapping\Driver\YamlDriver(
                    array(__DIR__ . '/../../../../conf.d/Doctrine')
    );

    $chainDriverImpl->addDriver($driverYaml, 'Entities');

    $chainDriverImpl->addDriver($driverYaml, 'Gedmo\Timestampable');

    $config->setMetadataDriverImpl($chainDriverImpl);

    $config->setProxyDir(realpath(__DIR__ . '/../../../../Doctrine/Proxies'));

    $config->setProxyNamespace('Proxies');

    $dbalConf = isset($doctrineConfiguration["dbal"]) ? $doctrineConfiguration["dbal"] : false;

    if (!$dbalConf)
    {
      throw new \Exception("Unable to read dbal configuration");
    }

    $evm = new \Doctrine\Common\EventManager();

    $evm->addEventSubscriber(new \Gedmo\Timestampable\TimestampableListener());

    $this->entityManager = \Doctrine\ORM\EntityManager::create($dbalConf, $config, $evm);

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
                    'Doctrine\Common\DataFixtures'
                    , realpath(__DIR__ . '/../../../../vendor/data-fixtures/lib')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'PhraseaFixture'
                    , realpath(__DIR__ . '/../../../../conf.d/')
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

    if(!Type::hasType('blob'))
      Type::addType('blob', 'Types\Blob');
    if(!Type::hasType('enum'))
      Type::addType('enum', 'Types\Enum');
    if(!Type::hasType('longblob'))
      Type::addType('longblob', 'Types\LongBlob');
    if(!Type::hasType('varbinary'))
      Type::addType('varbinary', 'Types\VarBinary');

    $platform->registerDoctrineTypeMapping('enum', 'enum');
    $platform->registerDoctrineTypeMapping('blob', 'blob');
    $platform->registerDoctrineTypeMapping('longblob', 'longblob');
    $platform->registerDoctrineTypeMapping('varbinary', 'varbinary');

    return;
  }

  /**
   * Return a cache object according to the $name
   * 
   * @param type $cacheName 
   */
  private function getCache($cacheName = self::ARRAYCACHE)
  {
    switch ($cacheName)
    {
      case self::MEMCACHED:
        $cache = new \Doctrine\Common\Cache\MemcacheCache();
        break;
      case self::APC:
        $cache = new \Doctrine\Common\Cache\ApcCache();
        break;
      case self::ARRAYCACHE:
      default:
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        break;
    }

    return $cache;
  }

  /**
   * Handle Cache configuration
   * 
   * @param AbstractCache $metaCache
   * @param AbstractCache $queryCache
   * @param type $cache
   * @param type $debug 
   */
  private function handleCache(AbstractCache &$metaCache, AbstractCache &$queryCache, $cache, $debug)
  {
    if ($cache && !$debug)
    {
      //define query cache
      $cacheName = isset($cache["query"]) ? $cache["query"] : self::ARRAYCACHE;
      $queryCache = $this->getCache($cacheName);

      //define metadatas cache
      $cacheName = isset($cache["metadata"]) ? $cache["metadata"] : self::ARRAYCACHE;
      $metaCache = $this->getCache($cacheName);
    }
  }

  /**
   * Handle logs configuration
   * 
   * @param \Doctrine\ORM\Configuration $config
   * @param type $log
   * @param type $logger 
   */
  private function handleLogs(\Doctrine\ORM\Configuration &$config, $log, $logger)
  {
    $logEnable = isset($log["enable"]) ? !!$log["enable"] : false;
    
    if ($logEnable)
    {
      $loggerService = isset($log["type"]) ? $log["type"] : '';

      switch ($loggerService)
      {
        case 'monolog':
          //defaut to main handler
          $doctrineHandler = isset($log["handler"]) ? $log["handler"] : 'main';

          if (!isset($logger["handlers"]))
          {
            throw new \Exception("You must specify at least on monolog handler");
          }

          if (!array_key_exists($doctrineHandler, $logger["handlers"]))
          {
            throw new \Exception(sprintf('Unknow monolog handler %s'), $handlerType);
          }

          $handlerName = ucfirst($logger["handlers"][$doctrineHandler]["type"]);

          $handlerClassName = sprintf('\Monolog\Handler\%sHandler', $handlerName);

          if (!class_exists($handlerClassName))
          {
            throw new \Exception(sprintf('Unknow monolog handler class %s', $handlerClassName));
          }
          
          if (!isset($log["filename"]))
          {
            throw new \Exception('you must specify a file to write "filename: my_filename"');
          }

          $logPath = __DIR__ . '/../../../../../logs';
          $file = sprintf('%s/%s', $logPath, $log["filename"]);
          
          if ($doctrineHandler == 'rotate')
          {
            $maxDay = isset($log["max_day"]) ? (int) $log["max_day"] : false;
            
            if(!$maxDay && isset($logger["handlers"]['rotate']["max_day"]))
            {
              $maxDay = (int) $logger["handlers"]['rotate']["max_day"];
            }
            else
            {
              $maxDay = 10;
            }
            $handlerInstance = new $handlerClassName($file, $maxDay);
          }
          else
          {
            $handlerInstance = new $handlerClassName($file);
          }
          
          $monologLogger = new \Monolog\Logger('query-logger');
          $monologLogger->pushHandler($handlerInstance);

          if (isset($log["output"]))
          {
            $output = $log["output"];
          }
          elseif (isset($logger["output"]))
          {
            $output = $logger["output"];
          }
          else
          {
            $output = null;
          }

          if (null === $output)
          {
            $sqlLogger = new \Doctrine\Logger\MonologSQLLogger($monologLogger);
          }
          else
          {
            $sqlLogger = new \Doctrine\Logger\MonologSQLLogger($monologLogger, $output);
          }

          $config->setSQLLogger($sqlLogger);
          break;
        case 'echo':
        default:
          $config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
          break;
      }
    }
  }

}