<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
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

class module_console_tasklist extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Lists Phraseanet tasks');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->container['phraseanet.configuration-tester']->isInstalled()) {
            return self::EXITCODE_SETUP_ERROR;
        }

        try {
            $task_manager = $this->container['task-manager'];
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

    protected function printTask(task_abstract $task, OutputInterface $output)
    {
        $message = $task->getID() . "\t" . ($task->getState() ) . "\t" . $task->getTitle();
        $output->writeln($message);

        return $this;
    }
}
