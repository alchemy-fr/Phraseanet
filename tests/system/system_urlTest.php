<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class system_urlTest extends PhraseanetPHPUnitAbstract
{

  /**
   * @var system_url
   */
  protected $object;
  protected $url = "http://test.example.com?action=test&labourer=bien";

  public function setUp()
  {
    $this->object = new system_url($this->url);
  }

  public function testGet_url()
  {
    $this->assertEquals($this->url, $this->object->get_url());
  }

}

