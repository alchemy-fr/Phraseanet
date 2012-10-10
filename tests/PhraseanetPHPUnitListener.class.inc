<?php

class PhraseanetPHPUnitListener implements PHPUnit_Framework_TestListener
{
    private static $skipped = array();

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        return;
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        return;
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        return;
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        static::$skipped[] = get_class($test) . ':' . $test->getName() . ' - ' . $e->getMessage();

        return;
    }

    public static function getSkipped()
    {
        return static::$skipped;
    }

    public static function resetSkipped()
    {
        static::$skipped = array();

        return;
    }

    public function startTest(PHPUnit_Framework_Test $test)
    {
echo "start test ".$test->getName()."\n";
        return;
    }

    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        return;
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        return;
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        return;
    }
}
