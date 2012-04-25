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

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Doctrine extends ServiceAbstract
{
    protected $loggers = array(
        'Log\\Doctrine\Monolog'
        , 'Log\\Doctrine\\Phpecho'
    );
    protected $entityManager;
    protected $debug;

    protected function init()
    {
        $options = $this->getOptions();

        $config = new \Doctrine\ORM\Configuration();

        $this->debug = ! ! $options["debug"];

        $logServiceName = isset($options["log"]['service']) ? $options["log"]['service'] : false;

        if ($logServiceName) {
            $config->setSQLLogger($this->getLog($logServiceName));
        }

        $cache = isset($options["cache"]) ? $options["cache"] : false;

        if ( ! $cache || $this->debug) {
            $metaCache = $this->core['CacheService']->get('ORMmetadata', 'Cache\\ArrayCache');
            $queryCache = $this->core['CacheService']->get('ORMquery', 'Cache\\ArrayCache');
        } else {
            $query = isset($cache["query"]['service']) ? $cache["query"]['service'] : 'Cache\\ArrayCache';
            $meta = isset($cache["metadata"]['service']) ? $cache["metadata"]['service'] : 'Cache\\ArrayCache';

            $queryCache = $this->core['CacheService']->get('ORMquery', $query);
            $metaCache = $this->core['CacheService']->get('ORMmetadata', $meta);
        }

        $resultCache = $this->core['CacheService']->get('ORMresult', 'Cache\\ArrayCache');

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

        if ( ! $connexion) {
            throw new \Exception(sprintf(
                    "Missing dbal configuration for '%s' service"
                    , __CLASS__
                )
            );
        }

        try {
            $dbalConf = $this->core->getConfiguration()->getConnexion($connexion)->all();
        } catch (\Exception $e) {
            throw new \Exception("Connexion '%s' is not declared");
        }

        $evm = new \Doctrine\Common\EventManager();

        $evm->addEventSubscriber(new \Gedmo\Timestampable\TimestampableListener());

        try {
            $this->entityManager = \Doctrine\ORM\EntityManager::create($dbalConf, $config, $evm);
        } catch (\Exception $e) {
            throw new \Exception(sprintf(
                    "Failed to create doctrine service for the following reason '%s'"
                    , $e->getMessage()
                )
            );
        }

        $this->addTypes();

        return $this;
    }

    protected function addTypes()
    {

        $platform = $this->entityManager->getConnection()->getDatabasePlatform();

        if ( ! Type::hasType('blob')) {
            Type::addType('blob', 'Types\Blob');
        }

        if ( ! Type::hasType('enum')) {
            Type::addType('enum', 'Types\Enum');
        }

        if ( ! Type::hasType('longblob')) {
            Type::addType('longblob', 'Types\LongBlob');
        }

        if ( ! Type::hasType('varbinary')) {
            Type::addType('varbinary', 'Types\VarBinary');
        }

        if ( ! Type::hasType('binary')) {
            Type::addType('binary', 'Types\Binary');
        }

        $platform->registerDoctrineTypeMapping('enum', 'enum');
        $platform->registerDoctrineTypeMapping('blob', 'blob');
        $platform->registerDoctrineTypeMapping('longblob', 'longblob');
        $platform->registerDoctrineTypeMapping('varbinary', 'varbinary');
        $platform->registerDoctrineTypeMapping('binary', 'binary');

        return;
    }

    private function getLog($serviceName)
    {
        try {
            $configuration = $this->core->getConfiguration()->getService($serviceName);
        } catch (\Exception $e) {
            $message = sprintf(
                "%s from %s service"
                , $e->getMessage()
                , __CLASS__
            );

            $e = new \Exception($message);

            throw $e;
        }

        $type = $configuration->get("type");

        if ( ! in_array($type, $this->loggers)) {
            throw new \Exception(sprintf(
                    "The logger type '%s' declared in %s service is not valid.
          Available types are %s."
                    , $type
                    , __CLASS__
                    , implode(", ", $this->loggers)
                )
            );
        }

        $service = Core\Service\Builder::create($this->core, $configuration);

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

    public function isDebug()
    {
        return $this->debug;
    }

    public function getMandatoryOptions()
    {
        return array('debug', 'dbal');
    }
}
