<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class DownloadTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Download::download
     */
    public function testDownloadRecords()
    {
        $eventManagerStub = $this->getMockBuilder('\eventsmanager_broker')
                     ->disableOriginalConstructor()
                     ->getMock();

        $eventManagerStub->expects($this->once())
             ->method('trigger')
             ->with($this->equalTo('__DOWNLOAD__'), $this->isType('array'))
             ->will($this->returnValue(null));

        self::$DI['app']['events-manager'] = $eventManagerStub;

        self::$DI['client']->request('POST', '/prod/download/', array(
            'lst'               => self::$DI['record_1']->get_serialize_key(),
            'ssttid'            => '',
            'obj'               => array('preview', 'document'),
            'title'             => 'export_title_test',
            'businessfields'    => '1'
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertRegExp('#download/[a-zA-Z0-9]*/$#', $response->headers->get('location'));
        unset($response, $eventManagerStub);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Download::download
     */
    public function testDownloadRestricted()
    {
        $eventManagerStub = $this->getMockBuilder('\eventsmanager_broker')
                     ->disableOriginalConstructor()
                     ->getMock();

        $eventManagerStub->expects($this->once())
             ->method('trigger')
             ->with($this->equalTo('__DOWNLOAD__'), $this->isType('array'))
             ->will($this->returnValue(null));

        self::$DI['app']['events-manager'] = $eventManagerStub;

        self::$DI['app']['phraseanet.user'] = $this->getMockBuilder('\User_Adapter')
            ->setMethods(array('ACL'))
            ->disableOriginalConstructor()
            ->getMock();

        $stubbedACL = $this->getMockBuilder('\ACL')
            ->disableOriginalConstructor()
            ->getMock();

        //has_right_on_base return true
        $stubbedACL->expects($this->any())
            ->method('has_right_on_bas')
            ->will($this->returnValue(false));

        //has_access_to_subdef return true
        $stubbedACL->expects($this->any())
            ->method('is_restricted_download')
            ->will($this->returnValue(true));

        //has_access_to_subdef return true
        $stubbedACL->expects($this->any())
            ->method('remaining_download')
            ->will($this->returnValue(0));

        self::$DI['app']['phraseanet.user']->expects($this->any())
            ->method('ACL')
            ->will($this->returnValue($stubbedACL));

        self::$DI['client']->request('POST', '/prod/download/', array(
            'lst'               => self::$DI['record_1']->get_serialize_key(),
            'ssttid'            => '',
            'obj'               => array('preview', 'document'),
            'title'             => 'export_title_test',
            'businessfields'    => '1'
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertRegExp('#download/[a-zA-Z0-9]*/$#', $response->headers->get('location'));
        unset($response, $eventManagerStub);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Download::download
     */
    public function testDownloadBasket()
    {
        $basket = $this->insertOneBasketEnv();

        $eventManagerStub = $this->getMockBuilder('\eventsmanager_broker')
                     ->disableOriginalConstructor()
                     ->getMock();

        $eventManagerStub->expects($this->once())
             ->method('trigger')
             ->with($this->equalTo('__DOWNLOAD__'), $this->isType('array'))
             ->will($this->returnValue(null));

        self::$DI['app']['events-manager'] = $eventManagerStub;

        self::$DI['client']->request('POST', '/prod/download/', array(
            'lst'               => '',
            'ssttid'            => $basket->getId(),
            'obj'               => array('preview', 'document'),
            'title'             => 'export_title_test',
            'businessfields'    => '1'
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertRegExp('#download/[a-zA-Z0-9]*/$#', $response->headers->get('location'));
        unset($response, $eventManagerStub);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Download::download
     */
    public function testDownloadBasketValidation()
    {
        $basket = $this->insertOneValidationBasket();

        $eventManagerStub = $this->getMockBuilder('\eventsmanager_broker')
                     ->disableOriginalConstructor()
                     ->getMock();

        $eventManagerStub->expects($this->once())
             ->method('trigger')
             ->with($this->equalTo('__DOWNLOAD__'), $this->isType('array'))
             ->will($this->returnValue(null));

        self::$DI['app']['events-manager'] = $eventManagerStub;

        self::$DI['client']->request('POST', '/prod/download/', array(
            'lst'               => '',
            'ssttid'            => $basket->getId(),
            'obj'               => array('preview', 'document'),
            'title'             => 'export_title_test',
            'businessfields'    => '1'
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertRegExp('#download/[a-zA-Z0-9]*/$#', $response->headers->get('location'));
        unset($response, $eventManagerStub);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Download::connect
     * @covers Alchemy\Phrasea\Controller\Prod\Download::call
     */
    public function testRequireAuthentication()
    {
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/prod/download/');
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }
}
