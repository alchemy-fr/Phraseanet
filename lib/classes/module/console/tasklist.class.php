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
        if ( ! setup::is_installed()) {
            $output->writeln('Phraseanet is not set up');

            return 1;
        }

        require_once __DIR__ . '/../../../../lib/bootstrap.php';

        try {
            $appbox = appbox::get_instance(\bootstrap::getCore());
            $task_manager = new task_manager($appbox);
            $tasks = $task_manager->getTasks();

            if (count($tasks) === 0) {
                $output->writeln('No tasks on your install !');
            }

            foreach ($tasks as $task) {
                $this->printTask($task, $output);
            }

            return 0;
        } catch (\Exception $e) {
            return 1;
        }
    }

    protected function printTask(task_abstract $task, OutputInterface &$output)
    {
        $message = $task->getID() . "\t" . ($task->getState() ) . "\t" . $task->getTitle();
        $output->writeln($message);

        return $this;
    }
}
