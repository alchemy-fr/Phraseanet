<?php

namespace Alchemy\Tests\Phrasea\Border\Attribute;

use Alchemy\Phrasea\Border\Attribute\Story;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class StoryTest extends \PhraseanetTestCase
{
    public function testGetName()
    {
        $story = new Story(self::$DI['record_story_1']);
        $this->assertEquals(AttributeInterface::NAME_STORY, $story->getName());
    }

    public function testGetValue()
    {
        $record = self::$DI['record_story_1'];
        $story = new Story($record);
        $this->assertSame($record, $story->getValue());
    }

    public function testAsString()
    {
        $story = new Story(self::$DI['record_story_1']);
        $this->assertIsString($story->asString());
    }

    public function testLoadFromString()
    {
        $story = new Story(self::$DI['record_story_1']);
        $loaded = Story::loadFromString(self::$DI['app'], $story->asString());
        $this->assertEquals($story, $loaded);
    }

    public function testConstructWrongElement()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Story(self::$DI['record_1']);
    }

    public function testLoadFromStringWrongElement()
    {
        $this->expectException(\InvalidArgumentException::class);
        Story::loadFromString(self::$DI['app'], self::$DI['record_1']->get_serialize_key());
    }

    public function testLoadFromStringWrongStory()
    {
        $this->expectException(\InvalidArgumentException::class);
        \PHPUnit\Framework\Error\Warning::$enabled = false;

        Story::loadFromString(self::$DI['app'], self::$DI['collection']->get_databox()->get_sbas_id() . '_0');
    }
}
