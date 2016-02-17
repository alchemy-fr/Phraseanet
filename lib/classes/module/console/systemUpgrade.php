<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Core\Version;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class module_console_systemUpgrade extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this
            ->setDescription('Upgrades Phraseanet to the latest version')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Answers yes to all questions and do not ask the user')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces the upgrade even if there is a concurrent upgrade')
            ->addOption('dump', 'd', InputOption::VALUE_NONE, 'Dumps SQL queries that can be used to clean database.')
            ->addOption('stderr', 's', InputOption::VALUE_NONE, 'Dumps SQL queries to stderr');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $pluginAutoloader = rtrim($this->container['root.path'], '\\/') . '/plugins/autoload.php';

        if (file_exists($pluginAutoloader)) {
            require_once $pluginAutoloader;

            $serviceProvider = new \Alchemy\Phrasea\Core\Provider\PluginServiceProvider();
            $serviceProvider->register($this->getContainer());
            $serviceProvider->boot($this->getContainer());
        }

        $this->getContainer()->loadPlugins();

        $interactive = !$input->getOption('yes');

        while ($migrations = $this->container['phraseanet.configuration-tester']->getMigrations()) {
            foreach ($migrations as $migration) {
                $migration->migrate();
            }
        }

        if (!$this->getService('phraseanet.configuration-tester')->isInstalled()) {
            throw new \RuntimeException('Phraseanet must be set-up');
        }

        $output->write('Phraseanet is going to be upgraded', true);

        if ($interactive) {
            $dialog = $this->getHelperSet()->get('dialog');

            do {
                $continue = mb_strtolower($dialog->ask($output, '<question>' . $this->container->trans('Continuer ?') . ' (Y/n)</question>', 'Y'));
            } while (!in_array($continue, ['y', 'n']));
        } else {
            $continue = 'y';
        }

        if ($continue == 'y') {
            $version = new Version();
            $output->write(sprintf('Upgrading... from version <info>%s</info> to <info>%s</info>', $this->container->getApplicationBox()->get_version(), $version->getNumber()), true);

            try {
                $upgrader = new Setup_Upgrade($this->container, $input->getOption('force'));
            } catch (\Exception_Setup_FixBadEmailAddresses $e) {
                return $output->writeln(sprintf('<error>You have to fix your database before upgrade with the system:mailCheck command </error>'));
            }

            $queries = $this->getService('phraseanet.appbox')->forceUpgrade($upgrader, $this->container);

            if ($input->getOption('dump') || $input->getOption('stderr')) {
                if (0 < count($queries)) {
                    $output->writeln("Some SQL queries can be executed to optimize\n");

                    $stderr = $input->getOption('stderr');

                    if ($stderr) {
                        $handle = fopen('php://stderr', 'a');
                    }
                    foreach ($queries as $query) {
                        if ($stderr) {
                            fwrite($handle, $query['sql']."\n");
                        } else {
                            $output->writeln(" ".$query['sql']);
                        }
                    }
                    if ($stderr) {
                        fclose ($handle);
                    }

                    $output->writeln("\n");
                } else {
                    $output->writeln("No SQL queries to execute to optimize\n");
                }
            }

            foreach ($upgrader->getRecommendations() as $recommendation) {
                list($message, $command) = $recommendation;

                $output->writeln(sprintf('<info>%s</info>', $message));
                $output->writeln("");
                $output->writeln(sprintf("\t\t%s", $command));
                $output->writeln("");
                $output->writeln("");
            }

            if (null !== $this->getApplication()) {
                $command = $this->getApplication()->find('crossdomain:generate');
                $command->run(new ArrayInput(array(
                    'command' => 'crossdomain:generate'
                )), $output);
            }
        } else {
            $output->write('<info>Canceled</info>', true);
        }
        $output->write('Finished !', true);

        return 0;
    }
}
