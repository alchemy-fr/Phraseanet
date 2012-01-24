<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\Orm;

use Alchemy\Phrasea\Core,
    Alchemy\Phrasea\Core\Service,
    Alchemy\Phrasea\Core\Service\ServiceAbstract,
    Alchemy\Phrasea\Core\Service\ServiceInterface;
use Doctrine\DBAL\Types\Type,
    Doctrine\Common\Cache\AbstractCache;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Doctrine extends ServiceAbstract implements ServiceInterface
{

  const ARRAYCACHE = 'array';
  const MEMCACHE = 'memcache';
  const XCACHE = 'xcache';
  const APC = 'apc';

  protected $caches = array(
      self::MEMCACHE, self::APC, self::ARRAYCACHE, self::XCACHE
  );
  protected $outputs = array(
      'json', 'yaml', 'vdump'
  );
  protected $loggers = array(
      'monolog', 'phpecho'
  );
  protected $entityManager;
  protected $cacheServices = array();
  protected $debug;

  public function __construct($name, Array $options, Array $dependencies)
  {
    parent::__construct($name, $options, $dependencies);

    static::loadClasses();

    if (empty($options))
    {
      throw new \Exception(sprintf(
                      "'%s' service options can not be empty"
                      , $this->name
              )
      );
    }

    $config = new \Doctrine\ORM\Configuration();

    //get debug mod : default to false
    $debug = $this->debug = isset($options["debug"]) ? !!$options["debug"] : false;

    //get logger
    $logServiceName = isset($options["log"]) ? $options["log"] : false;

    if ($logServiceName)
    {
      //set logger
      $config->setSQLLogger($this->getLog($logServiceName));
    }

    //get cache
    $cache = isset($options["orm"]["cache"]) ? $options["orm"]["cache"] : false;

    if (!$cache)
    {
      $metaCache = $this->getCache('metadata');
      $queryCache = $this->getCache('query');
      $resultCache = $this->getCache('result');
    }
    else
    {
      //define query cache set to array cache if no defined or if service in on debug mode
      $queryCache = isset($cache["query"]) && !$debug ?
              $this->getCache('query', (string) $cache["query"]) :
              isset($cache["query"]) ?
                      $this->getCache('query', $cache["query"]) :
                      $this->getCache('query');

      //define metadatas cache set to array cache if no defined or if service in on debug mode
      $metaCache = isset($cache["metadata"]) && !$debug ?
              $this->getCache('metadata', (string) $cache["metadata"]) :
              isset($cache["metadata"]) ?
                      $this->getCache('metadata', $cache["metadata"]) :
                      $this->getCache('metadata');

      //define metadatas cache set to array cache if no defined or if service in on debug mode
      $resultCache = isset($cache["result"]) && !$debug ?
              $this->getCache('result', (string) $cache["result"]) :
              isset($cache["result"]) ?
                      $this->getCache('result', $cache["result"]) :
                      $this->getCache('result');
    }

    //set caches
    $config->setMetadataCacheImpl($metaCache);

    $config->setQueryCacheImpl($queryCache);

    $config->setResultCacheImpl($resultCache);

    //define autoregeneration of proxies base on debug mode
    $config->setAutoGenerateProxyClasses($debug);

    $chainDriverImpl = new \Doctrine\ORM\Mapping\Driver\DriverChain();

    $driverYaml = new \Doctrine\ORM\Mapping\Driver\YamlDriver(
                    array(__DIR__ . '/../../../../../conf.d/Doctrine')
    );

    $chainDriverImpl->addDriver($driverYaml, 'Entities');

    $chainDriverImpl->addDriver($driverYaml, 'Gedmo\Timestampable');

    $config->setMetadataDriverImpl($chainDriverImpl);

    $config->setProxyDir(realpath(__DIR__ . '/../../../../../Doctrine/Proxies'));

    $config->setProxyNamespace('Proxies');

    $connexion = isset($options["dbal"]) ? $options["dbal"] : false;
   
    if (!$connexion)
    {
      throw new \Exception(sprintf(
                      "Missing dbal configuration for '%s' service"
                      , $this->name
              )
      );
    }

    try
    {
      $dbalConf = $this->configuration->getConnexion($connexion)->all();
    }
    catch (\Exception $e)
    {
      $connexionFile = $this
              ->configuration
              ->getConfigurationHandler()
              ->getSpecification()
              ->getConnexionFile();

      throw new \Exception(sprintf(
                      "Connexion '%s' is not declared in %s"
                      , $connexion
                      , $connexionFile->getFileName()
              )
      );
    }

    $evm = new \Doctrine\Common\EventManager();

    $evm->addEventSubscriber(new \Gedmo\Timestampable\TimestampableListener());

    try
    {
      $this->entityManager = \Doctrine\ORM\EntityManager::create($dbalConf, $config, $evm);
    }
    catch (\Exception $e)
    {
      throw new \Exception(sprintf(
                      "Failed to create doctrine service for the following reason '%s'"
                      , $e->getMessage()
              )
      );
    }

    $this->addTypes();

    return $this;
  }

  public function getVersion()
  {
    return \Doctrine\Common\Version::VERSION;
  }

  protected static function loadClasses()
  {
    require_once __DIR__ . '/../../../../../vendor/doctrine2-orm/lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Doctrine\ORM'
                    , realpath(__DIR__ . '/../../../../../vendor/doctrine2-orm/lib')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Doctrine\DBAL'
                    , realpath(__DIR__ . '/../../../../../vendor/doctrine2-orm/lib/vendor/doctrine-dbal/lib')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Doctrine\Common'
                    , realpath(__DIR__ . '/../../../../../vendor/doctrine2-orm/lib/vendor/doctrine-common/lib')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Doctrine\Common\DataFixtures'
                    , realpath(__DIR__ . '/../../../../../vendor/data-fixtures/lib')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'PhraseaFixture'
                    , realpath(__DIR__ . '/../../../../../conf.d/')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Entities'
                    , realpath(__DIR__ . '/../../../../../Doctrine')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Repositories'
                    , realpath(__DIR__ . '/../../../../../Doctrine')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Proxies'
                    , realpath(__DIR__ . '/../../../../../Doctrine')
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Symfony'
                    , realpath(__DIR__ . '/../../../../vendor/doctrine2-orm/lib/vendor')
    );

    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Doctrine\Logger'
                    , realpath(__DIR__ . '/../../../../../../../')
    );

    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Monolog'
                    , realpath(__DIR__ . '/../../../../../vendor/Silex/vendor/monolog/src')
    );

    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Types'
                    , realpath(__DIR__ . '/../../../../../Doctrine')
    );

    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'Gedmo'
                    , __DIR__ . "/../../../../../vendor/doctrine2-gedmo/lib"
    );
    $classLoader->register();

    $classLoader = new \Doctrine\Common\ClassLoader(
                    'DoctrineExtensions'
                    , __DIR__ . "/../../../../../vendor/doctrine2-beberlei/lib"
    );
    $classLoader->register();

    return;
  }

  protected function addTypes()
  {

    $platform = $this->entityManager->getConnection()->getDatabasePlatform();

    if (!Type::hasType('blob'))
    {
      Type::addType('blob', 'Types\Blob');
    }

    if (!Type::hasType('enum'))
    {
      Type::addType('enum', 'Types\Enum');
    }

    if (!Type::hasType('longblob'))
    {
      Type::addType('longblob', 'Types\LongBlob');
    }

    if (!Type::hasType('varbinary'))
    {
      Type::addType('varbinary', 'Types\VarBinary');
    }

    $platform->registerDoctrineTypeMapping('enum', 'enum');
    $platform->registerDoctrineTypeMapping('blob', 'blob');
    $platform->registerDoctrineTypeMapping('longblob', 'longblob');
    $platform->registerDoctrineTypeMapping('varbinary', 'varbinary');

    return;
  }

  /**
   * 
   * 
   * @param type $cacheName 
   */
  private function getCache($cacheDoctrine, $serviceName = null)
  {
    if (null === $serviceName)
    {
      $serviceName = 'default_cache';
      $configuration = new ParameterBag(array(
                  'type' => self::ARRAYCACHE
                  , 'options' => array()
                      )
      );
    }
    else
    {
      try
      {
        $configuration = $this->configuration->getService($serviceName);
      }
      catch (\Exception $e)
      {
        $message = sprintf(
                "%s from %s service in orm:cache scope"
                , $e->getMessage()
                , $this->name
        );

        $e = new \Exception($message);
        throw $e;
      }
      $type = $configuration->get("type");

      if (!in_array($type, $this->caches))
      {
        throw new \Exception(sprintf(
                        "The cache type '%s' declared in %s  %s service is not valid. 
          Available types are %s."
                        , $type
                        , $this->name
                        , $this->getScope()
                        , implode(", ", $this->caches)
                )
        );
      }
    }

    $registry = $this->getDependency("registry");

    $serviceBuilder = new Core\ServiceBuilder\Cache(
                    $serviceName,
                    $configuration,
                    array("registry" => $registry)
    );

    $service = $serviceBuilder->buildService();

    $this->cacheServices[$cacheDoctrine] = $service;

    return $service->getService();
  }

  private function getLog($serviceName)
  {
    try
    {
      $configuration = $this->configuration->getService($serviceName);
    }
    catch (\Exception $e)
    {
      $message = sprintf(
              "%s from %s service in orm:log scope"
              , $e->getMessage()
              , $this->name
      );
      $e = new \Exception($message);
      throw $e;
    }

    $type = $configuration->get("type");

    if (!in_array($type, $this->loggers))
    {
      throw new \Exception(sprintf(
                      "The logger type '%s' declared in %s %s service is not valid. 
          Available types are %s."
                      , $type
                      , $this->name
                      , $this->getScope()
                      , implode(", ", $this->loggers)
              )
      );
    }

    $serviceBuilder = new Core\ServiceBuilder\Log(
                    $serviceName,
                    $configuration,
                    array(),
                    "Doctrine"
    );

    return $serviceBuilder->buildService()->getService();
  }

  public function getService()
  {
    return $this->entityManager;
  }

  public function getType()
  {
    return 'doctrine';
  }

  public function getScope()
  {
    return 'orm';
  }

  public function getCacheServices()
  {
    return new ParameterBag($this->cacheServices);
  }

  public function isDebug()
  {
    return $this->debug;
  }

}
