<?php

require_once __DIR__ . '/../../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Controller\Prod\Record\Property;
use Symfony\Component\HttpFoundation\Request;

class PropertyTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Record\Property::displayProperty
     */
    public function testDisplayProperty()
    {
        $property = new Property();
        $request = Request::create('/prod/records/property/', 'GET', array(
                'lst' => implode(';', array(self::$DI['record_no_access']->get_serialize_key(), self::$DI['record_1']->get_serialize_key(), self::$DI['record_4']->get_serialize_key()))
                ), array(), array(), array('HTTP_X-Requested-With' => 'XMLHttpRequest'));
        $response = $property->displayProperty(self::$DI['app'], $request);
        $this->assertTrue($response->isOk());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Prod\Record\Property::displayProperty
     */
    public function testDisplayPropertyNotXMLHTTPRequets()
    {
        $property = new Property();
        $request = Request::create('/prod/records/property/', 'GET');
        $property->displayProperty(self::$DI['app'], $request);
        unset($property, $request);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Record\Property::changeStatus
     */
    public function testChangeSTatus()
    {
        $property = new Property();
        $request = Request::create('/prod/records/property/status/', 'POST', array(
                'apply_to_children' => array(self::$DI['record_story_1']->get_sbas_id() => true),
                'status'                                   => array(
                    self::$DI['record_1']->get_sbas_id() => array(6     => true, 8     => true, 11    => true)
                ),
                'lst' => implode(';', array(
                    self::$DI['record_1']->get_serialize_key(), self::$DI['record_story_1']->get_serialize_key()
                ))
            ));
        $response = $property->changeStatus(self::$DI['app'], $request);
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertArrayHasKey('updated', $datas);

        $record = new \record_adapter(self::$DI['app'], self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $story = new \record_adapter(self::$DI['app'], self::$DI['record_story_1']->get_sbas_id(), self::$DI['record_story_1']->get_record_id());

        $recordStatus = strrev($record->get_status());
        $storyStatus = strrev($story->get_status());

        $this->assertEquals(1, substr($recordStatus, 6, 1));
        $this->assertEquals(1, substr($recordStatus, 8, 1));
        $this->assertEquals(1, substr($recordStatus, 11, 1));

        $this->assertEquals(1, substr($storyStatus, 6, 1));
        $this->assertEquals(1, substr($storyStatus, 8, 1));
        $this->assertEquals(1, substr($storyStatus, 11, 1));

        foreach ($story->get_children() as $child) {
            $childStatus = strrev($child->get_status());
            $this->assertEquals(1, substr($childStatus, 6, 1));
            $this->assertEquals(1, substr($childStatus, 8, 1));
            $this->assertEquals(1, substr($childStatus, 11, 1));
        }

        unset($property, $request, $response, $datas, $story, $record);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Record\Property::changeType
     */
    public function testChangeType()
    {
        $property = new Property();
        $request = Request::create('/prod/records/property/type/', 'POST', array(
                'lst' => implode(';', array(
                    self::$DI['record_1']->get_serialize_key(), self::$DI['record_2']->get_serialize_key()
                )),
                'types' => array(
                    self::$DI['record_1']->get_serialize_key() => 'document',
                    self::$DI['record_2']->get_serialize_key() => 'flash',
                )
            ));
        $response = $property->changeType(self::$DI['app'], $request);
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertArrayHasKey('updated', $datas);

        $record = new \record_adapter(self::$DI['app'], self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $record2 = new \record_adapter(self::$DI['app'], self::$DI['record_2']->get_sbas_id(), self::$DI['record_2']->get_record_id());

        $this->assertEquals('document', $record->get_type());
        $this->assertEquals('flash', $record2->get_type());
        unset($property, $request, $response, $datas, $record2, $record);
    }
}
