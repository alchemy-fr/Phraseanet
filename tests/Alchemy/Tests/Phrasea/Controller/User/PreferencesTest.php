<?php

namespace Alchemy\Tests\Phrasea\Controller\User;

class PreferencesTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\User\Preferences::saveUserPref
     */
    public function testSaveUserPref()
    {
        $this->XMLHTTPRequest('POST', '/user/preferences/', ['prop'  => 'prop_test', 'value' => 'val_test']);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertTrue(json_decode($response->getContent())->success);
        $this->assertEquals('val_test', self::$DI['app']['settings']->getUserSetting(self::$DI['user'], 'prop_test'));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\User\Preferences::saveUserPref
     */
    public function testSaveUserPrefNoXMLHTTPRequests()
    {
        self::$DI['client']->request('POST', '/user/preferences/',  ['prop'  => 'prop_test', 'value' => 'val_test']);

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\User\Preferences::saveTemporaryPref
     */
    public function testSaveTempPrefNoXMLHTTPRequests()
    {
        self::$DI['client']->request('POST', '/user/preferences/temporary/',  ['prop'  => 'prop_test', 'value' => 'val_test']);

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\User\Preferences::saveTemporaryPref
     */
    public function testSaveTemporaryPref()
    {
        $this->XMLHTTPRequest('POST', "/user/preferences/temporary/", ['prop'  => 'prop_test', 'value' => 'val_test']);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertTrue(json_decode($response->getContent())->success);
        $this->assertEquals('val_test', self::$DI['app']['session']->get('phraseanet.prop_test'));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\User\Preferences::connect
     * @covers Alchemy\Phrasea\Controller\User\Preferences::call
     */
    public function testRequireAuthentication()
    {
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/user/preferences/');
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }
}
