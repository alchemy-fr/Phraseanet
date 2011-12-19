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
class DoctrineTestSQLite
{

  protected $entityManager;

  public function __construct()
  {

    require_once __DIR__ . '/../../../../vendor/doctrine2-orm/lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

    static::loadClasses();

    $config = new \Doctrine\ORM\Configuration();

    $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());

    $chainDriverImpl = new \Doctrine\ORM\Mapping\Driver\DriverChain();

    $driverYaml = new \Doctrine\ORM\Mapping\Driver\YamlDriver(
                    array(__DIR__ . '/../../../../conf.d/Doctrine')
    );

    $chainDriverImpl->addDriver($driverYaml, 'Entities');

    $chainDriverImpl->addDriver($driverYaml, 'Gedmo\Timestampable');

    $config->setMetadataDriverImpl($chainDriverImpl);

    $config->setProxyDir(realpath(__DIR__ . '/../../../../Doctrine/Proxies'));

    $config->setProxyNamespace('Proxies');

    $evm = new \Doctrine\Common\EventManager();

    $evm->addEventSubscriber(new \Gedmo\Timestampable\TimestampableListener());

    $connectionOptions = array(
        'driver' => 'pdo_sqlite',
        'path' => __DIR__ . '/../../../../unitTest/tests.sqlite',
    );

    $this->entityManager = \Doctrine\ORM\EntityManager::create($connectionOptions, $config, $evm);

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