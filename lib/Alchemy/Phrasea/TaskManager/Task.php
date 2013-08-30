<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager;

use Alchemy\TaskManager\TaskInterface;
use Symfony\Component\Process\ProcessableInterface;

class Task implements TaskInterface
{
    private $name;
    private $iterations;
    private $process;

    public function __construct($name, $iterations, ProcessableInterface $process)
    {
        $this->name = $name;
        $this->iterations = $iterations;
        $this->process = $process;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterations()
    {
        return $this->iterations;
    }

    /**
     * {@inheritdoc}
     */
    public function createProcess()
    {
        return clone $this->process;
    }
}
