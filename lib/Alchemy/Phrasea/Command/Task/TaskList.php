<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Model\Entities\Task;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TaskList extends Command
{
    public function __construct()
    {
        parent::__construct('task-manager:task:list');
        $this->setDescription('Lists tasks');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Querying the task manager...</info>");
        $errors = 0;
        $probe = $this->container['task-manager.live-information'];

        $rows = array_map(function (Task $task) use ($probe, &$errors) {
            $info = $probe->getTask($task);
            $error = $info['actual'] !== $task->getStatus();
            if ($error) {
                $errors ++;
            }

            return [
                $task->getId(),
                $task->getName(),
                $task->getStatus() !== 'started' ? "<comment>".$task->getStatus() . "</comment>" : $task->getStatus(),
                $error ? "<error>".$info['actual']."</error>" : $info['actual'],
                $info['process-id'],
            ];
        }, $this->container['manipulator.task']->getRepository()->findAll());

        $this
            ->getHelperSet()->get('table')
            ->setHeaders(['Id', 'Name', 'Status (set)', 'Actual (probed)', 'Process Id'])
            ->setRows($rows)
            ->render($output);

        return $errors;
    }
}
