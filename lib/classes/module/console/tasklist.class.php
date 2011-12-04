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

class module_console_tasklist extends Command
{

  public function __construct($name = null)
  {
    parent::__construct($name);

    $this->setDescription('List tasks');

    return $this;
  }

  public function execute(InputInterface $input, OutputInterface $output)
  {
    if(!setup::is_installed())
    {
      throw new RuntimeException('Phraseanet is not set up');
    }

    require_once dirname(__FILE__) . '/../../../../lib/bootstrap.php';

    $appbox = appbox::get_instance();
    $task_manager = new task_manager($appbox);
    $tasks = $task_manager->get_tasks();

    if(count($tasks) === 0)
      $output->writeln ('No tasks on your install !');

    foreach($tasks as $task)
    {
      $this->print_task($task, $output);
    }

    return $this;
  }


  protected function print_task(task_abstract $task, OutputInterface &$output)
  {
    $message = $task->get_task_id()."\t".($task->get_status() )."\t".$task->get_title();
    $output->writeln($message);

    return $this;
  }
}
