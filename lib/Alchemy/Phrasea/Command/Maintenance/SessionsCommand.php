<?php

namespace Alchemy\Phrasea\Command\Maintenance;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Model\Entities\Session;
use Alchemy\Phrasea\Model\Entities\SessionModule;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SessionsCommand extends Command
{
    /** @var EntityManager */
    private $em;

    public function __construct()
    {
        parent::__construct('sessions');

        $this
            ->setDescription('truncate, drop, create sessions')
            ->addOption('truncate', null, InputOption::VALUE_NONE, 'truncate tables sessions (= disconnect users)')
            ->addOption('drop', null, InputOption::VALUE_NONE, 'drop tables sessions (= prevent users from log-in)')
            ->addOption('create', null, InputOption::VALUE_NONE, 'create tables sessions (= repair after crash) ')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Answer yes to all questions')
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->container['orm.em'];
        $isInteractive = !$input->getOption('yes');

        //multiple options allowed

        if ($input->getOption('truncate')) {
            $this->truncateTable($output, $isInteractive);
        }

        if ($input->getOption('drop')) {
            $this->dropTable($output, $isInteractive);
        }

        if ($input->getOption('create')) {
            $this->createTable($output, $isInteractive);
        }
    }

    private function truncateTable(OutputInterface $output, $isInteractive)
    {
        if ($isInteractive) {
            $dialog = $this->getHelperSet()->get('dialog');

            do {
                $continue = mb_strtolower($dialog->ask($output, '<question> Do you want to truncate session tables ? (Y/n)</question>', 'Y'));
            } while (!in_array($continue, ['y', 'n']));
        } else {
            $continue = 'y';
        }

        if ($continue == 'y') {
            $connection = $this->container->getApplicationBox()->get_connection();
            $platform = $connection->getDatabasePlatform();
            $this->em->beginTransaction();

            try {
                $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0;');
                $connection->executeUpdate($platform->getTruncateTableSQL('SessionModules', true));
                $connection->executeUpdate($platform->getTruncateTableSQL('Sessions', true));
                $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1;');

                $this->em->commit();

                $output->writeln("Table <info>Sessions</info> and <info>SessionModules</info> successfully truncated!");
            } catch (\Exception $e) {
                $this->em->rollback();
                $output->writeln("<error>error when truncate table : </error>" . $e->getMessage());
            }
        } else {
            $output->writeln('<info>Canceled</info>');
        }
    }

    private function createTable(OutputInterface $output, $isInteractive)
    {
        if ($isInteractive) {
            $dialog = $this->getHelperSet()->get('dialog');

            do {
                $continue = mb_strtolower($dialog->ask($output, '<question> Do you want to create session tables ? (Y/n)</question>', 'Y'));
            } while (!in_array($continue, ['y', 'n']));
        } else {
            $continue = 'y';
        }

        if ($continue == 'y') {
            $tool = new SchemaTool($this->em);
            $connection = $this->container->getApplicationBox()->get_connection();
            $dbName = $this->container->getApplicationBox()->get_dbname();

            // check table if exist
            $sql = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :db_name AND TABLE_NAME = 'Sessions';";
            $stmt = $connection->prepare($sql);
            $stmt->execute([':db_name' => $dbName]);
            $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            /**********************/

            if (count($row) == 1) {
                $output->writeln("<error>Table Sessions already exist !</error>");
            } else {
                try {
                    $metadataS[] = $this->em->getMetadataFactory()->getMetadataFor(Session::class);
                    $tool->updateSchema($metadataS, true);

                    $output->writeln("Table <info>Sessions</info> successfully created ! ");
                } catch (\Exception $e) {
                    $output->writeln("<error>error when creating table : </error>" . $e->getMessage());
                }
            }

            // check table if exist
            $sql = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :db_name AND TABLE_NAME = 'SessionModules';";
            $stmt = $connection->prepare($sql);
            $stmt->execute([':db_name' => $dbName]);
            $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            /**********************/

            if (count($row) == 1) {
                $output->writeln("<error>Table SessionModules already exist !</error>");
            } else {
                try {
                    $metadataSM[] = $this->em->getMetadataFactory()->getMetadataFor(SessionModule::class);
                    $tool->updateSchema($metadataSM, true);

                    $output->writeln("Table <info>SessionModules</info> successfully created ! ");
                } catch (\Exception $e) {
                    $output->writeln("<error>error when creating table : </error>" . $e->getMessage());
                }
            }
        } else {
            $output->writeln('<info>Canceled</info>');
        }
    }

    private function dropTable(OutputInterface $output, $isInteractive)
    {
        if ($isInteractive) {
            $dialog = $this->getHelperSet()->get('dialog');

            do {
                $continue = mb_strtolower($dialog->ask($output, '<question> Do you want to drop session tables ? (Y/n)</question>', 'Y'));
            } while (!in_array($continue, ['y', 'n']));
        } else {
            $continue = 'y';
        }

        if ($continue == 'y') {
            try {
                $tool = new SchemaTool($this->em);
                $metadatas[] = $this->em->getMetadataFactory()->getMetadataFor(Session::class);
                $metadatas[] = $this->em->getMetadataFactory()->getMetadataFor(SessionModule::class);

                $tool->dropSchema($metadatas);

                $output->writeln("Table <info>Sessions</info> and <info>SessionModules</info> successfully dropped ! ");
            } catch(\Exception $e) {
                $output->writeln("<error>error when dropping table :  </error>" . $e->getMessage());
            }
        } else {
            $output->writeln('<info>Canceled</info>');
        }
    }
}
