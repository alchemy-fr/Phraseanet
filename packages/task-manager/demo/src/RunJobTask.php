<?php

namespace Alchemy\TaskManager\Demo;

use Alchemy\TaskManager\TaskInterface;
use Symfony\Component\Process\Process;

class RunJobTask implements TaskInterface
{
    private $iterations;
    private $name;

    public function __construct($name, $iterations)
    {
        $this->name = $name;
        $this->iterations = $iterations;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getIterations()
    {
        return $this->iterations;
    }

    public function createProcess()
    {
        return new Process(array('php', __DIR__.'/../bin/console', 'console:run-task'));
    }
}
