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
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class module_console_systemConfigCheck extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Check the configuration');

        return $this;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ( ! function_exists('_')) {
            $output->writeln('<error>YOU MUST ENABLE GETTEXT SUPPORT TO USE PHRASEANET</error>');
            $output->writeln('Canceled');

            return 1;
        }

        $ok = true;

        if (setup::is_installed()) {
            $registry = registry::get_instance();

            $output->writeln(_('*** CHECK BINARY CONFIGURATION ***'));
            $ok = $this->processConstraints(setup::check_binaries($registry), $output) && $ok;
            $output->writeln("");
        } else {
            $registry = new Setup_Registry();
        }



        $output->writeln(_('*** FILESYSTEM CONFIGURATION ***'));
        $ok = $this->processConstraints(setup::check_writability($registry), $output) && $ok;
        $output->writeln("");
        $output->writeln(_('*** CHECK CACHE OPCODE ***'));
        $ok = $this->processConstraints(setup::check_cache_opcode(), $output) && $ok;
        $output->writeln("");
        $output->writeln(_('*** CHECK CACHE SERVER ***'));
        $ok = $this->processConstraints(setup::check_cache_server(), $output) && $ok;
        $output->writeln("");
        $output->writeln(_('*** CHECK PHP CONFIGURATION ***'));
        $ok = $this->processConstraints(setup::check_php_configuration(), $output) && $ok;
        $output->writeln("");
        $output->writeln(_('*** CHECK PHP EXTENSIONS ***'));
        $ok = $this->processConstraints(setup::check_php_extension(), $output) && $ok;
        $output->writeln("");
        $output->writeln(_('*** CHECK PHRASEA ***'));
        $ok = $this->processConstraints(setup::check_phrasea(), $output) && $ok;
        $output->writeln("");
        $output->writeln(_('*** CHECK SYSTEM LOCALES ***'));
        $ok = $this->processConstraints(setup::check_system_locales(), $output) && $ok;
        $output->writeln("");

        $output->write('Finished !', true);

        return (int) ! $ok;
    }

    protected function processConstraints(Setup_ConstraintsIterator $constraints, OutputInterface &$output)
    {
        $hasError = false;
        foreach ($constraints as $constraint) {
            if ( ! $hasError && ! $this->processConstraint($constraint, $output)) {
                $hasError = true;
            }
        }

        return ! $hasError;
    }

    protected function processConstraint(Setup_Constraint $constraint, OutputInterface &$output)
    {
        $ok = true;
        if ($constraint->is_ok()) {
            $output->writeln("\t\t<info>" . $constraint->get_message() . '</info>');
        } elseif ($constraint->is_blocker()) {
            $output->writeln("\t!!!\t<error>" . $constraint->get_message() . '</error>');
            $ok = false;
        } else {
            $output->writeln("\t/!\\\t<comment>" . $constraint->get_message() . '</comment>');
        }

        return $ok;
    }
}
