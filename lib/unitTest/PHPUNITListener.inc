<?php

class SimpleTestListener implements PHPUnit_Framework_TestListener
{

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
    return;
  }

  public function startTest(PHPUnit_Framework_Test $test)
  {
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
