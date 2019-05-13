<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Core\Version;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Utilities\StringHelper;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use vierbergenlars\SemVer\version as SemVer;

class IniReset extends Command
{
    public function __construct()
    {
        parent::__construct('ini:reset');

        $this->setDescription('Reset environment')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Answers yes to all questions and do not ask the user')
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Admin e-mail address', null)
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Admin password', null)
            ->addOption('db-name', null, InputOption::VALUE_OPTIONAL, 'Databox name to reset, in case of multiple databox are mounted', null)
            ->addOption('dependencies', null, InputOption::VALUE_NONE, 'Fetch dependencies', null)
            ->addOption('run-patches', null, InputOption::VALUE_NONE, 'Reset in v3.1 states & apply all patches', null)
            ->addOption('no-setup-dbs', null, InputOption::VALUE_NONE, 'Do not create dbs for setup tests used in phpunit test suite', null)
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $interactive = !$input->getOption('yes');
        $dialog = $this->getHelperSet()->get('dialog');

        if (!$this->container['phraseanet.configuration']->isSetup()) {
            throw new RuntimeException(sprintf(
                'Phraseanet is not setup. You can run <info>bin/setup system::install</info> command to install Phraseanet.'
            ));
        }

        // get dbs
        $conf = $this->container['phraseanet.configuration']->getConfig();
        $dbs = array('ab' => $conf['main']['database']['dbname'], 'dbs' => array(), 'setup_dbs' => array());
        foreach($this->container->getDataboxes() as $databox) {
            $dbs['dbs'][] = $databox;
        }

        if (count($dbs['dbs']) > 1) {
            if ($input->getOption('db-name')) {
                $dbName = $input->getOption('db-name');
            } else {
                $dialog = $this->getHelperSet()->get('dialog');
                $dbName = $dialog->ask(
                    $output,
                    $this->container['translator']->trans('Please enter the databox name to reset or create')
                );
            }
        } else if ($input->getOption('db-name')) {
            $dbName = $input->getOption('db-name');
        } else  {
            $dbName = current($dbs['dbs'])->get_dbname();
        }

        $continue = 'y';
        if (count($dbs['dbs']) > 1 && in_array($dbName, array_map(function(\base $db) { return $db->get_dbname();}, $dbs['dbs']))) {
            if ($interactive) {
                do {
                    $continue = mb_strtolower($dialog->ask($output, '<question>' .$dbName.' database is going to be truncated, do you want to continue ? (Y/n)</question>', 'Y'));
                } while (!in_array($continue, array('y', 'n')));
            }
        }

        if ('y' !== $continue) {
            return;
        }

        $unmountedDbs = $dbToMount = array_diff(array_map(function(\base $db) { return $db->get_dbname();}, $dbs['dbs']), array($dbName));

        if (count($unmountedDbs) > 1 && $interactive) {
            array_unshift($unmountedDbs, 'all');
            $selected = $dialog->select(
                $output,
                'Choose Dbs to mount',
                $unmountedDbs,
                0,
                false,
                'Invalid choice',
                true
            );

            $dbToMount = array_map(function($c) use ($unmountedDbs) {
                return $unmountedDbs[$c];
            }, $selected);
        }

        if ($input->getOption('dependencies') || !SemVer::eq($this->container->getApplicationBox()->get_version(), $this->container['phraseanet.version']->getNumber())) {
            $this->getApplication()->find('dependencies:all')->run( new ArrayInput(array(
                'command' => 'dependencies:all'
            )), $output);
        }

        // get data paths
        $dataPath = $this->container['conf']->get(['main', 'storage', 'subdefs'], $this->container['root.path'].'/datas');

        /** @var Connection $connection */
        $connection = $this->container['orm.em']->getConnection();
        $schema = $connection->getSchemaManager();

        $output->writeln('Creating database "'.$dbs['ab'].'"...<info>OK</info>');
        $schema->dropAndCreateDatabase(StringHelper::SqlQuote($dbs['ab'], StringHelper::SQL_IDENTIFIER));

        $output->writeln('Creating database "'.$dbName.'"...<info>OK</info>');
        $schema->dropAndCreateDatabase(StringHelper::SqlQuote($dbName, StringHelper::SQL_IDENTIFIER));

        // inject v3.1 fixtures
        if ($input->getOption('run-patches')) {
            $content = file_get_contents($this->container['root.path'].'/resources/hudson/connexion.inc');

            $content = str_replace('{{dbname}}', $conf['main']['database']['dbname'], $content);
            $content = str_replace('{{hostname}}', $conf['main']['database']['host'], $content);
            $content = str_replace('{{port}}', $conf['main']['database']['port'], $content);
            $content = str_replace('{{user}}', $conf['main']['database']['user'], $content);
            $content = str_replace('{{password}}', $conf['main']['database']['password'], $content);

            $tmpFile = tempnam(sys_get_temp_dir(), 'connexion.inc-v3.1-');
            $this->container['filesystem']->dumpFile($tmpFile, $content);

            $this->container['filesystem']->copy($tmpFile, $this->container['root.path'].'/config/connexion.inc');
            $this->container['filesystem']->copy($this->container['root.path'].'/resources/hudson/_GV.php', $this->container['root.path'].'/config/_GV.php');

            $content = file_get_contents($this->container['root.path'] . '/resources/hudson/fixtures.sql');
            $content = str_replace('{{APPLICATION_BOX}}', $dbs['ab'], $content);
            $content = str_replace('{{DATA_BOX}}', $dbName, $content);
            $content = str_replace('{{DB_HOST}}', $conf['main']['database']['host'], $content);
            $content = str_replace('{{DB_PORT}}', $conf['main']['database']['port'], $content);
            $content = str_replace('{{DB_USER}}', $conf['main']['database']['user'], $content);
            $content = str_replace('{{DB_PASSWORD}}', $conf['main']['database']['password'], $content);
            $content = str_replace('{{DATA_BOX}}', $dbName, $content);
            $content = str_replace('{{USER_EMAIL}}', $input->getOption('email'), $content);
            $content = str_replace('{{USER_PASSWORD}}', hash('sha256', $input->getOption('password')), $content);

            $tmpFile = tempnam(sys_get_temp_dir(), 'fixtures-v3.1-');
            $this->container['filesystem']->dumpFile($tmpFile, $content);

            $verbosity = $output->getVerbosity();
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
            $this->getApplication()->find('dbal:import')->run(new ArrayInput(array(
                'command' => 'dbal:import',
                'file' => $tmpFile
            )), $output);
            $output->setVerbosity($verbosity);
            $output->writeln('Importing Phraseanet v3.1 fixtures...<info>OK</info>');
        } else {
            $this->getApplication()->find('system:uninstall')->run(new ArrayInput(array(
                'command' => 'system:uninstall'
            )), $output);

            $cmd = sprintf('php ' . __DIR__ . '/../../../../../bin/setup system:install --email=%s --password=%s --db-user=%s --db-template=%s --db-password=%s --databox=%s --appbox=%s --server-name=%s --db-host=%s --db-port=%s -y',
                $input->getOption('email'),
                $input->getOption('password'),
                $conf['main']['database']['user'],
                'en',
                $conf['main']['database']['password'],
                $dbName,
                $dbs['ab'],
                $conf['servername'],
                $conf['main']['database']['host'],
                $conf['main']['database']['port']
            );
            $process = new Process($cmd);
            $process->setTimeout(300);
            $process->run(function ($type, $buffer) {
                if ('err' === $type) {
                    echo 'ERR > ' . $buffer;
                }
            });
            if (false === $process->isSuccessful()) {
                $output->writeln('<error>Failed to execute the following command "'.$cmd.'"</error>');

                return 1;
            }
            $output->writeln("<info>Install successful !</info>");
        }

        foreach ($dbs['dbs'] as $databox) {
            if (!in_array($databox->get_dbname(), $dbToMount) && !in_array('all', $dbToMount)) {
                continue;
            }
            $credentials = $databox->get_connection()->get_credentials();

            \databox::mount(
                $this->container,
                $credentials['hostname'],
                $credentials['port'],
                $credentials['user'],
                $credentials['password'],
                $databox->get_dbname()
            );
            $output->writeln('Mounting database "'.$databox->get_dbname().'"...<info>OK</info>');
        }

        if ($input->getOption('run-patches') || false === $this->container['phraseanet.configuration']->isUpToDate()) {
            $version = new Version();
            if ($input->getOption('run-patches')) {
                $output->write(sprintf('Upgrading... from version <info>3.1.21</info> to <info>%s</info>', $version->getNumber()), true);
            } else {
                $output->write(sprintf('Upgrading... from version <info>%s</info> to <info>%s</info>', $this->app->getApplicationBox()->get_version(), $version->getNumber()), true);
            }

            $cmd = 'php ' . __DIR__ . '/../../../../../bin/setup system:upgrade -y -f -v';
            $process = new Process($cmd);
            $process->setTimeout(600);
            $process->run(function ($type, $buffer) {
                if ('err' === $type) {
                    echo 'ERR > ' . $buffer;
                }
            })
            ;
            if (false === $process->isSuccessful()) {
                $output->writeln('<error>Failed to execute the following command "' . $cmd . '"</error>');

                return 1;
            }
        }


        if (!$input->getOption('no-setup-dbs')) {
            // create setup dbs
            $command = $this->getApplication()->find('ini:setup-tests-dbs');
            $input = new ArrayInput(array(
                'command' => 'ini:setup-tests-dbs'
            ));
            $command->run($input, $output);
        }

        $this->container['conf']->set(['main', 'storage', 'subdefs'], $dataPath);

        return 0;
    }
}
