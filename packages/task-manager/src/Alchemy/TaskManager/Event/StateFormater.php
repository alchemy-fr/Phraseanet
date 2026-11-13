<?php

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager\Event;

use Neutron\ProcessManager\ManagedProcess;
use Symfony\Component\Process\Process;

class StateFormater
{
    /**
     * @param Process[] $processes
     *
     * @return array
     */
    public function toArray($processes)
    {
        $data = array(
            'manager' => array(
                'process-id' => getmypid(),
            ),
            'jobs' => array()
        );

        foreach ($processes as $name => $process) {
            $data['jobs'][$name] = $this->extractData($process);
        }

        return $data;
    }

    private function extractData(ManagedProcess $managed)
    {
        return array(
            'status'     => $managed->getStatus(),
            'process-id' => $managed->getManagedProcess() instanceof Process ? $managed->getManagedProcess()->getPid() : null,
        );
    }
}
