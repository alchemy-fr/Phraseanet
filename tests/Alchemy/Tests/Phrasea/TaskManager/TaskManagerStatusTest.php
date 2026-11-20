<?php

namespace Alchemy\Tests\Phrasea\TaskManager;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\TaskManager\TaskManagerStatus;
use Alchemy\Tests\Phrasea\MockArrayConf;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class TaskManagerStatusTest extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideInitialData')]
    public function testStart($initialData)
    {
        $conf = new MockArrayConf($initialData);
        $expected = $conf->getConfig();
        $expected['main']['task-manager']['status'] = TaskManagerStatus::STATUS_STARTED;

        $status = new TaskManagerStatus(new PropertyAccess($conf));
        $status->start();

        $this->assertEquals($expected, $conf->getConfig());
    }

    public static function provideInitialData()
    {
        return [
            [['main' => []]],
            [['main' => ['task-manager' => []]]],
            [['main' => ['task-manager' => ['status' => TaskManagerStatus::STATUS_STARTED]]]],
            [['main' => ['task-manager' => ['status' => TaskManagerStatus::STATUS_STOPPED]]]],
            [['main' => ['key1' => 'value1']]],
            [['main' => ['task-manager' => [], 'key2' => 'value2']]],
            [['main' => ['task-manager' => ['status' => TaskManagerStatus::STATUS_STARTED, 'key3' => 'value3'], 'key4' => 'value4']]],
            [['main' => ['task-manager' => ['status' => TaskManagerStatus::STATUS_STOPPED, 'key5' => 'value5'], 'key6' => 'value6']]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideInitialData')]
    public function testStop($initialData)
    {
        $conf = new MockArrayConf($initialData);
        $expected = $conf->getConfig();
        $expected['main']['task-manager']['status'] = TaskManagerStatus::STATUS_STOPPED;

        $status = new TaskManagerStatus(new PropertyAccess($conf));
        $status->stop();

        $this->assertEquals($expected, $conf->getConfig());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideConfAndStatusData')]
    public function testIsRunning($data, $expectedStatus, $isRunning)
    {
        $conf = new PropertyAccess(new MockArrayConf($data));
        $status = new TaskManagerStatus($conf);
        $this->assertEquals($isRunning, $status->isRunning());
    }

    public static function provideConfAndStatusData()
    {
        return [
            [['main' => []], TaskManagerStatus::STATUS_STARTED, true],
            [['main' => ['task-manager' => []]], TaskManagerStatus::STATUS_STARTED, true],
            [['main' => ['task-manager' => ['status' => TaskManagerStatus::STATUS_STOPPED]]], TaskManagerStatus::STATUS_STOPPED, false],
            [['main' => ['task-manager' => ['status' => TaskManagerStatus::STATUS_STARTED]]], TaskManagerStatus::STATUS_STARTED, true],
            [['main' => ['task-manager' => ['status' => 'unknown']]], TaskManagerStatus::STATUS_STARTED, true],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideConfAndStatusData')]
    public function testGetStatus($data, $expectedStatus, $isRunning)
    {
        $conf = new PropertyAccess(new MockArrayConf($data));
        $status = new TaskManagerStatus($conf);
        $this->assertEquals($expectedStatus, $status->getStatus());
    }
}
