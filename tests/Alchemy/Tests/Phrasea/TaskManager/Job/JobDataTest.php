<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\JobData;

class JobDataTest extends \PhraseanetTestCase
{
    public function testGetters()
    {
        $app = $this->getMockBuilder('Alchemy\Phrasea\Application')
            ->disableOriginalConstructor()
            ->getMock();
        $task = $this->getMockBuilder('Alchemy\Phrasea\Model\Entities\Task')
            ->disableOriginalConstructor()
            ->getMock();
        $data = new JobData($app, $task);
        $this->assertInstanceOf('Alchemy\TaskManager\JobDataInterface', $data);
        $this->assertSame($app, $data->getApplication());
        $this->assertSame($task, $data->getTask());
    }
}
