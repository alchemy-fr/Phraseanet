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

/**
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Doctrine extends ServiceAbstract implements ServiceInterface
{
  const MEMCACHED = 'memcached';
  const ARRAYCACHE = 'array';
  const APC = 'apc';

  protected $outputs = array(
      'json', 'yaml', 'normal'
  );
  protected $loggers = array(
      'monolog', 'echo'
  );
  protected $entityManager;

  public function __construct($name, Array $options = array())
  {
    parent::__construct($name, $options);

    static::loadClasses();

    $config = new \Doctrine\ORM\Configuration();

    $handler = new Core\Configuration\Handler(
                    new Core\Configuration\Application(),
                    new Core\Configuration\Parser\Yaml()
    );

    $phraseaConfig = new Core\Configuration($handler);

    /*
     * debug mode
     */
    $debug = isset($options["debug"]) ? : false;
    /*
     * doctrine cache
     */
    $cache = isset($options["orm"]["cache"]) ? $options["orm"]["cache"] : false;

    /*
     * default query cache & meta chache
     */
    $metaCache = $this->getCache();
    $queryCache = $this->getCache();

    //Handle logs
    $logServiceName = isset($options["log"]) ? $options["log"] : false;

    if ($logServiceName)
    {
      $serviceConf = $phraseaConfig->getService($logServiceName);
      $this->handleLogs($config, $logServiceName, $serviceConf->all());
    }

    //handle cache
    $this->handleCache($metaCache, $queryCache, $cache, $debug);

    //set caches
    $config->setMetadataCacheImpl($metaCache);
    $config->setQueryCacheImpl($queryCache);

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

    if(!$connexion)
    {
      throw new \Exception("Missing dbal connexion for doctrine");
    }
    
    try
    {
      $dbalConf = $phraseaConfig->getConnexion($connexion)->all();
    }
    catch(\Exception $e)
    {
      throw new \Exception(sprintf("Unable to read %s configuration", $connexion));
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
  private function handleLogs(\Doctrine\ORM\Configuration &$config, $serviceName, Array $serviceConf)
  {
    $logType = $serviceConf['type'];
    $logService = null;

    switch ($logType)
    {
      case 'monolog':
        $logService = Core\ServiceBuilder::build(
                          $serviceName
                        , Core\ServiceBuilder::LOG
                        , $logType
                        , $serviceConf['options']
                        , 'doctrine'
        );
        break;
      case 'echo':
      default:
        $logService = Core\ServiceBuilder::build(
                        $serviceName
                        , Core\ServiceBuilder::LOG
                        , 'normal'
                        , array()
                        , 'doctrine'
        );
        break;
    }

    if ($logService instanceof Alchemy\Phrasea\Core\Service\ServiceAbstract)
    {
      $config->setSQLLogger($logService->getService());
    }
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
  
}
