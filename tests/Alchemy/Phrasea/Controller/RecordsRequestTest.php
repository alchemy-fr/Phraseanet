<?php

namespace Alchemy\Phrasea\Controller;

use Symfony\Component\HttpFoundation\Request;
use Alchemy\Phrasea\Application;

require_once __DIR__ . '/../../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class RecordsRequestTest extends \PhraseanetPHPUnitAuthenticatedAbstract
{

    public function testSimple()
    {
        $application = new Application();
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

        $records = RecordsRequest::fromRequest($application, $request);

        $this->assertEquals(3, count($records));
        $this->assertEquals(5, count($records->received()));
        $this->assertEquals(1, count($records->databoxes()));
        $this->assertEquals(1, count($records->stories()));
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());
        $this->assertNull($records->basket());

        $serialized = $records->serializedList();

        $this->assertEquals(3, count(explode(';', $serialized)));
        $this->assertTrue(false !== strpos($serialized, self::$records['record_24']->get_serialize_key()));
        $this->assertTrue(false !== strpos($serialized, self::$records['record_2']->get_serialize_key()));
        $this->assertTrue(false !== strpos($serialized, self::$records['record_story_2']->get_serialize_key()));
        $this->assertTrue(false === strpos($serialized, self::$records['record_no_access']->get_serialize_key()));
        $this->assertTrue(false === strpos($serialized, self::$records['record_no_access_by_status']->get_serialize_key()));
    }

    public function testSimpleSimple()
    {
        $application = new Application();
        $request = new Request(array(
                'lst' => implode(';', array(
                    self::$records['record_2']->get_serialize_key(),
                ))
            ));

        $records = RecordsRequest::fromRequest($application, $request);

        $this->assertEquals(1, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertEquals(1, count($records->databoxes()));
        $this->assertEquals(0, count($records->stories()));
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());
        $this->assertNull($records->basket());

        $serialized = $records->serializedList();

        $this->assertEquals(1, count(explode(';', $serialized)));
        $this->assertTrue(false !== strpos($serialized, self::$records['record_2']->get_serialize_key()));
    }

    public function testSimpleWithoutSbasRights()
    {
        $application = new Application();
        self::$core->getAuthenticatedUser()->ACL()
            ->update_rights_to_sbas(self::$records['record_2']->get_sbas_id(), array('bas_chupub' => 0));

        $request = new Request(array(
                'lst' => implode(';', array(
                    self::$records['record_2']->get_serialize_key(),
                ))
            ));

        $records = RecordsRequest::fromRequest($application, $request, false, array(), array('bas_chupub'));

        $this->assertEquals(0, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertNull($records->basket());
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());

        $serialized = $records->serializedList();

        $this->assertEquals('', $serialized);
        $this->assertTrue(false === strpos($serialized, self::$records['record_2']->get_serialize_key()));
    }

    public function testSimpleWithoutBasRights()
    {
        $application = new Application();
        self::$core->getAuthenticatedUser()->ACL()
            ->update_rights_to_base(self::$records['record_2']->get_base_id(), array('chgstatus' => 0));

        $request = new Request(array(
                'lst' => implode(';', array(
                    self::$records['record_2']->get_serialize_key(),
                ))
            ));

        $records = RecordsRequest::fromRequest($application, $request, false, array('chgstatus'));

        $this->assertEquals(0, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertNull($records->basket());
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());

        $serialized = $records->serializedList();

        $this->assertEquals('', $serialized);
        $this->assertTrue(false === strpos($serialized, self::$records['record_2']->get_serialize_key()));
    }

    public function testSimpleFlatten()
    {
        $application = new Application();
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

        $records = RecordsRequest::fromRequest($application, $request, true);

        $this->assertEquals(2, count($records));
        $this->assertEquals(5, count($records->received()));
        $this->assertEquals(0, count($records->stories()));
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());
        $this->assertNull($records->basket());

        $serialized = $records->serializedList();

        $this->assertEquals(2, count(explode(';', $serialized)));
        $this->assertTrue(false !== strpos($serialized, self::$records['record_24']->get_serialize_key()));
        $this->assertTrue(false !== strpos($serialized, self::$records['record_2']->get_serialize_key()));
        $this->assertTrue(false === strpos($serialized, self::$records['record_story_2']->get_serialize_key()));
        $this->assertTrue(false === strpos($serialized, self::$records['record_no_access']->get_serialize_key()));
        $this->assertTrue(false === strpos($serialized, self::$records['record_no_access_by_status']->get_serialize_key()));
    }

    public function testSimpleBasket()
    {
        $application = new Application();
        $elements = array(
            self::$records['record_24'],
            self::$records['record_2'],
            self::$records['record_no_access'],
            self::$records['record_no_access_by_status'],
        );

        $basket = new \Entities\Basket();
        $basket->setName('test');
        $basket->setOwner(self::$core->getAuthenticatedUser());

        foreach ($elements as $element) {
            $basket_element = new \Entities\BasketElement();
            $basket_element->setRecord($element);
            $basket_element->setBasket($basket);
            $basket->addBasketElement($basket_element);
            self::$core['EM']->persist($basket_element);
        }
        self::$core['EM']->persist($basket);
        self::$core['EM']->flush();

        $request = new Request(array(
                'ssel' => $basket->getId(),
            ));

        $records = RecordsRequest::fromRequest($application, $request);

        $this->assertEquals(2, count($records));
        $this->assertEquals(4, count($records->received()));
        $this->assertEquals(0, count($records->stories()));
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());
        $this->assertEquals($basket, $records->basket());

        $serialized = $records->serializedList();

        $this->assertEquals(2, count(explode(';', $serialized)));
        $this->assertTrue(false !== strpos($serialized, self::$records['record_24']->get_serialize_key()));
        $this->assertTrue(false !== strpos($serialized, self::$records['record_2']->get_serialize_key()));
        $this->assertTrue(false === strpos($serialized, self::$records['record_no_access']->get_serialize_key()));
        $this->assertTrue(false === strpos($serialized, self::$records['record_no_access_by_status']->get_serialize_key()));
    }

    public function testSimpleStory()
    {
        $application = new Application();
        $story = $this->getStoryWZ();
        $request = new Request(array('story' => $story->getId()));

        $records = RecordsRequest::fromRequest($application, $request);

        $this->assertEquals(1, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertEquals(1, count($records->stories()));
        $this->assertInstanceOf('record_adapter', $records->singleStory());
        $this->assertTrue($records->isSingleStory());
        $this->assertEquals(array($story->getRecord()->get_databox()), $records->databoxes());

        $serialized = $records->serializedList();

        $this->assertEquals(1, count(explode(';', $serialized)));
        $this->assertTrue(false !== strpos($serialized, $story->getRecord()->get_serialize_key()));
    }

    public function testSimpleStoryFlatten()
    {
        $application = new Application();
        $story = $this->getStoryWZ();
        $request = new Request(array('story' => $story->getId()));

        $records = RecordsRequest::fromRequest($application, $request, true);

        $this->assertEquals(0, count($records));
        $this->assertEquals(1, count($records->received()));
        $this->assertEquals(0, count($records->stories()));
        $this->assertNull($records->singleStory());
        $this->assertFalse($records->isSingleStory());
        $this->assertEquals(array(), $records->databoxes());

        $serialized = $records->serializedList();

        $this->assertEquals('', $serialized);
        $this->assertTrue(false === strpos($serialized, $story->getRecord()->get_serialize_key()));
    }

    protected function getStoryWZ()
    {
        $story = new \Entities\StoryWZ();
        $story->setRecord(self::$records['record_story_2']);
        $story->setUser(self::$core->getAuthenticatedUser());

        self::$core['EM']->persist($story);
        self::$core['EM']->flush();

        return $story;
    }
}
