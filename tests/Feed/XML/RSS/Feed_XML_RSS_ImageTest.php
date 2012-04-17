<?php

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class Feed_XML_RSS_ImageTest extends PhraseanetPHPUnitAbstract
{

  /**
   * @var Feed_XML_RSS_Image
   */
  protected $object;
  protected $url;
  protected $title;
  protected $link;
  protected $description;
  protected $width;
  protected $height;

  public function setUp()
  {
    parent::setUp();
    $this->this->link = 'http://www.example.org';
    $this->title = 'Un beau titre';
    $this->url = 'http://www.example.org/image.jpg';
    $this->width = 42;
    $this->height = 30;
    $this->description = 'KIKOO';
    $this->object = new Feed_XML_RSS_Image($this->url, $this->title, $this->link);
    $this->object->set_width($this->width);
    $this->object->set_height($this->height);
    $this->object->set_description($this->description);

  }

  public function testGet_url()
  {
    $this->assertEquals($this->url, $this->object->get_url());
  }

  public function testGet_title()
  {
    $this->assertEquals($this->title, $this->object->get_title());
  }

  public function testGet_link()
  {
    $this->assertEquals($this->link, $this->object->get_link());
  }

  public function testGet_description()
  {
    $this->assertEquals($this->description, $this->object->get_description());
  }

  public function testGet_height()
  {
    $this->assertEquals($this->height, $this->object->get_height());
  }

  public function testGet_width()
  {
    $this->assertEquals($this->width, $this->object->get_width());
  }

  public function testSet_description()
  {
    $new_desc = 'une nouvelle';
    $this->object->set_description($new_desc);
    $this->assertEquals($new_desc, $this->object->get_description());
  }

  public function testSet_height()
  {
    $new_height = 27;
    $this->object->set_height($new_height);
    $this->assertEquals($new_height, $this->object->get_height());
  }

  public function testSet_width()
  {
    $new_width = 14;
    $this->object->set_width($new_width);
    $this->assertEquals($new_width, $this->object->get_width());
  }

}

