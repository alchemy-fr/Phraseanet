<?php

class Feed_LinkTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var Feed_Link
     */
    protected $object;
    protected $href = "http://www.google.fr/";
    protected $title = "google";
    protected $mime = "html/text";

    public function setUp()
    {
        parent::setUp();
        $this->object = new Feed_Link($this->href, $this->title, $this->mime);
    }

    public function testGet_mimetype()
    {
        $this->assertEquals($this->mime, $this->object->get_mimetype());
    }

    public function testGet_title()
    {
        $this->assertEquals($this->title, $this->object->get_title());
    }

    public function testGet_href()
    {
        $this->assertEquals($this->href, $this->object->get_href());
    }
}
