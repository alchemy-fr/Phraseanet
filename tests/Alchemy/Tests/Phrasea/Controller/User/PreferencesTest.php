<?php

namespace Alchemy\Tests\Phrasea\Controller\User;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class PreferencesTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    public function testSaveUserPref()
    {
        $response = $this->XMLHTTPRequest('POST', '/user/preferences/', ['prop' => 'prop_test', 'value' => 'val_test']);
        $this->assertTrue($response->isOk());
        $this->assertTrue(json_decode($response->getContent())->success);
        $this->assertEquals('val_test', self::$DI['app']['settings']->getUserSetting(self::$DI['user'], 'prop_test'));
    }

    public function testSaveUserPrefNoXMLHTTPRequests()
    {
        self::$DI['client']->request('POST', '/user/preferences/',  ['prop'  => 'prop_test', 'value' => 'val_test']);

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    public function testSaveTempPrefNoXMLHTTPRequests()
    {
        self::$DI['client']->request('POST', '/user/preferences/temporary/',  ['prop'  => 'prop_test', 'value' => 'val_test']);

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    public function testSaveTemporaryPref()
    {
        $response = $this->XMLHTTPRequest('POST', "/user/preferences/temporary/", [
            'prop' => 'prop_test',
            'value' => 'val_test'
        ]);
        $this->assertTrue($response->isOk());
        $this->assertTrue(json_decode($response->getContent())->success);
        $this->assertEquals('val_test', self::$DI['app']['session']->get('phraseanet.prop_test'));
    }

    public function testRequireAuthentication()
    {
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/user/preferences/');
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }
}
