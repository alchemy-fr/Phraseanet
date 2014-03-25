<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Symfony\Component\EventDispatcher\Event;

class DownloadTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Download::download
     */
    public function testDownloadRecords()
    {
        $triggered = false;
        self::$DI['app']['dispatcher']->addListener(PhraseaEvents::EXPORT_CREATE, function (Event $event) use (&$triggered) {
            $triggered = true;
        });
        self::$DI['client']->request('POST', '/prod/download/', [
            'lst'               => self::$DI['record_1']->get_serialize_key(),
            'ssttid'            => '',
            'obj'               => ['preview', 'document'],
            'title'             => 'export_title_test',
            'businessfields'    => '1'
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertRegExp('#/download/[a-zA-Z0-9]{8,32}/#', $response->headers->get('location'));
        $this->assertTrue($triggered);
        unset($response, $eventManagerStub);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Download::download
     */
    public function testDownloadRestricted()
    {
        $triggered = false;
        self::$DI['app']['dispatcher']->addListener(PhraseaEvents::EXPORT_CREATE, function (Event $event) use (&$triggered) {
            $triggered = true;
        });
        self::$DI['app']['authentication']->setUser(self::$DI['user']);

        $stubbedACL = $this->getMockBuilder('\ACL')
            ->disableOriginalConstructor()
            ->getMock();

        //has_right_on_base return true
        $stubbedACL->expects($this->any())
            ->method('has_right_on_bas')
            ->will($this->returnValue(false));

        //has_access_to_subdef return true
        $stubbedACL->expects($this->any())
            ->method('is_restricted_download')
            ->will($this->returnValue(true));

        //has_access_to_subdef return true
        $stubbedACL->expects($this->any())
            ->method('remaining_download')
            ->will($this->returnValue(0));

        $aclProvider = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ACLProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $aclProvider->expects($this->any())
            ->method('get')
            ->will($this->returnValue($stubbedACL));

        self::$DI['app']['acl'] = $aclProvider;

        self::$DI['client']->request('POST', '/prod/download/', [
            'lst'               => self::$DI['record_1']->get_serialize_key(),
            'ssttid'            => '',
            'obj'               => ['preview', 'document'],
            'title'             => 'export_title_test',
            'businessfields'    => '1'
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertTrue($triggered);
        $this->assertRegExp('#/download/[a-zA-Z0-9]{8,32}/#', $response->headers->get('location'));
        unset($response, $eventManagerStub);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Download::download
     */
    public function testDownloadBasket()
    {
        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 4);

        $triggered = false;
        self::$DI['app']['dispatcher']->addListener(PhraseaEvents::EXPORT_CREATE, function (Event $event) use (&$triggered) {
            $triggered = true;
        });

        self::$DI['client']->request('POST', '/prod/download/', [
            'lst'               => '',
            'ssttid'            => $basket->getId(),
            'obj'               => ['preview', 'document'],
            'title'             => 'export_title_test',
            'businessfields'    => '1'
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertTrue($triggered);
        $this->assertRegExp('#/download/[a-zA-Z0-9]{8,32}/#', $response->headers->get('location'));
        unset($response, $eventManagerStub);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Download::download
     */
    public function testDownloadBasketValidation()
    {
        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 4);

        $triggered = false;
        self::$DI['app']['dispatcher']->addListener(PhraseaEvents::EXPORT_CREATE, function (Event $event) use (&$triggered) {
            $triggered = true;
        });

        self::$DI['client']->request('POST', '/prod/download/', [
            'lst'               => '',
            'ssttid'            => $basket->getId(),
            'obj'               => ['preview', 'document'],
            'title'             => 'export_title_test',
            'businessfields'    => '1'
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertTrue($triggered);
        $this->assertRegExp('#/download/[a-zA-Z0-9]{8,32}/#', $response->headers->get('location'));
        unset($response, $eventManagerStub);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Download::connect
     * @covers Alchemy\Phrasea\Controller\Prod\Download::call
     */
    public function testRequireAuthentication()
    {
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/prod/download/');
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }
}
