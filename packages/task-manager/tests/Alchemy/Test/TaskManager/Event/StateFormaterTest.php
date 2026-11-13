<?php

namespace Alchemy\Test\TaskManager\Event;

use Alchemy\TaskManager\Event\StateFormater;
use Alchemy\Test\TaskManager\TestCase;

class StateFormaterTest extends TestCase
{
    public function testToArray()
    {
        $pid = getmypid();

        $process1 = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();
        $process1->expects($this->once())
                ->method('getPid')
                ->willReturn(1234);
        $process2 = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $managed1 = $this->getMockBuilder('Neutron\ProcessManager\ManagedProcess')
                ->disableOriginalConstructor()
                ->getMock();
        $managed1->expects($this->any())
                ->method('getManagedProcess')
                ->willReturn($process1);
        $managed1->expects($this->once())
                ->method('getStatus')
                ->willReturn('laughing');

        $managed2 = $this->getMockBuilder('Neutron\ProcessManager\ManagedProcess')
                ->disableOriginalConstructor()
                ->getMock();
        $managed2->expects($this->any())
                ->method('getManagedProcess')
                ->willReturn($process2);
        $managed2->expects($this->once())
                ->method('getStatus')
                ->willReturn('started');

        $state = array(
            'manager' => array(
                'process-id' => $pid,
            ),
            'jobs' => array(
                'task-0' => array(
                    'status' => 'laughing',
                    'process-id' => 1234
                ),
                'task-1' => array(
                    'status' => 'started',
                    'process-id' => null
                ),
            )
        );

        $formater = new StateFormater();
        $this->assertEquals($state, $formater->toArray(array('task-0' => $managed1, 'task-1' => $managed2)));
    }
}
