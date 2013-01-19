<?php

namespace Alchemy\Tests\Phrasea\Controller;

use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Application;
use Symfony\Component\HttpFoundation\Request;

class RecordsRequestTest extends \PhraseanetPHPUnitAuthenticatedAbstract
{

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$DI['app'] = new Application('test');

        self::giveRightsToUser(self::$DI['app'], self::$DI['user']);
        self::$DI['user']->ACL()->revoke_access_from_bases(array(self::$DI['collection_no_access']->get_base_id()));
        self::$DI['user']->ACL()->set_masks_on_base(self::$DI['collection_no_access_by_status']->get_base_id(), '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000');
    }

    public function testSimple()
    {
        $request = new Request(array(
                'lst' => implode(';', array(
                    self::$DI['record_24']->get_serialize_key(),
                    self::$DI['record_24']->get_serialize_key(),
                    self::$DI['record_2']->get_serialize_key(),
                    self::$DI['record_story_2']->get_serialize_key(),
                    self::$DI['record_no_access']->get_serialize_key(),
                    self::$DI['record_no_access_by_status']->get_serialize_key(),
                    '',
                    '0_490',
                    '0_',
                    '_490',
                    '_',
                ))
            ));

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
        $this->assertContains(self::$DI['record_24']->get_serialize_key(), $exploded);
        $this->assertContains(self::$DI['record_2']->get_serialize_key(), $exploded);
        $this->assertContains(self::$DI['record_story_2']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$DI['record_no_access']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$DI['record_no_access_by_status']->get_serialize_key(), $exploded);
    }

    public function testSimpleSimple()
    {
        $request = new Request(array(
                'lst' => implode(';', array(
                    self::$DI['record_2']->get_serialize_key(),
                ))
            ));

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
        self::$DI['app']['phraseanet.user']->ACL()
            ->update_rights_to_sbas(self::$DI['record_2']->get_sbas_id(), array('bas_chupub' => 0));

        $request = new Request(array(
                'lst' => implode(';', array(
                    self::$DI['record_2']->get_serialize_key(),
                ))
            ));

        $records = RecordsRequest::fromRequest(self::$DI['app'], $request, false, array(), array('bas_chupub'));

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
        self::$DI['app']['phraseanet.user']->ACL()
            ->update_rights_to_base(self::$DI['record_2']->get_base_id(), array('chgstatus' => 0));

        $request = new Request(array(
                'lst' => implode(';', array(
                    self::$DI['record_2']->get_serialize_key(),
                ))
            ));

        $records = RecordsRequest::fromRequest(self::$DI['app'], $request, false, array('chgstatus'));

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
        $request = new Request(array(
                'lst' => implode(';', array(
                    self::$DI['record_24']->get_serialize_key(),
                    self::$DI['record_24']->get_serialize_key(),
                    self::$DI['record_2']->get_serialize_key(),
                    self::$DI['record_story_2']->get_serialize_key(),
                    self::$DI['record_no_access']->get_serialize_key(),
                    self::$DI['record_no_access_by_status']->get_serialize_key(),
                ))
            ));

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
        $this->assertContains(self::$DI['record_24']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$DI['record_story_2']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$DI['record_no_access']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$DI['record_no_access_by_status']->get_serialize_key(), $exploded);
    }

    public function testSimpleBasket()
    {
        $basketElement = $this->insertOneBasketElement();
        $request = new Request(array('ssel' => $basketElement->getBasket()->getId()));

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

    public function getBasket()
    {
        $elements = array(
            self::$DI['record_24'],
            self::$DI['record_2'],
            self::$DI['record_no_access'],
            self::$DI['record_no_access_by_status'],
        );

        $basket = new \Entities\Basket();
        $basket->setName('test');
        $basket->setOwner(self::$DI['app']['phraseanet.user']);

        self::$DI['app']['EM']->persist($basket);
        self::$DI['app']['EM']->flush();

        foreach ($elements as $element) {
            $basket_element = new \Entities\BasketElement();
            $basket_element->setRecord($element);
            $basket_element->setBasket($basket);
            $basket->addBasketElement($basket_element);
            self::$DI['app']['EM']->persist($basket_element);
            self::$DI['app']['EM']->flush();
        }

        return $basket;
    }

    public function testSimpleStory()
    {
        $story = $this->getStoryWZ();
        $request = new Request(array('story' => $story->getId()));

        $records = RecordsRequest::fromRequest(self::$DI['app'], $request);

        $this->assertEquals(1, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertEquals(1, count($records->stories()));
        $this->assertInstanceOf('record_adapter', $records->singleStory());
        $this->assertTrue($records->isSingleStory());
        $this->assertEquals(array($story->getRecord(self::$DI['app'])->get_databox()), $records->databoxes());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals(1, count($exploded));
        $this->assertContains($story->getRecord(self::$DI['app'])->get_serialize_key(), $exploded);
    }

    public function testSimpleStoryFlatten()
    {
        $story = $this->getStoryWZ();
        $request = new Request(array('story' => $story->getId()));

        $records = RecordsRequest::fromRequest(self::$DI['app'], $request, true);

        $this->assertEquals(0, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertEquals(0, count($records->stories()));
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());
        $this->assertEquals(array(), $records->databoxes());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals('', $serialized);
        $this->assertNotContains($story->getRecord(self::$DI['app'])->get_serialize_key(), $exploded);
    }

    protected function getStoryWZ()
    {
        $story = new \Entities\StoryWZ();
        $story->setRecord(self::$DI['record_story_2']);
        $story->setUser(self::$DI['app']['phraseanet.user']);

        self::$DI['app']['EM']->persist($story);
        self::$DI['app']['EM']->flush();

        return $story;
    }
}
