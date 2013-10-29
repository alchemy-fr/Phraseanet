<?php

namespace Alchemy\Tests\Phrasea\TaskManager;

use Alchemy\Phrasea\TaskManager\TaskManagerStatus;
use Alchemy\Phrasea\Core\Configuration\ConfigurationInterface;

class TaskManagerStatusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideInitialData
     */
    public function testStart($initialData)
    {
        $conf = new ConfigurationTest($initialData);
        $expected = $conf->getConfig();
        $expected['task-manager']['status'] = TaskManagerStatus::STATUS_STARTED;

        $status = new TaskManagerStatus($conf);
        $status->start();

        $this->assertEquals($expected, $conf->getConfig());
    }

    public function provideInitialData()
    {
        return array(
            array(array()),
            array(array('task-manager' => array())),
            array(array('task-manager' => array('status' => TaskManagerStatus::STATUS_STARTED))),
            array(array('task-manager' => array('status' => TaskManagerStatus::STATUS_STOPPED))),
            array(array('key1' => 'value1')),
            array(array('task-manager' => array(), 'key2' => 'value2')),
            array(array('task-manager' => array('status' => TaskManagerStatus::STATUS_STARTED, 'key3' => 'value3'), 'key4' => 'value4')),
            array(array('task-manager' => array('status' => TaskManagerStatus::STATUS_STOPPED, 'key5' => 'value5'), 'key6' => 'value6')),
        );
    }

    /**
     * @dataProvider provideInitialData
     */
    public function testStop($initialData)
    {
        $conf = new ConfigurationTest($initialData);
        $expected = $conf->getConfig();
        $expected['task-manager']['status'] = TaskManagerStatus::STATUS_STOPPED;

        $status = new TaskManagerStatus($conf);
        $status->stop();

        $this->assertEquals($expected, $conf->getConfig());
    }

    /**
     * @dataProvider provideConfAndStatusData
     */
    public function testIsRunning($data, $expectedStatus, $isRunning)
    {
        $conf = new ConfigurationTest($data);
        $status = new TaskManagerStatus($conf);
        $this->assertEquals($isRunning, $status->isRunning());
    }

    public function provideConfAndStatusData()
    {
        return array(
//            array(array(), TaskManagerStatus::STATUS_STARTED, true),
//            array(array('task-manager' => array()), TaskManagerStatus::STATUS_STARTED, true),
            array(array('task-manager' => array('status' => TaskManagerStatus::STATUS_STOPPED)), TaskManagerStatus::STATUS_STOPPED, false),
//            array(array('task-manager' => array('status' => TaskManagerStatus::STATUS_STARTED)), TaskManagerStatus::STATUS_STARTED, true),
//            array(array('task-manager' => array('status' => 'unknown')), TaskManagerStatus::STATUS_STARTED, true),
        );
    }

    /**
     * @dataProvider provideConfAndStatusData
     */
    public function testGetStatus($data, $expectedStatus, $isRunning)
    {
        $conf = new ConfigurationTest($data);
        $status = new TaskManagerStatus($conf);
        $this->assertEquals($expectedStatus, $status->getStatus());
    }
}

class ConfigurationTest implements ConfigurationInterface
{
    private $data = array();

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function offsetGet($key)
    {
        return $this->data[$key];
    }

    public function offsetSet($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function offsetExists($key)
    {
        return isset($this->data[$key]);
    }

    public function offsetUnset($key)
    {
        unset($this->data[$key]);
    }

    public function getConfig()
    {
        return $this->data;
    }

    public function initialize()
    {
        throw new \RuntimeException('This method should not be used here');
    }

    public function delete()
    {
        throw new \RuntimeException('This method should not be used here');
    }

    public function isSetup()
    {
        throw new \RuntimeException('This method should not be used here');
    }

    public function setDefault($name)
    {
        throw new \RuntimeException('This method should not be used here');
    }

    public function setConfig(array $config)
    {
        throw new \RuntimeException('This method should not be used here');
    }

    public function compileAndWrite()
    {
        throw new \RuntimeException('This method should not be used here');
    }
}
