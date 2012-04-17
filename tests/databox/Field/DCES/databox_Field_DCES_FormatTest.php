<?php

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class databox_Field_DCES_FormatTest extends PhraseanetPHPUnitAbstract
{

  /**
   * @var databox_Field_DCES_Format
   */
  protected $object;

  public function setUp()
  {
    $this->object = new databox_Field_DCES_Format;
  }

  public function testGet_label()
  {
    $name = str_replace('Test', '', array_pop(explode('_', __CLASS__)));
    $this->assertEquals($name, $this->object->get_label());
  }

  public function testGet_definition()
  {
    $this->assertTrue(is_string($this->object->get_definition()));
    $this->assertTrue(strlen($this->object->get_definition()) > 20);
  }

  public function testGet_documentation_link()
  {
    $this->assertRegExp('/^http:\/\/dublincore\.org\/documents\/dces\/#[a-z]+$/', $this->object->get_documentation_link());
  }

}

