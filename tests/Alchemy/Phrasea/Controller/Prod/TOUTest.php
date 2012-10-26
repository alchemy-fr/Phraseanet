<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Application;

class TOUTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public static function tearDownAfterClass()
    {
        $application = new Application('test');
        self::giveRightsToUser($application, self::$DI['user_alt2']);
        self::$DI['user_alt2']->ACL()->revoke_access_from_bases(array(self::$DI['collection_no_access']->get_base_id()));
        self::$DI['user_alt2']->ACL()->set_masks_on_base(self::$DI['collection_no_access_by_status']->get_base_id(), '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000');
        parent::tearDownAfterClass();
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\TOU::displayTermsOfUse
     */
    public function testGetTOUNotAJAX()
    {
        self::$DI['client']->request('GET', '/prod/TOU/');
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\TOU::denyTermsOfUse
     */
    public function testGetTOUAJAX()
    {
        $this->XMLHTTPRequest('GET', '/prod/TOU/');
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\TOU::displayTermsOfUse
     */
    public function testDenyTOU()
    {
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
        $databox = array_shift($databoxes);
        self::$DI['app']['phraseanet.user'] = self::$DI['user_alt2'];
        $this->XMLHTTPRequest('POST', '/prod/TOU/deny/'.$databox->get_sbas_id() .'/');
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response, $databoxes);

        foreach ($databox->get_collections() as $collection) {
            $this->assertFalse(self::$DI['user_alt2']->ACL()->has_access_to_base($collection->get_base_id()));
        }
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\TOU::denyTermsOfUse
     * @covers Alchemy\Phrasea\Controller\Prod\TOU::connect
     * @covers Alchemy\Phrasea\Controller\Prod\TOU::call
     */
    public function testDenyTOURequireAuthentication()
    {
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
        $databox = array_shift($databoxes);
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/prod/TOU/deny/'. $databox->get_sbas_id() .'/');
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        unset($databoxes);
    }
}
