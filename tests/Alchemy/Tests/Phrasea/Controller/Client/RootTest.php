<?php

namespace Alchemy\Tests\Phrasea\Controller\Client;

class RootTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Root::connect
     * @covers Alchemy\Phrasea\Controller\Client\Root::call
     * @covers Alchemy\Phrasea\Controller\Client\Root::getClient
     * @covers Alchemy\Phrasea\Controller\Client\Root::getDefaultClientStartPage
     * @covers Alchemy\Phrasea\Controller\Client\Root::getQueryStartPage
     * @covers Alchemy\Phrasea\Controller\Client\Root::getHelpStartPage
     * @covers Alchemy\Phrasea\Controller\Client\Root::getPublicationStartPage
     * @covers Alchemy\Phrasea\Controller\Client\Root::getGridProperty
     * @covers Alchemy\Phrasea\Controller\Client\Root::getDocumentStorageAccess
     * @covers Alchemy\Phrasea\Controller\Client\Root::getTabSetup
     * @covers Alchemy\Phrasea\Controller\Client\Root::getCssFile
     */
    public function testGetClient()
    {
        $this->authenticate(self::$DI['app']);
        self::$DI['client']->request("GET", "/client/");
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }
}
