<?php

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Configuration as ORMConfiguration;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\FileCacheReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class RegenerateSqliteDb extends Command
{
    public function __construct()
    {
        parent::__construct('phraseanet:regenerate-sqlite');

        $this->setDescription("Updates the sqlite 'tests/db-ref.sqlite' database with current database definition.");
    }

    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();

        $source = __DIR__ . '/../../../../../tests/db-ref.sqlite';
        $target = __DIR__ . '/../../../../../tests/db-ref.sqlite.bkp';

        $fs->rename($source, $target);

        try {
            $dbParams = $this->container['phraseanet.configuration']->getTestConnectionParameters();
            $dbParams['path'] = $source;

            $config = new ORMConfiguration();
            AnnotationRegistry::registerFile(
                $this->container['root.path'].'/vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'
            );

            $annotationReader = new AnnotationReader();
            $driverChain = new DriverChain();
            $annotationDriver = new AnnotationDriver(
                $annotationReader,
                array($this->container['root.path'].'/lib/Doctrine/Entities')
            );
            $driverChain->addDriver($annotationDriver, 'Entities');

            $config->setAutoGenerateProxyClasses(true);
            $config->setMetadataDriverImpl($driverChain);
            $config->setProxyDir($this->container['root.path'] . '/lib/Doctrine/Proxies');
            $config->setProxyNamespace('Proxies');

            $em = EntityManager::create($dbParams, $config, new EventManager());

            $metadatas = $em->getMetadataFactory()->getAllMetadata();
            $schemaTool = new SchemaTool($em);
            $schemaTool->createSchema($metadatas);
        } catch (\Exception $e) {
            $fs->rename($target, $source);
            throw $e;
        }

        $fs->remove($target);

        return 0;
    }
}
