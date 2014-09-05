<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Process;

class IniReset extends Command
{
    public function __construct()
    {
        parent::__construct('ini:reset');

        $this->setDescription('Reset environment')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Databox name to reset, in case of multiple databox are mounted', null)
            ->addOption('dependencies', null, InputOption::VALUE_NONE, 'Fetch dependencies', null)
            ->addOption('v3.1', null, InputOption::VALUE_NONE, 'Reset with v3.1 fixtures', null)
            ->addOption('uninstall', null, InputOption::VALUE_NONE, 'Uninstall Phraseanet', null);
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->container['phraseanet.configuration']->isSetup()) {
            throw new RuntimeException(sprintf(
                'Phraseanet is not setup. You can run <info>bin/setup system::install</info> command to install Phraseanet.'
            ));
        }

        // get dbs
        $conf = $this->container['phraseanet.configuration']->getConfig();
        $dbs = array('ab' => $conf['main']['database']['dbname'], 'dbs' => array(), 'setup_dbs' => array());
        foreach($this->container['phraseanet.appbox']->get_databoxes() as $databox) {
            $dbs['dbs'][] = $databox->get_dbname();
        }

        //uninstall
        if ($input->getOption('uninstall')) {
            $command = $this->getApplication()->find('system:uninstall');

            $output->writeln('Uninstalling...<info>OK</info>');
            $input = new ArrayInput(array(
                'command' => 'system:uninstall'
            ));
            $command->run($input, $output);
        }

        //run composer
        //run bower
        if ($input->getOption('dependencies')) {
            $command = $this->getApplication()->find('dependencies:composer');

            $input = new ArrayInput(array(
                'command' => 'dependencies:composer'
            ));
            $command->run($input, $output);

            $command = $this->getApplication()->find('dependencies:bower');

            $input = new ArrayInput(array(
                'command' => 'dependencies:bower'
            ));
            $command->run($input, $output);
        }

        if (count($dbs['dbs']) > 1) {
            if ($input->getOption('name')) {
                $dbName = $input->getOption('name');
            } else {
                $dialog = $this->getHelperSet()->get('dialog');
                $dbName = $dialog->ask(
                    $output,
                    _('Please enter the databox name to reset')
                );
            }
        } else  {
            $dbName = current($dbs['dbs']);
        }

        $schema = $this->container['EM']->getConnection()->getSchemaManager();
        $output->writeln('Creating database "'.$dbs['ab'].'"...<info>OK</info>');
        $schema->dropAndCreateDatabase($dbs['ab']);
        $output->writeln('Creating database "'.$dbName.'"...<info>OK</info>');
        $schema->dropAndCreateDatabase($dbName);

        // inject v3.1 fixtures
        if ($input->getOption('v3.1')) {
            $this->container['filesystem']->copy($this->container['root.path'].'/resources/hudson/connexion.inc', $this->container['root.path'].'/config/connexion.inc');
            $this->container['filesystem']->copy($this->container['root.path'].'/resources//hudson/_GV.php', $this->container['root.path'].'/config/_GV.php');

            $command = $this->getApplication()->find('dbal:import');

            $content = file_get_contents($this->container['root.path'] . '/resources//hudson/fixtures.sql');
            $content = str_replace('ab_test', $dbs['ab'], $content);
            $content = str_replace('db_test', $dbName, $content);

            $tmpFile = tempnam(sys_get_temp_dir(), 'fixtures-v3.1-');
            $this->container['filesystem']->dumpFile($tmpFile, $content);

            $input = new ArrayInput(array(
                'command' => 'dbal:import',
                'file' => $tmpFile
            ));

            $verbosity = $output->getVerbosity();
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
            $command->run($input, $output);
            $output->setVerbosity($verbosity);
            $output->writeln('Importing Phraseanet v3.1 fixtures...<info>OK</info>');
        }

        // create setup dbs
        $command = $this->getApplication()->find('ini:setup-tests-dbs');
        $input = new ArrayInput(array(
            'command' => 'ini:setup-tests-dbs'
        ));
        $command->run($input, $output);

        return 0;
    }
}
