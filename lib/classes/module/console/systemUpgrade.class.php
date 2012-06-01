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
use Symfony\Component\Console\Output\OutputInterface;

class module_console_systemUpgrade extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Upgrade Phraseanet to the lastest version');

        return $this;
    }

    public function requireSetup()
    {
        return false;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ( ! $this->checkSetup($output)) {

            return 1;
        }

        $Core = \bootstrap::getCore();

        if ( ! setup::is_installed()) {

            $output->writeln('This version of Phraseanet requires a config/config.yml, config/connexion.yml, config/service.yml');
            $output->writeln('Would you like it to be created based on your settings ?');

            $dialog = $this->getHelperSet()->get('dialog');
            do {
                $continue = mb_strtolower($dialog->ask($output, '<question>' . _('Create automatically') . ' (Y/n)</question>', 'y'));
            } while ( ! in_array($continue, array('y', 'n')));

            if ($continue == 'y') {
                try {
                    $connexionInc = new \SplFileInfo(__DIR__ . '/../../../../config/connexion.inc', true);
                    $configInc = new \SplFileInfo(__DIR__ . '/../../../../config/config.inc', true);

                    $Core->getConfiguration()->upgradeFromOldConf($configInc, $connexionInc);
                } catch (\Exception $e) {

                }
            } else {
                throw new RuntimeException('Phraseanet is not set up');
            }
        }

        require_once __DIR__ . '/../../../../lib/bootstrap.php';

        $output->write('Phraseanet is going to be upgraded', true);
        $dialog = $this->getHelperSet()->get('dialog');

        do {
            $continue = mb_strtolower($dialog->ask($output, '<question>' . _('Continuer ?') . ' (Y/n)</question>', 'Y'));
        } while ( ! in_array($continue, array('y', 'n')));


        if ($continue == 'y') {
            try {
                $Core = \bootstrap::getCore();
                $output->write('<info>Upgrading...</info>', true);
                $appbox = appbox::get_instance($Core);

                if (count(User_Adapter::get_wrong_email_users($appbox)) > 0) {
                    return $output->writeln(sprintf('<error>You have to fix your database before upgrade with the system:mailCheck command </error>'));
                }

                $upgrader = new Setup_Upgrade($appbox);
                $appbox->forceUpgrade($upgrader);
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
