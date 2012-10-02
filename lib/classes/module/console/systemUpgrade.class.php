<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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
            ->setDescription('Upgrade Phraseanet to the latest version')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Answer yes to all questions and do not ask the user')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the upgrade even if there is a concurrent upgrade');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        require_once dirname(__FILE__) . '/../../../../lib/bootstrap.php';

        $interactive = !$input->getOption('yes');

        while ($migrations = $this->container['phraseanet.configuration-tester']->getMigrations()) {
            foreach ($migrations as $migration) {
                echo get_class($migration) . "\n";
                $migration->migrate();
                echo get_class($migration) . " finished\n";
            }
        }

        if (!$this->getService('phraseanet.configuration')->isInstalled()) {
            throw new \RuntimeException('Phraseanet must be set-up');
        }

        $output->write('Phraseanet is going to be upgraded', true);

        if ($interactive) {
            $dialog = $this->getHelperSet()->get('dialog');

            do {
                $continue = mb_strtolower($dialog->ask($output, '<question>' . _('Continuer ?') . ' (Y/n)</question>', 'Y'));
            } while (!in_array($continue, array('y', 'n')));
        } else {
            $continue = 'y';
        }

        if ($continue == 'y') {
            try {
                $output->write('<info>Upgrading...</info>', true);

                $this->container['phraseanet.registry'] = new \Setup_Registry();

                if (count(User_Adapter::get_wrong_email_users($this->container)) > 0) {
                    return $output->writeln(sprintf('<error>You have to fix your database before upgrade with the system:mailCheck command </error>'));
                }

                $upgrader = new Setup_Upgrade($this->container, $input->getOption('force'));

                $this->getService('phraseanet.appbox')->forceUpgrade($upgrader, $this->container);

                $this->container['phraseanet.registry'] = new \registry($this->container);

                foreach ($upgrader->getRecommendations() as $recommendation) {
                    list($message, $command) = $recommendation;

                    $output->writeln(sprintf('<info>%s</info>', $message));
                    $output->writeln("");
                    $output->writeln(sprintf("\t\t%s", $command));
                    $output->writeln("");
                    $output->writeln("");
                }
            } catch (\Exception $e) {

                $output->writeln(sprintf('<error>An error occured while upgrading : %s </error>', $e->getMessage()));
            }
        } else {
            $output->write('<info>Canceled</info>', true);
        }
        $output->write('Finished !', true);

        return 0;
    }
}
