<?php

class PhraseanetPHPUnitListener implements PHPUnit_Framework_TestListener
{
    private static $enableDurationCapture = false;
    private static $skipped = [];
    private static $duration = [];
    private static $csv = [];
    private static $durationByTest = [];

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        static::$skipped[] = get_class($test) . ':' . $test->getName() . ' - ' . $e->getMessage();
    }

    public static function getSkipped()
    {
        return static::$skipped;
    }

    public static function getDuration()
    {
        return static::$duration;
    }

    public static function getCsv()
    {
        return static::$csv;
    }

    public static function getDurationByTest()
    {
        return static::$durationByTest;
    }

    public static function resetSkipped()
    {
        static::$skipped = [];
    }

    public static function resetDuration()
    {
        static::$duration = [];
        static::$durationByTest = [];
        static::$csv = [];
    }

    public function startTest(PHPUnit_Framework_Test $test)
    {
        printf("'%s' started\n", self::generateName($test));
        if (!static::$enableDurationCapture) {
            return;
        }
        if (!isset(static::$durationByTest[get_class($test)]['executions'])) {
            static::$durationByTest[get_class($test)]['executions'] = 0;
        }

        static::$durationByTest[get_class($test)]['executions']++;
        static::$duration[self::generateName($test)] = microtime(true);
        static::$csv[self::generateName($test)] = [
            'duration' => microtime(true),
            'test'     => get_class($test),
            'name'     => $test->getName(),
        ];
    }

    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        if (!static::$enableDurationCapture) {
            return;
        }
        $name = self::generateName($test);
        static::$duration[$name] = microtime(true) - static::$duration[$name];
        static::$csv[self::generateName($test)]['duration'] = microtime(true) - static::$csv[self::generateName($test)]['duration'];
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        if (!static::$enableDurationCapture) {
            return;
        }

        if (!class_exists($suite->getName())) {
            return;
        }

        static::$durationByTest[$suite->getName()]['time'] = microtime(true);
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        if (!static::$enableDurationCapture) {
            return;
        }

        if (!class_exists($suite->getName())) {
            return;
        }

        static::$durationByTest[$suite->getName()]['time'] = microtime(true) - static::$durationByTest[$suite->getName()]['time'];
    }

    private static function generateName(PHPUnit_Framework_Test $test)
    {
        $reflect = new \ReflectionClass($test);

        return $reflect->getShortName() . '::' . $test->getName();
    }
}
