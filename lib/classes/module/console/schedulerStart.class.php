<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
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

class module_console_schedulerStart extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Start the scheduler');
        $this->addOption(
            'nolog'
            , NULL
            , 1 | InputOption::VALUE_NONE
            , 'do not log (scheduler) to logfile'
            , NULL
        );
        $this->addOption(
            'notasklog'
            , NULL
            , 1 | InputOption::VALUE_NONE
            , 'do not log (tasks) to logfiles'
            , NULL
        );
        $this->setHelp(
            "You should use launch the command and finish it with `&`"
            . " to return to the console\n\n"
            . "\tie : <info>bin/console scheduler:start &</info>"
        );

        return $this;
    }

    public function execute(InputInterface $zinput, OutputInterface $output)
    {
        if ( ! setup::is_installed()) {
            $output->writeln('Phraseanet is not set up');

            return 1;
        }

        require_once __DIR__ . '/../../../../lib/bootstrap.php';

        $scheduler = new task_Scheduler();
        $scheduler->run($zinput, $output); //, !$input->getOption('nolog'), !$input->getOption('notasklog'));

        try {
            $scheduler = new task_Scheduler();
            $scheduler->run($output, true);
        } catch (\Exception $e) {
            return 1;
        }
    }
}
