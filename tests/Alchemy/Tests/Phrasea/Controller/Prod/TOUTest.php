<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class TOUTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    public function setUp(): void
    {
        parent::setUp();
        self::resetUsersRights(self::$DI['app'], self::$DI['user_alt2']);
    }

    public function testGetTOUNotAJAX()
    {
        self::$DI['client']->request('GET', '/prod/TOU/');
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response);
    }

    public function testGetTOUAJAX()
    {
        $response = $this->XMLHTTPRequest('GET', '/prod/TOU/');
        $this->assertTrue($response->isOk());
    }

    public function testDenyTOU()
    {
        $databoxes = self::$DI['app']->getDataboxes();
        $databox = array_shift($databoxes);
        self::$DI['app']['authentication']->setUser(self::$DI['user_alt2']);
        $response = $this->XMLHTTPRequest('POST', '/prod/TOU/deny/' . $databox->get_sbas_id() . '/');
        $this->assertTrue($response->isOk());
        unset($response, $databoxes);

        foreach ($databox->get_collections() as $collection) {
            $this->assertFalse(self::$DI['app']->getAclForUser(self::$DI['user_alt2'])->has_access_to_base($collection->get_base_id()));
        }
    }

    public function testDenyTOURequireAuthentication()
    {
        $databoxes = self::$DI['app']->getDataboxes();
        $databox = array_shift($databoxes);
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/prod/TOU/deny/'. $databox->get_sbas_id() .'/');
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        unset($databoxes);
    }
}
