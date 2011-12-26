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

class module_console_taskrun extends Command
{

  public function __construct($name = null)
  {
    parent::__construct($name);

    $this->addArgument('task_id', InputArgument::REQUIRED, 'The task_id to run');
    $this->addOption(
            'runner'
            , 'r'
            , InputOption::VALUE_REQUIRED
            , 'The name of the runner (manual, scheduler...)'
            , task_abstract::RUNNER_MANUAL
    );
    $this->setDescription('Run task');

    return $this;
  }

  public function execute(InputInterface $input, OutputInterface $output)
  {
    if(!setup::is_installed())
    {
      throw new RuntimeException('Phraseanet is not set up');
    }

    require_once __DIR__ . '/../../../../lib/bootstrap.php';

    $task_id = (int) $input->getArgument('task_id');

    if ($task_id <= 0 || strlen($task_id) !== strlen($input->getArgument('task_id')))
      throw new \RuntimeException('Argument must be an Id.');

    $appbox = appbox::get_instance();
    $task_manager = new task_manager($appbox);
    $task = $task_manager->get_task($task_id);

    $runner = task_abstract::RUNNER_SCHEDULER;
    if ($input->getOption('runner') === task_abstract::RUNNER_MANUAL)
      $runner = task_abstract::RUNNER_MANUAL;

    $task->run($runner);

    return $this;
  }


}
