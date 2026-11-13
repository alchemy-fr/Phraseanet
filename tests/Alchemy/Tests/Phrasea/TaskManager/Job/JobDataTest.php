<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\JobData;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class JobDataTest extends \PhraseanetTestCase
{
    public function testGetters()
    {
        $app = $this->createStub('Alchemy\Phrasea\Application');
        $task = $this->createStub('Alchemy\Phrasea\Model\Entities\Task');
        $data = new JobData($app, $task);
        $this->assertInstanceOf('Alchemy\TaskManager\Job\JobDataInterface', $data);
        $this->assertSame($app, $data->getApplication());
        $this->assertSame($task, $data->getTask());
    }
}
