<?php

require_once __DIR__ . '/../../Bridge_datas.inc';

class Bridge_Api_Youtube_ElementTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Bridge_Api_Youtube_Element
     */
    protected $object;

    public function setUp()
    {
        $published = new Zend_Gdata_App_Extension_Published("2011-10-21 12:00:00");
        $updated = new Zend_Gdata_App_Extension_Updated("2011-10-21 12:20:00");
        $id = new Zend_Gdata_App_Extension_Id("Az2cv12");
        $rating = new Zend_Gdata_Extension_Rating(4, 1, 5, 200, 4);
        $duration = new Zend_Gdata_YouTube_Extension_Duration(80);
        $player = new Zend_Gdata_Media_Extension_MediaPlayer();
        $player->setUrl("coucou");
        $stat = new Zend_Gdata_YouTube_Extension_Statistics();
        $stat->setViewCount("5");
        $thumb = new Zend_Gdata_Media_Extension_MediaThumbnail('une url', '120', '90');
        $media = new Zend_Gdata_YouTube_Extension_MediaGroup();
        $media->setPlayer([$player]);
        $media->setDuration($duration);
        $media->setVideoId($id);
        $media->setThumbnail([$thumb]);
        $entry = new Zend_Gdata_YouTube_VideoEntry();
        $entry->setMajorProtocolVersion(2);
        $entry->setMediaGroup($media);
        $entry->setStatistics($stat);
        $entry->setRating($rating);
        $entry->setVideoCategory("category");
        $entry->setVideoDescription("one description");
        $entry->setVideoPrivate();
        $entry->setVideoTags(['tags']);
        $entry->setVideoTitle("hellow");
        $entry->setUpdated($updated);
        $entry->setPublished($published);
        $this->object = new Bridge_Api_Youtube_Element($entry, 'video');
    }

    public function testGet_id()
    {
        $this->assertEquals("Az2cv12", $this->object->get_id());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_id());
    }

    public function testGet_thumbnail()
    {
        $this->assertEquals("une url", $this->object->get_thumbnail());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_thumbnail());
    }

    public function testGet_url()
    {
        $this->assertEquals("coucou", $this->object->get_url());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_url());
    }

    public function testGet_title()
    {
        $this->assertEquals("hellow", $this->object->get_title());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_title());
    }

    public function testGet_description()
    {
        $this->assertEquals("one description", $this->object->get_description());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_description());
    }

    public function testGet_updated_on()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_updated_on());
        $this->assertEquals(new DateTime("2011-10-21 12:20:00"), $this->object->get_updated_on());
    }

    public function testGet_category()
    {
        $this->assertEquals("category", $this->object->get_category());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_category());
    }

    public function testGet_duration()
    {
        $this->assertEquals(p4string::format_seconds(80), $this->object->get_duration());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_duration());
    }

    public function testGet_view_count()
    {
        $this->assertEquals(5, $this->object->get_view_count());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $this->object->get_view_count());
    }

    public function testGet_rating()
    {
        $this->assertEquals(200, $this->object->get_rating());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $this->object->get_rating());
    }

    public function testGet_created_on()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_created_on());
        $this->assertEquals(new DateTime("2011-10-21 12:00:00"), $this->object->get_created_on());
    }

    public function testIs_private()
    {
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_BOOL, $this->object->is_private());
        $this->assertTrue($this->object->is_private());
    }

    public function testGet_type()
    {
        $this->assertEquals("video", $this->object->get_type());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_type());
    }
}
