<?php

namespace Alchemy\Tests\Phrasea\Controller\Client;

use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;

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
        \phrasea::start(self::$DI['app']['phraseanet.configuration']);
        $auth = new \Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);
        self::$DI['client']->request("GET", "/client/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Root::getClientLanguage
     */
    public function testGetLanguage()
    {
        self::$DI['client']->request("GET", "/client/language/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Root::getClientPublications
     */
    public function testGetPublications()
    {
        self::$DI['client']->request("GET", "/client/publications/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Root::getClientHelp
     */
    public function testGetClientHelp()
    {
        self::$DI['client']->request("GET", "/client/help/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Root::query
     * @covers Alchemy\Phrasea\Controller\Client\Root::buildQueryFromRequest
     */
    public function testExecuteQuery()
    {
        $queryParameters = array();
        $queryParameters["mod"] = self::$DI['user']->getPrefs('client_view') ? : '3X6';
        $queryParameters["bas"] = array_keys(self::$DI['user']->ACL()->get_granted_base());
        $queryParameters["qry"] = self::$DI['user']->getPrefs('start_page_query') ? : 'all';
        $queryParameters["pag"] = 0;
        $queryParameters["search_type"] = SearchEngineOptions::RECORD_RECORD;
        $queryParameters["qryAdv"] = '';
        $queryParameters["opAdv"] = array();
        $queryParameters["status"] = array();
        $queryParameters["recordtype"] = SearchEngineOptions::TYPE_ALL;
        $queryParameters["sort"] = self::$DI['app']['phraseanet.registry']->get('GV_phrasea_sort', '');
        $queryParameters["infield"] = array();
        $queryParameters["ord"] = SearchEngineOptions::SORT_MODE_DESC;

        self::$DI['client']->request("POST", "/client/query/", $queryParameters);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }
}
