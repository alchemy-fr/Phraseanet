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

class module_console_taskState extends Command
{
    const EXITCODE_SETUP_ERROR = 1;
    const EXITCODE_BAD_ARGUMENT = 2;
    const EXITCODE_FATAL_ERROR = 3;
    const EXITCODE_TASK_UNKNOWN = 20;
    const EXITCODE_STATE_UNKNOWN = 21;

    private $stateToExitCode = array(
        \task_abstract::STATE_TOSTOP    => 13,
        \task_abstract::STATE_STARTED   => 10,
        \task_abstract::STATE_TOSTART   => 14,
        \task_abstract::STATE_TORESTART => 15,
        \task_abstract::STATE_STOPPED   => 11,
        \task_abstract::STATE_TODELETE  => 16
    );

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->addArgument('task_id', InputArgument::REQUIRED, 'The task_id to test');

        $this->setDescription('Get task state');

        $this->addOption(
            'short'
            , NULL
            , InputOption::VALUE_NONE
            , 'print short result, ie: <info>stopped()</info> | <info>started(12345)</info> | <info>tostop(12345)</info> | <info>...</info>'
            , NULL
        );

        return $this;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ( ! setup::is_installed()) {
            $output->writeln($input->getOption('short') ? 'setup_error' : 'Phraseanet is not set up');

            return self::EXITCODE_SETUP_ERROR;
        }
        $task_id = (int) $input->getArgument('task_id');
        if ($task_id <= 0 || strlen($task_id) !== strlen($input->getArgument('task_id'))) {
            $output->writeln($input->getOption('short') ? 'bad_id' : 'Argument must be an ID');

            return self::EXITCODE_BAD_ARGUMENT;
        }

        require_once __DIR__ . '/../../../../lib/bootstrap.php';

        $appbox = appbox::get_instance(\bootstrap::getCore());
        $task_manager = new task_manager($appbox);

        $taskPID = $taskState = NULL;
        $exitCode = 0;

        $task = NULL;
        try {
            $task = $task_manager->getTask($task_id);
            $taskPID = $task->getPID();
            $taskState = $task->getState();
        } catch (Exception_NotFound $e) {
            $output->writeln($input->getOption('short') ? 'unknown_id' : $e->getMessage());

            return self::EXITCODE_TASK_UNKNOWN;
        } catch (Exception $e) {
            $output->writeln($input->getOption('short') ? 'fatal_error' : $e->getMessage());

            return self::EXITCODE_FATAL_ERROR;
        }

        if ($input->getOption('short')) {
            $output->writeln(sprintf('%s(%s)', $taskState, $taskPID));
        } else {
            if ($taskPID !== NULL) {
                $output->writeln(sprintf(
                        'Task %d is %s on pid %d'
                        , $task_id
                        , $taskState
                        , $taskPID
                    ));
            } else {
                $output->writeln(sprintf('Task %d is %s', $task_id, $taskState));
            }
        }

        if (array_key_exists($taskState, $this->stateToExitCode)) {
            $exitCode = $this->stateToExitCode[$taskState];
        } else {
            $exitCode = self::EXITCODE_STATE_UNKNOWN;
        }

        return $exitCode;
    }
}

