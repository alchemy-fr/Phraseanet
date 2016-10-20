<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Border\File;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class PropertyTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Property::displayStatusProperty
     */
    public function testDisplayStatusProperty()
    {
        $response = $this->XMLHTTPRequest('GET', '/prod/records/property/', [
            'lst' => implode(';', [
                self::$DI['record_no_access']->get_serialize_key(),
                self::$DI['record_1']->get_serialize_key(),
                self::$DI['record_4']->get_serialize_key()
            ])
        ]);
        $this->assertTrue($response->isOk());
        unset($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Property::displayStatusProperty
     */
    public function testDisplayStatusPropertyNotXMLHTTPRequets()
    {
        self::$DI['client']->request('GET', '/prod/records/property/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Property::displayTypeProperty
     */
    public function testDisplayTypeProperty()
    {
        $response = $this->XMLHTTPRequest('GET', '/prod/records/property/type/', [
            'lst' => implode(';', [
                self::$DI['record_no_access']->get_serialize_key(),
                self::$DI['record_1']->get_serialize_key(),
                self::$DI['record_4']->get_serialize_key()
            ])
        ]);
        $this->assertTrue($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Property::displayProperty
     */
    public function testDisplayTypePropertyNotXMLHTTPRequets()
    {
        self::$DI['client']->request('GET', '/prod/records/property/type/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
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

        $acl = $this->getMockBuilder('ACL')
            ->disableOriginalConstructor()
            ->getMock();
        $acl->expects($this->any())
            ->method('has_access_to_record')
            ->with($this->isInstanceOf('\record_adapter'))
            ->will($this->returnValue(true));
        $acl->expects($this->any())
            ->method('has_right_on_base')
            ->with($this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT), $this->equalTo(\ACL::CHGSTATUS))
            ->will($this->returnValue(true));
        $acl->expects($this->any())
            ->method('has_right_on_sbas')
            ->with($this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT), $this->equalTo(\ACL::CHGSTATUS))
            ->will($this->returnValue(true));

        $aclProvider = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ACLProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $aclProvider->expects($this->any())
            ->method('get')
            ->will($this->returnValue($acl));

        self::$DI['app']['acl'] = $aclProvider;

        self::$DI['client']->request('POST', '/prod/records/property/status/', [
            'apply_to_children' => [$story->getDataboxId() => true],
            'status'                                   => [
                $record->getDataboxId() => [6     => true, 8     => true, 11    => true]
            ],
            'lst' => implode(';', [
                $record->getId(),$story->getId()
            ])
        ]);
        $response = self::$DI['client']->getResponse();
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertArrayHasKey('updated', $datas);

        $record = new \record_adapter(self::$DI['app'], $record->getDataboxId(), $record->getRecordId());
        $story = new \record_adapter(self::$DI['app'], $story->getDataboxId(), $story->getRecordId());

        $recordStatus = strrev($record->getStatus());
        $storyStatus = strrev($story->getStatus());

        $this->assertEquals(1, substr($recordStatus, 6, 1));
        $this->assertEquals(1, substr($recordStatus, 8, 1));
        $this->assertEquals(1, substr($recordStatus, 11, 1));

        $this->assertEquals(1, substr($storyStatus, 6, 1));
        $this->assertEquals(1, substr($storyStatus, 8, 1));
        $this->assertEquals(1, substr($storyStatus, 11, 1));

        foreach ($story->getChildren() as $child) {
            $childStatus = strrev($child->getStatus());
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

        self::$DI['client']->request('POST', '/prod/records/property/type/',  [
            'lst' => implode(';', [
                $record->getId(), $record2->getId()
            ]),
            'types' => [
                $record->getId() => 'document',
                $record2->getId() => 'flash',
            ]
        ]);
        $response = self::$DI['client']->getResponse();
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertArrayHasKey('updated', $datas);

        $record = new \record_adapter(self::$DI['app'], $record->getDataboxId(), $record->getRecordId());
        $record2 = new \record_adapter(self::$DI['app'], $record2->getDataboxId(), $record2->getRecordId());

        $this->assertEquals('document', $record->getType());
        $this->assertEquals('flash', $record2->getType());

        $record->delete();
        $record2->delete();

        unset($response, $datas, $record2, $record, $file);
    }
}
