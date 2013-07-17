<?php

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManager;
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

            $config = Setup::createYAMLMetadataConfiguration(array(__DIR__ . '/../../../../conf.d/Doctrine'), true);
            $em = EntityManager::create($dbParams, $config);

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
