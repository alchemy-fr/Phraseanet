<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class DownloadTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Download::exportFtp
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
