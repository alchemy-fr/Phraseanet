<?php

namespace Alchemy\Phrasea\Controller;

use Symfony\Component\HttpFoundation\Request;
use Alchemy\Phrasea\Application;

require_once __DIR__ . '/../../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class RecordsRequestTest extends \PhraseanetPHPUnitAuthenticatedAbstract
{

    public function testSimple()
    {
        $request = new Request(array(
                'lst' => implode(';', array(
                    self::$records['record_24']->get_serialize_key(),
                    self::$records['record_24']->get_serialize_key(),
                    self::$records['record_2']->get_serialize_key(),
                    self::$records['record_story_2']->get_serialize_key(),
                    self::$records['record_no_access']->get_serialize_key(),
                    self::$records['record_no_access_by_status']->get_serialize_key(),
                    '',
                    '0_490',
                    '0_',
                    '_490',
                    '_',
                ))
            ));

        $records = RecordsRequest::fromRequest(self::$application, $request);

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
        $this->assertContains(self::$records['record_24']->get_serialize_key(), $exploded);
        $this->assertContains(self::$records['record_2']->get_serialize_key(), $exploded);
        $this->assertContains(self::$records['record_story_2']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$records['record_no_access']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$records['record_no_access_by_status']->get_serialize_key(), $exploded);
    }

    public function testSimpleSimple()
    {
        $request = new Request(array(
                'lst' => implode(';', array(
                    self::$records['record_2']->get_serialize_key(),
                ))
            ));

        $records = RecordsRequest::fromRequest(self::$application, $request);

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
        $this->assertContains(self::$records['record_2']->get_serialize_key(), $exploded);
    }

    public function testSimpleWithoutSbasRights()
    {
        self::$application['phraseanet.user']->ACL()
            ->update_rights_to_sbas(self::$records['record_2']->get_sbas_id(), array('bas_chupub' => 0));

        $request = new Request(array(
                'lst' => implode(';', array(
                    self::$records['record_2']->get_serialize_key(),
                ))
            ));

        $records = RecordsRequest::fromRequest(self::$application, $request, false, array(), array('bas_chupub'));

        $this->assertEquals(0, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertNull($records->basket());
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals('', $serialized);
        $this->assertNotContains(self::$records['record_2']->get_serialize_key(), $exploded);
    }

    public function testSimpleWithoutBasRights()
    {
        self::$application['phraseanet.user']->ACL()
            ->update_rights_to_base(self::$records['record_2']->get_base_id(), array('chgstatus' => 0));

        $request = new Request(array(
                'lst' => implode(';', array(
                    self::$records['record_2']->get_serialize_key(),
                ))
            ));

        $records = RecordsRequest::fromRequest(self::$application, $request, false, array('chgstatus'));

        $this->assertEquals(0, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertNull($records->basket());
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals('', $serialized);
        $this->assertNotContains(self::$records['record_2']->get_serialize_key(), $exploded);
    }

    public function testSimpleFlatten()
    {
        $request = new Request(array(
                'lst' => implode(';', array(
                    self::$records['record_24']->get_serialize_key(),
                    self::$records['record_24']->get_serialize_key(),
                    self::$records['record_2']->get_serialize_key(),
                    self::$records['record_story_2']->get_serialize_key(),
                    self::$records['record_no_access']->get_serialize_key(),
                    self::$records['record_no_access_by_status']->get_serialize_key(),
                ))
            ));

        $records = RecordsRequest::fromRequest(self::$application, $request, true);

        $this->assertEquals(2, count($records));
        $this->assertEquals(5, count($records->received()));
        $this->assertEquals(0, count($records->stories()));
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());
        $this->assertNull($records->basket());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals(2, count($exploded));
        $this->assertContains(self::$records['record_2']->get_serialize_key(), $exploded);
        $this->assertContains(self::$records['record_24']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$records['record_story_2']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$records['record_no_access']->get_serialize_key(), $exploded);
        $this->assertNotContains(self::$records['record_no_access_by_status']->get_serialize_key(), $exploded);
    }

    public function testSimpleBasket()
    {
        $basketElement = $this->insertOneBasketElement();
        $request = new Request(array('ssel' => $basketElement->getBasket()->getId()));

        $records = RecordsRequest::fromRequest(self::$application, $request);

        $this->assertEquals(1, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertEquals(0, count($records->stories()));
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());
        $this->assertEquals($basketElement->getBasket(), $records->basket());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals(1, count($exploded));
        $this->assertContains($basketElement->getRecord(self::$application)->get_serialize_key(), $exploded);
    }

    public function getBasket()
    {
        $elements = array(
            self::$records['record_24'],
            self::$records['record_2'],
            self::$records['record_no_access'],
            self::$records['record_no_access_by_status'],
        );

        $basket = new \Entities\Basket();
        $basket->setName('test');
        $basket->setOwner(self::$application['phraseanet.user']);

        self::$application['EM']->persist($basket);
        self::$application['EM']->flush();

        foreach ($elements as $element) {
            $basket_element = new \Entities\BasketElement();
            $basket_element->setRecord($element);
            $basket_element->setBasket($basket);
            $basket->addBasketElement($basket_element);
            self::$application['EM']->persist($basket_element);
            self::$application['EM']->flush();
        }

        return $basket;
    }

    public function testSimpleStory()
    {
        $story = $this->getStoryWZ();
        $request = new Request(array('story' => $story->getId()));

        $records = RecordsRequest::fromRequest(self::$application, $request);

        $this->assertEquals(1, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertEquals(1, count($records->stories()));
        $this->assertInstanceOf('record_adapter', $records->singleStory());
        $this->assertTrue($records->isSingleStory());
        $this->assertEquals(array($story->getRecord(self::$application)->get_databox()), $records->databoxes());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals(1, count($exploded));
        $this->assertContains($story->getRecord(self::$application)->get_serialize_key(), $exploded);
    }

    public function testSimpleStoryFlatten()
    {
        $story = $this->getStoryWZ();
        $request = new Request(array('story' => $story->getId()));

        $records = RecordsRequest::fromRequest(self::$application, $request, true);

        $this->assertEquals(0, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertEquals(0, count($records->stories()));
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());
        $this->assertEquals(array(), $records->databoxes());

        $serialized = $records->serializedList();
        $exploded = explode(';', $serialized);

        $this->assertEquals('', $serialized);
        $this->assertNotContains($story->getRecord(self::$application)->get_serialize_key(), $exploded);
    }

    protected function getStoryWZ()
    {
        $story = new \Entities\StoryWZ();
        $story->setRecord(self::$records['record_story_2']);
        $story->setUser(self::$application['phraseanet.user']);

        self::$application['EM']->persist($story);
        self::$application['EM']->flush();

        return $story;
    }
}
