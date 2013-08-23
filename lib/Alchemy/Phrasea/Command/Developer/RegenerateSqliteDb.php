<?php

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Core\Provider\ORMServiceProvider;
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

        if (is_file($source)) {
            $fs->rename($source, $target);
        }

        try {
            $dbParams = $this->container['phraseanet.configuration']->getTestConnectionParameters();
            $dbParams['path'] = $source;

            $this->container->register(new ORMServiceProvider());
            $this->container['EM.dbal-conf'] = $dbParams;

            $metadatas = $this->container['EM']->getMetadataFactory()->getAllMetadata();
            $schemaTool = new SchemaTool($this->container['EM']);
            $schemaTool->createSchema($metadatas);
        } catch (\Exception $e) {
            $fs->rename($target, $source);
            throw $e;
        }

        $fs->remove($target);

        return 0;
    }
}
