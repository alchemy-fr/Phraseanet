<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Symfony\Contracts\EventDispatcher\Event;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class DownloadTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    public function testDownloadRecords()
    {
        $triggered = false;
        $randomValue = $this->setSessionFormToken('prodExportDownload');

        self::$DI['app']['dispatcher']->addListener(PhraseaEvents::EXPORT_CREATE, function (Event $event) use (&$triggered) {
            $triggered = true;
        });
        self::$DI['client']->request('POST', '/prod/download/', [
            'lst'               => self::$DI['record_1']->get_serialize_key(),
            'ssttid'            => '',
            'obj'               => ['preview', 'document'],
            'title'             => 'export_title_test',
            'businessfields'    => '1',
            'prodExportDownload_token' => $randomValue
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertMatchesRegularExpression('#/download/[a-zA-Z0-9]{8,32}/#', $response->headers->get('location'));
        $this->assertTrue($triggered);
        unset($response, $eventManagerStub);
    }

    public function testDownloadRestricted()
    {
        $triggered = false;
        $randomValue = $this->setSessionFormToken('prodExportDownload');

        self::$DI['app']['dispatcher']->addListener(PhraseaEvents::EXPORT_CREATE, function (Event $event) use (&$triggered) {
            $triggered = true;
        });
        self::$DI['app']['authentication']->setUser(self::$DI['user']);

        $stubbedACL = $this->createMock('\ACL');

        //has_right_on_base return true
        $stubbedACL
            ->method('has_right_on_base')
            ->will($this->returnValue(false));

        //has_access_to_subdef return true
        $stubbedACL
            ->method('is_restricted_download')
            ->will($this->returnValue(true));

        //has_access_to_subdef return true
        $stubbedACL
            ->method('remaining_download')
            ->will($this->returnValue(0));

        $aclProvider = $this->createMock('Alchemy\Phrasea\Authentication\ACLProvider');
        $aclProvider
            ->method('get')
            ->will($this->returnValue($stubbedACL));

        self::$DI['app']['acl'] = $aclProvider;

        self::$DI['client']->request('POST', '/prod/download/', [
            'lst'               => self::$DI['record_1']->get_serialize_key(),
            'ssttid'            => '',
            'obj'               => ['preview', 'document'],
            'title'             => 'export_title_test',
            'businessfields'    => '1',
            'prodExportDownload_token' => $randomValue
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertTrue($triggered);
        $this->assertMatchesRegularExpression('#/download/[a-zA-Z0-9]{8,32}/#', $response->headers->get('location'));
        unset($response, $eventManagerStub);
    }

    public function testDownloadBasket()
    {
        $basket = self::$DI['app']['orm.em']->find('Alchemy\Phrasea\Model\Entities\Basket', 4);
        $randomValue = $this->setSessionFormToken('prodExportDownload');

        $triggered = false;
        self::$DI['app']['dispatcher']->addListener(PhraseaEvents::EXPORT_CREATE, function (Event $event) use (&$triggered) {
            $triggered = true;
        });

        self::$DI['client']->request('POST', '/prod/download/', [
            'lst'               => '',
            'ssttid'            => $basket->getId(),
            'obj'               => ['preview', 'document'],
            'title'             => 'export_title_test',
            'businessfields'    => '1',
            'prodExportDownload_token' => $randomValue
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertTrue($triggered);
        $this->assertMatchesRegularExpression('#/download/[a-zA-Z0-9]{8,32}/#', $response->headers->get('location'));
        unset($response, $eventManagerStub);
    }

    public function testDownloadBasketValidation()
    {
        $basket = self::$DI['app']['orm.em']->find('Alchemy\Phrasea\Model\Entities\Basket', 4);
        $randomValue = $this->setSessionFormToken('prodExportDownload');

        $triggered = false;
        self::$DI['app']['dispatcher']->addListener(PhraseaEvents::EXPORT_CREATE, function (Event $event) use (&$triggered) {
            $triggered = true;
        });

        self::$DI['client']->request('POST', '/prod/download/', [
            'lst'               => '',
            'ssttid'            => $basket->getId(),
            'obj'               => ['preview', 'document'],
            'title'             => 'export_title_test',
            'businessfields'    => '1',
            'prodExportDownload_token' => $randomValue
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertTrue($triggered);
        $this->assertMatchesRegularExpression('#/download/[a-zA-Z0-9]{8,32}/#', $response->headers->get('location'));
        unset($response, $eventManagerStub);
    }

    public function testRequireAuthentication()
    {
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/prod/download/');
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }
}
