<?php

namespace Alchemy\Tests\Phrasea\TaskManager;

use Alchemy\Phrasea\TaskManager\TaskManagerStatus;
use Alchemy\Tests\Phrasea\MockArrayConf;

class TaskManagerStatusTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideInitialData
     */
    public function testStart($initialData)
    {
        $conf = new MockArrayConf($initialData);
        $expected = $conf->getConfig();
        $expected['main']['task-manager']['status'] = TaskManagerStatus::STATUS_STARTED;

        $status = new TaskManagerStatus($conf);
        $status->start();

        $this->assertEquals($expected, $conf->getConfig());
    }

    public function provideInitialData()
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

    /**
     * @dataProvider provideInitialData
     */
    public function testStop($initialData)
    {
        $conf = new MockArrayConf($initialData);
        $expected = $conf->getConfig();
        $expected['main']['task-manager']['status'] = TaskManagerStatus::STATUS_STOPPED;

        $status = new TaskManagerStatus($conf);
        $status->stop();

        $this->assertEquals($expected, $conf->getConfig());
    }

    /**
     * @dataProvider provideConfAndStatusData
     */
    public function testIsRunning($data, $expectedStatus, $isRunning)
    {
        $conf = new MockArrayConf($data);
        $status = new TaskManagerStatus($conf);
        $this->assertEquals($isRunning, $status->isRunning());
    }

    public function provideConfAndStatusData()
    {
        return [
            [['main' => []], TaskManagerStatus::STATUS_STARTED, true],
            [['main' => ['task-manager' => []]], TaskManagerStatus::STATUS_STARTED, true],
            [['main' => ['task-manager' => ['status' => TaskManagerStatus::STATUS_STOPPED]]], TaskManagerStatus::STATUS_STOPPED, false],
            [['main' => ['task-manager' => ['status' => TaskManagerStatus::STATUS_STARTED]]], TaskManagerStatus::STATUS_STARTED, true],
            [['main' => ['task-manager' => ['status' => 'unknown']]], TaskManagerStatus::STATUS_STARTED, true],
        ];
    }

    /**
     * @dataProvider provideConfAndStatusData
     */
    public function testGetStatus($data, $expectedStatus, $isRunning)
    {
        $conf = new MockArrayConf($data);
        $status = new TaskManagerStatus($conf);
        $this->assertEquals($expectedStatus, $status->getStatus());
    }
}
