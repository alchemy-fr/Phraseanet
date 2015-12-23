<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Log;

use Alchemy\Phrasea\Model\Entities\Task;

class TaskLogFile extends AbstractLogFile implements LogFileInterface
{
    /** @var Task */
    private $task;

    public function __construct($root, Task $task)
    {
        parent::__construct($root);
        $this->task = $task;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersions()
    {
        return array('', '2015-12-21');
    }

    /**
     * Returns the related Task entity.
     *
     * @return Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath($version)
    {
        $path = sprintf('%s/task_%d%s.log', $this->root, $this->task->getId(), $version ? ('-'.$version) : '');
        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) %s\n", __FILE__, __LINE__, var_export($path, true)), FILE_APPEND);
        return sprintf('%s/task_%d%s.log', $this->root, $this->task->getId(), $version ? ('-'.$version) : '');
    }
}
