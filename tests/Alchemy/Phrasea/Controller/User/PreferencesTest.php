<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Controller\User\Preferences;
use Symfony\Component\HttpFoundation\Request;

class PreferencesTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\User\Preferences::saveUserPref
     */
    public function testSaveUserPref()
    {
        $preferences = new Preferences();
        $request = Request::create('/user/preferences/', 'POST', array('prop'  => 'prop_test', 'value' => 'val_test'), array(), array(), array('HTTP_X-Requested-With' => 'XMLHttpRequest'));

        self::$DI['app']['phraseanet.user'] = $this->getMockBuilder('\User_Adapter')
            ->setMethods(array('setPrefs'))
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['phraseanet.user']->expects($this->once())
            ->method('setPrefs')
            ->with($this->equalTo('prop_test'), $this->equalTo('val_test'))
            ->will($this->returnValue(true));

        $response = $preferences->saveUserPref(self::$DI['app'], $request);
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertArrayHasKey('message', $datas);
        unset($preferences, $request, $response, $datas);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\User\Preferences::saveUserPref
     */
    public function testSaveUserPrefNoXMLHTTPRequests()
    {
        $preferences = new Preferences();
        $request = Request::create('/user/preferences/', 'POST', array('prop'  => 'prop_test', 'value' => 'val_test'));
        $preferences->saveUserPref(self::$DI['app'], $request);
        unset($preferences, $request);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\User\Preferences::saveTemporaryPref
     */
    public function testSaveTempPrefNoXMLHTTPRequests()
    {
        $preferences = new Preferences();
        $request = Request::create('/user/preferences/temporary/', 'POST', array('prop'  => 'prop_test', 'value' => 'val_test'));
        $preferences->saveUserPref(self::$DI['app'], $request);
        unset($preferences, $request);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\User\Preferences::saveTemporaryPref
     */
    public function testSaveTemporaryPref()
    {
        $preferences = new Preferences();
        $request = Request::create('/user/preferences/temporary/', 'POST', array('prop'  => 'prop_test', 'value' => 'val_test'), array(), array(), array('HTTP_X-Requested-With' => 'XMLHttpRequest'));
        $response = $preferences->saveTemporaryPref(self::$DI['app'], $request);
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertEquals('val_test', self::$DI['app']['session']->get('pref.prop_test'));
        unset($preferences, $request, $response, $datas);
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
