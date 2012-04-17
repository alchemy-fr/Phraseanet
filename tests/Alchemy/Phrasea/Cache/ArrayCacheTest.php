<?php

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class ArrayCacheTest extends \PHPUnit_Framework_TestCase
{

  /**
   * @var ArrayCache
   */
  protected $object;

  public function setUp()
  {
    $this->object = new \Alchemy\Phrasea\Cache\ArrayCache;
  }

  public function testIsServer()
  {
    $this->assertTrue(is_bool($this->object->isServer()));
  }

  public function testGetStats()
  {
    $this->assertTrue(is_array($this->object->getStats()) || is_null($this->object->getStats()));
  }

  public function testGet()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  public function testDeleteMulti()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

}

