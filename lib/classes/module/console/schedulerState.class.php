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

class module_console_schedulerState extends Command
{
  public function __construct($name = null)
  {
    parent::__construct($name);

    $this->setDescription('Get scheduler state');

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

    $state = $task_manager->get_scheduler_state();

    if ($state['schedstatus'] == 'started')
      $output->writeln(sprintf(
                      'Scheduler is %s on pid %d'
                      , $state['schedstatus']
                      , $state['schedpid']
              ));
    else
      $output->writeln(sprintf('Scheduler is %s', $state['schedstatus']));

    return;
  }

}
