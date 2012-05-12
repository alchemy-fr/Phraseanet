<?php

namespace Alchemy\Phrasea\Border\Attribute;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class StoryTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var Story
     */
    protected $object;

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = new Story(static::$records['record_story_1']);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::__destruct
     */
    public function tearDown()
    {
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
        $this->assertSame(static::$records['record_story_1'], $this->object->getValue());
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
        $loaded = Story::loadFromString($this->object->asString());

        $this->assertEquals($this->object, $loaded);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::loadFromString
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFromStringWrongElement()
    {
        Story::loadFromString(static::$records['record_1']->get_serialize_key());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::loadFromString
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFromStringWrongStory()
    {
        \PHPUnit_Framework_Error_Warning::$enabled = false;

        Story::loadFromString(self::$collection->get_databox()->get_sbas_id() . '_0');
    }
}
