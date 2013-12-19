<?php

namespace Alchemy\Tests\Phrasea\Border\Attribute;

use Alchemy\Phrasea\Border\Attribute\Story;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;

class StoryTest extends \PhraseanetTestCase
{
    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::getName
     */
    public function testGetName()
    {
        $story = new Story(self::$DI['record_story_1']);
        $this->assertEquals(AttributeInterface::NAME_STORY, $story->getName());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::getValue
     */
    public function testGetValue()
    {
        $record = self::$DI['record_story_1'];
        $story = new Story($record);
        $this->assertSame($record, $story->getValue());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::asString
     */
    public function testAsString()
    {
        $story = new Story(self::$DI['record_story_1']);
        $this->assertInternalType('string', $story->asString());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::loadFromString
     */
    public function testLoadFromString()
    {
        $story = new Story(self::$DI['record_story_1']);
        $loaded = Story::loadFromString(self::$DI['app'], $story->asString());
        $this->assertEquals($story, $loaded);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::__construct
     * @expectedException \InvalidArgumentException
     */
    public function testConstructWrongElement()
    {
        new Story(self::$DI['record_1']);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::loadFromString
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFromStringWrongElement()
    {
        Story::loadFromString(self::$DI['app'], self::$DI['record_1']->get_serialize_key());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Story::loadFromString
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFromStringWrongStory()
    {
        \PHPUnit_Framework_Error_Warning::$enabled = false;

        Story::loadFromString(self::$DI['app'], self::$DI['collection']->get_databox()->get_sbas_id() . '_0');
    }
}
