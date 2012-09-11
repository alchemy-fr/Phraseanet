<?php

namespace Alchemy\Phrasea\Border\Attribute;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class StoryTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var Story
     */
    protected $object;
    protected $story;

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Attribute
     * @covers Alchemy\Phrasea\Border\Attribute\Story::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $this->story = \record_adapter::createStory(self::$application, self::$collection);;
        $this->object = new Story($this->story);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::__destruct
     */
    public function tearDown()
    {
        $this->story->delete();
        $this->object = null;
        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::getName
     * @todo Implement testGetName().
     */
    public function testGetName()
    {
        $this->assertEquals(Attribute::NAME_STORY, $this->object->getName());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::getValue
     */
    public function testGetValue()
    {
        $this->assertSame($this->story, $this->object->getValue());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::asString
     */
    public function testAsString()
    {
        $this->assertInternalType('string', $this->object->asString());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::loadFromString
     */
    public function testLoadFromString()
    {
        $loaded = Story::loadFromString(self::$application, $this->object->asString());

        $this->assertEquals($this->object, $loaded);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::__construct
     * @expectedException \InvalidArgumentException
     */
    public function testConstructWrongElement()
    {
        new Story(static::$records['record_1']);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::loadFromString
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFromStringWrongElement()
    {
        Story::loadFromString(self::$application, static::$records['record_1']->get_serialize_key());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::loadFromString
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFromStringWrongStory()
    {
        \PHPUnit_Framework_Error_Warning::$enabled = false;

        Story::loadFromString(self::$application, self::$collection->get_databox()->get_sbas_id() . '_0');
    }
}
