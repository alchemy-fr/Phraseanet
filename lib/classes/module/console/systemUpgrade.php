<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @todo write tests
 *
 * @package     KonsoleKomander
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Alchemy\Phrasea\Command\Command;
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
            ->addOption('dump', 'd', InputOption::VALUE_NONE, 'Dumps SQL queries that can be used to clean database.');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
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
                $continue = mb_strtolower($dialog->ask($output, '<question>' . _('Continuer ?') . ' (Y/n)</question>', 'Y'));
            } while (!in_array($continue, ['y', 'n']));
        } else {
            $continue = 'y';
        }

        if ($continue == 'y') {
            $output->write('<info>Upgrading...</info>', true);

            try {
                $upgrader = new Setup_Upgrade($this->container, $input->getOption('force'));
                $queries = $this->getService('phraseanet.appbox')->forceUpgrade($upgrader, $this->container);
            } catch (\Exception_Setup_FixBadEmailAddresses $e) {
                return $output->writeln(sprintf('<error>You have to fix your database before upgrade with the system:mailCheck command </error>'));
            } catch (\Exception $e) {
                $output->write('<info>'.$e->getMessage().'</info>', true);
                var_dump($e->getTraceAsString());
            }



            if ($input->getOption('dump')) {
                if (0 < count($queries)) {
                    $output->writeln("Some SQL queries can be executed to optimize\n");

                    foreach ($queries as $query) {
                        $output->writeln(" ".$query['sql']);
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
        } else {
            $output->write('<info>Canceled</info>', true);
        }
        $output->write('Finished !', true);

        return 0;
    }
}
