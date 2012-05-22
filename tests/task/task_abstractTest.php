<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class task_abstractTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var task_abstract
     */
    protected static $task;
    protected static $tid;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $appbox = appbox::get_instance(\bootstrap::getCore());

        self::$task = task_abstract::create($appbox, 'task_period_test');
        self::$tid = self::$task->getID();
    }

    public static function tearDownAfterClass()
    {
        self::$task->delete();
        parent::tearDownAfterClass();
    }

    /**
     * @covers \task_abstract::setActive
     * @covers \task_abstract::isActive
     */
    public function testActive()
    {
        self::$task->setActive(true);
        self::assertTrue(self::$task->isActive());

        self::$task->setActive(false);
        self::assertFalse(self::$task->isActive());
    }

    /**
     * @covers \task_abstract::setState
     * @covers \task_abstract::getState
     */
    public function testState()
    {
        self::$task->setState(\task_abstract::STATE_STOPPED);
        self::assertEquals(\task_abstract::STATE_STOPPED, self::$task->getState());

        self::$task->setState(\task_abstract::STATE_TOSTOP);
        self::assertEquals(\task_abstract::STATE_TOSTOP, self::$task->getState());
    }

    /**
     * @covers \task_abstract::setTitle
     * @covers \task_abstract::getTitle
     */
    public function testTitle()
    {
        self::$task->setTitle('a_test_title');
        self::assertEquals('a_test_title', self::$task->getTitle());
    }

    /**
     * @covers \task_abstract::resetCrashCounter
     * @covers \task_abstract::incrementCrashCounter
     * @covers \task_abstract::getCrashCounter
     */
    public function testCrashCounter()
    {
        self::$task->resetCrashCounter();
        self::$task->incrementCrashCounter();
        self::assertEquals(1, self::$task->getCrashCounter());

        self::$task->incrementCrashCounter();
        self::assertEquals(2, self::$task->getCrashCounter());

        self::$task->resetCrashCounter();
        self::assertEquals(0, self::$task->getCrashCounter());
    }

    /**
     * @covers \task_abstract::setSettings
     * @covers \task_abstract::getSettings
     */
    public function testSettings()
    {
        $goodSettings = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><tasksettings />";
        $sxGoodSettings = simplexml_load_string($goodSettings);

        self::$task->setSettings($goodSettings);
        $settings = self::$task->getSettings();
        $sxSettings = @simplexml_load_string($settings);

        self::assertTrue($sxSettings !== FALSE);
        self::assertEquals($sxGoodSettings->saveXML(), $sxSettings->saveXML());
    }

    /**
     * @covers \task_abstract::setSettings
     * @expectedException Exception_InvalidArgument
     */
    public function testSettingsException()
    {
        self::$task->setSettings('this_is_bad_xml');
    }

    /**
     * @covers \task_abstract::setRunner
     * @covers \task_abstract::getRunner
     */
    public function testRunner()
    {
        self::$task->setRunner(\task_abstract::RUNNER_MANUAL);
        self::assertTrue(\task_abstract::RUNNER_MANUAL === self::$task->getRunner());

        self::$task->setRunner(\task_abstract::RUNNER_SCHEDULER);
        self::assertTrue(\task_abstract::RUNNER_SCHEDULER === self::$task->getRunner());
    }

    /**
     * @covers \task_abstract::setRunner
     * @expectedException Exception_InvalidArgument
     */
    public function testRunnerException()
    {
        self::$task->setRunner('this_is_bad_runner');
    }

    /**
     * @covers \task_abstract::lockTask
     * @covers \task_abstract::unlockTask
     */
    public function testLockTask()
    {
        $methodL = new ReflectionMethod(self::$task, 'lockTask');
        $methodL->setAccessible(TRUE);

        $methodU = new ReflectionMethod(self::$task, 'unlockTask');
        $methodU->setAccessible(TRUE);

        // test that task should not be locked
        try {
            $fd = $methodL->invoke(self::$task);
        } catch (Exception $e) {
            self::fail('file should not be locked');
        }
        self::assertInternalType('resource', $fd);

        // now task should be locked
        try {
            $fd = $methodL->invoke(self::$task);
            self::fail('file should be locked');
        } catch (Exception $e) {

        }

        // so we can unlock
        $methodU->invokeArgs(self::$task, array($fd));

        // task should not be locked
        try {
            $fd = $methodL->invoke(self::$task);
        } catch (Exception $e) {
            self::fail('file should not be locked');
        }
        self::assertInternalType('resource', $fd);

        // leave the file unlocked
        $methodU->invokeArgs(self::$task, array($fd));

    }
}
