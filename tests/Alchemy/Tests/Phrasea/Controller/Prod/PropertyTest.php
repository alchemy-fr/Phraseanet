<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Border\File;

class PropertyTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Property::displayStatusProperty
     */
    public function testDisplayStatusProperty()
    {
        $this->XMLHTTPRequest('GET', '/prod/records/property/', array('lst' => implode(';', array(self::$DI['record_no_access']->get_serialize_key(), self::$DI['record_1']->get_serialize_key(), self::$DI['record_4']->get_serialize_key()))));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Prod\Property::displayStatusProperty
     */
    public function testDisplayStatusPropertyNotXMLHTTPRequets()
    {
        self::$DI['client']->request('GET', '/prod/records/property/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Property::displayTypeProperty
     */
    public function testDisplayTypeProperty()
    {
        $this->XMLHTTPRequest('GET', '/prod/records/property/type/',array('lst' => implode(';', array(self::$DI['record_no_access']->get_serialize_key(), self::$DI['record_1']->get_serialize_key(), self::$DI['record_4']->get_serialize_key()))));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Prod\Property::displayProperty
     */
    public function testDisplayTypePropertyNotXMLHTTPRequets()
    {
        self::$DI['client']->request('GET', '/prod/records/property/type/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Property::changeStatus
     */
    public function testChangeStatus()
    {
        $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../../files/cestlafete.jpg'), self::$DI['collection']);
        $record = \record_adapter::createFromFile($file, self::$DI['app']);
        $record2 = \record_adapter::createFromFile($file, self::$DI['app']);
        $story = \record_adapter::createStory(self::$DI['app'], self::$DI['collection']);
        $story->appendChild($record2);

        self::$DI['client']->request('POST', '/prod/records/property/status/', array(
            'apply_to_children' => array($story->get_sbas_id() => true),
            'status'                                   => array(
                $record->get_sbas_id() => array(6     => true, 8     => true, 11    => true)
            ),
            'lst' => implode(';', array(
                $record->get_serialize_key(),$story->get_serialize_key()
            ))
        ));
        $response = self::$DI['client']->getResponse();
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertArrayHasKey('updated', $datas);

        $record = new \record_adapter(self::$DI['app'], $record->get_sbas_id(), $record->get_record_id());
        $story = new \record_adapter(self::$DI['app'], $story->get_sbas_id(), $story->get_record_id());

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

        $record->delete();
        $record2->delete();
        $story->delete();

        unset($response, $datas, $story, $record, $record2, $story, $file);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Property::changeType
     */
    public function testChangeType()
    {
        $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../../files/cestlafete.jpg'), self::$DI['collection']);
        $record = \record_adapter::createFromFile($file, self::$DI['app']);
        $record2 = \record_adapter::createFromFile($file, self::$DI['app']);

        self::$DI['client']->request('POST', '/prod/records/property/type/',  array(
            'lst' => implode(';', array(
                $record->get_serialize_key(), $record2->get_serialize_key()
            )),
            'types' => array(
                $record->get_serialize_key() => 'document',
                $record2->get_serialize_key() => 'flash',
            )
        ));
        $response = self::$DI['client']->getResponse();
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertArrayHasKey('updated', $datas);

        $record = new \record_adapter(self::$DI['app'], $record->get_sbas_id(), $record->get_record_id());
        $record2 = new \record_adapter(self::$DI['app'], $record2->get_sbas_id(), $record2->get_record_id());

        $this->assertEquals('document', $record->get_type());
        $this->assertEquals('flash', $record2->get_type());

        $record->delete();
        $record2->delete();

        unset($response, $datas, $record2, $record, $file);
    }
}
