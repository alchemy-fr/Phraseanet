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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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
        $x = sprintf('/^task_%d-(|(.*))\.log$/', $this->task->getId());
        $f = new Finder();
        $versions = [];
        /** @var \SplFileInfo $file */
        foreach($f->files()->in($this->root) as $file) {
            $matches = [];
            if(preg_match($x, $file->getBasename(), $matches)) {
                $versions[] = $matches[1];
            }
        }
        return $versions;
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
    public function getPath($version = '')
    {
        if (trim($version) != '') {
            $version = '-' . $version;
        }

        return sprintf('%s/task%s.log', $this->root, $version);
    }

}
