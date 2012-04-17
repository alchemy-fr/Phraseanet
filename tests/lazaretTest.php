<?php

require_once __DIR__ . '/PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class lazaretTest extends PhraseanetPHPUnitAuthenticatedAbstract
{

  /**
   * @var lazaret
   */
  protected $object;

  public function setUp()
  {
    parent::setUp();
    $this->object = new lazaret;
  }

  public function testIsOk()
  {
    $this->markTestIncomplete();
    foreach ($this->object->get_elements() as $element)
    {

    }
  }

}

