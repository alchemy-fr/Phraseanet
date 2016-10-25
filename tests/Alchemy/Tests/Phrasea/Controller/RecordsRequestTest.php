<?php

namespace Alchemy\Tests\Phrasea\Controller;

use Alchemy\Phrasea\Controller\RecordsRequest;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 */
class RecordsRequestTest extends \PhraseanetAuthenticatedTestCase
{
    public function testSimple()
    {
        $request = new Request([
            'lst' => implode(';', [
                self::$DI['record_3']->get_serialize_key(),
                self::$DI['record_3']->get_serialize_key(),
                self::$DI['record_2']->get_serialize_key(),
                self::$DI['record_story_2']->get_serialize_key(),
                self::$DI['record_no_access']->get_serialize_key(),
                self::$DI['record_no_access_by_status']->get_serialize_key(),
                '',
                '0_490',
                '0_',
                '_490',
                '_',
            ])
        ]);

        $records = RecordsRequest::fromRequest(self::$DI['app'], $request);

        $this->assertEquals(3, count($records));
        $this->assertEquals(5, count($records->received()));
        $this->assertEquals(1, count($records->databoxes()));
        $this->assertEquals(1, count($records->stories()));
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());
        $this->assertNull($records->basket());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals(3, count($exploded));
        $this->assertContains(self::$DI['record_3']->get_serialize_key(), $exploded);
        $this->assertContains(self::$DI['record_2']->get_serialize_key(), $exploded);
        $this->assertContains(self::$DI['record_story_2']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$DI['record_no_access']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$DI['record_no_access_by_status']->get_serialize_key(), $exploded);
    }

    public function testSimpleSimple()
    {
        $request = new Request([
            'lst' => implode(';', [
                self::$DI['record_2']->get_serialize_key(),
            ])
        ]);

        $records = RecordsRequest::fromRequest(self::$DI['app'], $request);

        $this->assertEquals(1, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertEquals(1, count($records->databoxes()));
        $this->assertEquals(0, count($records->stories()));
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());
        $this->assertNull($records->basket());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals(1, count($exploded));
        $this->assertContains(self::$DI['record_2']->get_serialize_key(), $exploded);
    }

    public function testSimpleWithoutSbasRights()
    {
        self::$DI['app']->getAclForUser(self::$DI['app']->getAuthenticatedUser())
            ->update_rights_to_sbas(self::$DI['record_2']->get_sbas_id(), [\ACL::BAS_CHUPUB => 0]);

        $request = new Request([
            'lst' => implode(';', [
                self::$DI['record_2']->get_serialize_key(),
            ])
        ]);

        $records = RecordsRequest::fromRequest(self::$DI['app'], $request, false, [], [\ACL::BAS_CHUPUB]);

        $this->assertEquals(0, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertNull($records->basket());
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals('', $serialized);
        $this->assertNotContains(self::$DI['record_2']->get_serialize_key(), $exploded);
    }

    public function testSimpleWithoutBasRights()
    {
        self::$DI['app']->getAclForUser(self::$DI['app']->getAuthenticatedUser())
            ->update_rights_to_base(
                self::$DI['record_2']->get_base_id(),
                [\ACL::CHGSTATUS => 0]
            );

        $request = new Request([
            'lst' => implode(';', [
                self::$DI['record_2']->get_serialize_key(),
            ])
        ]);

        $records = RecordsRequest::fromRequest(self::$DI['app'], $request, false, [\ACL::CHGSTATUS]);

        $this->assertEquals(0, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertNull($records->basket());
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals('', $serialized);
        $this->assertNotContains(self::$DI['record_2']->get_serialize_key(), $exploded);
    }

    public function testSimpleFlatten()
    {
        $request = new Request([
            'lst' => implode(';', [
                self::$DI['record_3']->get_serialize_key(),
                self::$DI['record_3']->get_serialize_key(),
                self::$DI['record_2']->get_serialize_key(),
                self::$DI['record_story_2']->get_serialize_key(),
                self::$DI['record_no_access']->get_serialize_key(),
                self::$DI['record_no_access_by_status']->get_serialize_key(),
            ])
        ]);

        $records = RecordsRequest::fromRequest(self::$DI['app'], $request, true);

        $this->assertEquals(2, count($records));
        $this->assertEquals(5, count($records->received()));
        $this->assertEquals(0, count($records->stories()));
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());
        $this->assertNull($records->basket());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals(2, count($exploded));
        $this->assertContains(self::$DI['record_2']->get_serialize_key(), $exploded);
        $this->assertContains(self::$DI['record_3']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$DI['record_story_2']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$DI['record_no_access']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$DI['record_no_access_by_status']->get_serialize_key(), $exploded);
    }

    public function testSimpleBasket()
    {
        $basketElement = self::$DI['app']['orm.em']->find('Phraseanet:BasketElement', 1);
        $request = new Request(['ssel' => $basketElement->getBasket()->getId()]);

        $records = RecordsRequest::fromRequest(self::$DI['app'], $request);

        $this->assertEquals(1, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertEquals(0, count($records->stories()));
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());
        $this->assertEquals($basketElement->getBasket(), $records->basket());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals(1, count($exploded));
        $this->assertContains($basketElement->getRecord(self::$DI['app'])->get_serialize_key(), $exploded);
    }

    public function testSimpleStory()
    {
        $story = $this->getStoryWZ();
        $request = new Request(['story' => $story->getId()]);

        $records = RecordsRequest::fromRequest(self::$DI['app'], $request);

        $this->assertEquals(1, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertEquals(1, count($records->stories()));
        $this->assertInstanceOf('record_adapter', $records->singleStory());
        $this->assertTrue($records->isSingleStory());
        $this->assertEquals([$story->getRecord(self::$DI['app'])->get_databox()], $records->databoxes());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals(1, count($exploded));
        $this->assertContains($story->getRecord(self::$DI['app'])->get_serialize_key(), $exploded);
    }

    public function testSimpleStoryFlatten()
    {
        $story = $this->getStoryWZ();
        $request = new Request(['story' => $story->getId()]);
        $records = RecordsRequest::fromRequest(self::$DI['app'], $request, true);

        $this->assertEquals($story->getRecord(self::$DI['app'])->get_children()->get_count(), count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertEquals(0, count($records->stories()));
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());
        $this->assertCount(0, $records->databoxes());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);
        $expected = '';
        foreach ($story->getRecord(self::$DI['app'])->get_children() as $record) {
            $expected .= $expected === '' ? $record->get_serialize_key() : ';' . $record->get_serialize_key();
        }
        $this->assertEquals($expected, $serialized);
        $this->assertNotContains($story->getRecord(self::$DI['app'])->get_serialize_key(), $exploded);
    }

    public function testSimpleStoryFlattenAndPreserve()
    {
        $story = $this->getStoryWZ();

        $request = new Request(['story' => $story->getId()]);

        $records = RecordsRequest::fromRequest(self::$DI['app'], $request, RecordsRequest::FLATTEN_YES_PRESERVE_STORIES);

        $this->assertEquals(1 + $story->getRecord(self::$DI['app'])->get_children()->get_count(), count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertEquals(1, count($records->stories()));
        $this->assertInstanceOf('\record_adapter', $records->singleStory());
        $this->assertTrue($records->isSingleStory());
        $this->assertCount(1, $records->databoxes());

        $serialized = $records->serializedList();

        $this->assertEquals($story->getRecord(self::$DI['app'])->get_serialize_key(), $serialized);
    }

    private function getStoryWZ()
    {
        return self::$DI['app']['orm.em']->find('Phraseanet:StoryWZ', 1);
    }
}
