<?php

namespace Alchemy\Tests\Phrasea\Controller\User;

class PreferencesTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\User\Preferences::saveUserPref
     */
    public function testSaveUserPref()
    {
        self::$DI['app']['phraseanet.user'] = $this->getMockBuilder('\User_Adapter')
            ->setMethods(array('setPrefs'))
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['phraseanet.user']->expects($this->once())
            ->method('setPrefs')
            ->with($this->equalTo('prop_test'), $this->equalTo('val_test'))
            ->will($this->returnValue(true));

        $this->XMLHTTPRequest('POST', '/user/preferences/', array('prop'  => 'prop_test', 'value' => 'val_test'));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertArrayHasKey('message', $datas);
        unset($response, $datas);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\User\Preferences::saveUserPref
     */
    public function testSaveUserPrefNoXMLHTTPRequests()
    {
        self::$DI['client']->request('POST', '/user/preferences/',  array('prop'  => 'prop_test', 'value' => 'val_test'));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\User\Preferences::saveTemporaryPref
     */
    public function testSaveTempPrefNoXMLHTTPRequests()
    {
        self::$DI['client']->request('POST', '/user/preferences/temporary/',  array('prop'  => 'prop_test', 'value' => 'val_test'));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\User\Preferences::saveTemporaryPref
     */
    public function testSaveTemporaryPref()
    {
        $this->XMLHTTPRequest('POST', "/user/preferences/temporary/", array('prop'  => 'prop_test', 'value' => 'val_test'));
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
