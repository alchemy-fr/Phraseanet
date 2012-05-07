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

class module_console_schedulerState extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Get scheduler status');

        $this->addOption(
            'short'
            , NULL
            , InputOption::VALUE_NONE
            , 'print short result, ie: <info>stopped</info> | <info>started(12345)</info> | <info>stopping</info>'
            , NULL
        );
//    $this->setHelp("");

        return $this;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ( ! setup::is_installed()) {
            $output->writeln($input->getOption('short') ? 'setup_error' : 'Phraseanet is not set up');

            return 1;
        }

        require_once __DIR__ . '/../../../../lib/bootstrap.php';

        $appbox = appbox::get_instance(\bootstrap::getCore());
        $task_manager = new task_manager($appbox);

        $state = $task_manager->get_scheduler_state();

        if ($state['status'] == 'started') {
            $output->writeln(sprintf(
                    'Scheduler is %s on pid %d'
                    , $state['status']
                    , $state['pid']
                ));
        } else {
            $output->writeln(sprintf('Scheduler is %s', $state['status']));
        }

        switch ($state['status']) {
            case \task_manager::STATUS_SCHED_STARTED:

                return 10;
                break;
            case \task_manager::STATUS_SCHED_STOPPED:

                return 11;
                break;
            case \task_manager::STATUS_SCHED_STOPPING:

                return 12;
                break;
            case \task_manager::STATUS_SCHED_TOSTOP:

                return 13;
                break;
        }

        return 1;
    }
}
