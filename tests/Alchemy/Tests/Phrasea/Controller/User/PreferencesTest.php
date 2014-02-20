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
        self::$DI['app']['authentication']->setUser($this->getMockBuilder('Alchemy\Phrasea\Model\Entities\User')
            ->setMethods(['addSetting'])
            ->disableOriginalConstructor()
            ->getMock());

        self::$DI['app']['manipulator.user'] = $this->getMockBuilder('Alchemy\Phrasea\Model\Manipulator\User')
            ->setMethods(['setUserSetting'])
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['manipulator.user']->expects($this->once())
            ->method('setUserSetting')
            ->with($this->isInstanceOf('Alchemy\Phrasea\Model\Entities\User'), $this->equalTo('prop_test'), $this->equalTo('val_test'))
            ->will($this->returnValue(null));

        $this->XMLHTTPRequest('POST', '/user/preferences/', ['prop'  => 'prop_test', 'value' => 'val_test']);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertArrayHasKey('message', $datas);
        unset($response, $datas);
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
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertEquals('val_test', self::$DI['app']['session']->get('phraseanet.prop_test'));
        unset($response, $datas);
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
