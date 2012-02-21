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
use Doctrine\DBAL\Types\Type;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Events\Listener\Cache\Action\Clear as ClearCacheListener;
use Doctrine\ORM\Events as DoctrineEvents;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Doctrine extends ServiceAbstract implements ServiceInterface
{

  const ARRAYCACHE = 'array';
  const MEMCACHE   = 'memcache';
  const XCACHE     = 'xcache';
  const REDIS      = 'redis';
  const APC        = 'apc';

  protected $caches = array(
    self::MEMCACHE, self::APC, self::ARRAYCACHE, self::XCACHE, self::REDIS
  );
  protected $outputs = array(
    'json', 'yaml', 'vdump'
  );
  protected $loggers = array(
    'Log\\Doctrine\Monolog', 'Log\\Doctrine\\Phpecho'
  );
  protected $entityManager;
  protected $cacheServices = array();
  protected $debug;

  public function __construct(Core $core, $name, Array $options)
  {
    parent::__construct( $core, $name, $options);

    $config = new \Doctrine\ORM\Configuration();

    $this->debug = !!$options["debug"];

    $logServiceName = isset($options["log"]['service']) ? $options["log"]['service'] : false;

    if ($logServiceName)
    {
      $config->setSQLLogger($this->getLog($logServiceName));
    }

    //get cache
    $cache = isset($options["orm"]["cache"]) ? $options["orm"]["cache"] : false;

    if (!$cache || $this->debug)
    {
      $metaCache   = $this->core['CacheService']->get('ORMmetadata', 'Cache\\ArrayCache');
      $queryCache  = $this->core['CacheService']->get('ORMquery', 'Cache\\ArrayCache');
      $resultCache = $this->core['CacheService']->get('ORMresult', 'Cache\\ArrayCache');
    }
    else
    {
      $query   = isset($cache["query"]['service']) ? $cache["query"]['service'] : 'Cache\\ArrayCache';
      $meta    = isset($cache["metadata"]['service']) ? $cache["metadata"]['service'] : 'Cache\\ArrayCache';
      $results = isset($cache["result"]['service']) ? $cache["result"]['service'] : 'Cache\\ArrayCache';

      $queryCache  = $this->core['CacheService']->get('ORMquery', $query);
      $metaCache   = $this->core['CacheService']->get('ORMmetadata', $meta);
      $resultCache = $this->core['CacheService']->get('ORMresult', $results);
    }

    $config->setMetadataCacheImpl($metaCache->getDriver());

    $config->setQueryCacheImpl($queryCache->getDriver());

    $config->setResultCacheImpl($resultCache->getDriver());


    //define autoregeneration of proxies base on debug mode
    $config->setAutoGenerateProxyClasses($this->debug);

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
      $dbalConf = $this->core->getConfiguration()->getConnexion($connexion)->all();
    }
    catch (\Exception $e)
    {
      $connexionFile = $this
        ->core->getConfiguration()
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

//    $evm->addEventListener(DoctrineEvents::postUpdate, new ClearCacheListener());
//    $evm->addEventListener(DoctrineEvents::postRemove, new ClearCacheListener());
//    $evm->addEventListener(DoctrineEvents::postPersist, new ClearCacheListener());


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
        'Events'
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

  private function getLog($serviceName)
  {
    try
    {
      $configuration = $this->core->getConfiguration()->getService($serviceName);
    }
    catch (\Exception $e)
    {
      $message = sprintf(
        "%s from %s service in orm:log scope"
        , $e->getMessage()
        , $this->name
      );
      $e       = new \Exception($message);
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

    $service = Core\Service\Builder::create($this->core, $serviceName, $configuration);

    return $service->getDriver();
  }

  public function getDriver()
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

  public static function getMandatoryOptions()
  {
    return array('debug', 'dbal');
  }

}
