<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Controller\Prod\Export;
use Symfony\Component\HttpFoundation\Request;

class ExportTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::displayMultiExport
     */
    public function testDisplayMultiExport()
    {
        $export = new Export();
        $request = Request::create('/prod/export/multi-export/', 'GET', array('lst'  => self::$DI['record_1']->get_serialize_key()));
        $response = $export->displayMultiExport(self::$DI['app'], $request);
        $this->assertTrue($response->isOk());
        unset($export, $request, $response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::testFtpConnexion
     */
    public function testTestFtpConnexion()
    {
        $framework = $this;
        self::$DI['app']['phraseanet.ftp.client'] = self::$DI['app']->protect(function($host, $port = 21, $timeout = 90, $ssl = false, $proxy = false, $proxyport = false) use ($framework){
            return $framework->getMockBuilder('\ftpclient')
            ->setMethods(array('login', 'close'))
            ->disableOriginalConstructor()
            ->getMock();
        });

        $export = new Export();
        $request = Request::create('/prod/export/ftp/test/', 'POST', array(), array(), array() ,array('HTTP_X-Requested-With' => 'XMLHttpRequest'));
        $response = $export->testFtpConnexion(self::$DI['app'], $request);
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertArrayHasKey('message', $datas);
        unset($export, $request, $response, $datas);
        unset($export, $request);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Prod\Export::testFtpConnexion
     */
    public function testFtpConnexionNoXMLHTTPRequests()
    {
       $framework = $this;
        self::$DI['app']['phraseanet.ftp.client'] = self::$DI['app']->protect(function($host, $port = 21, $timeout = 90, $ssl = false, $proxy = false, $proxyport = false) use ($framework){
            $ftpStub =  $framework->getMockBuilder('\ftpclient')
            ->setMethods(array('login', 'close'))
            ->disableOriginalConstructor()
            ->getMock();

            $ftpStub->expects($framework->once())
            ->method('login')
            ->will($framework->throwException(new \Exception()));

            return $ftpStub;
        });


        $export = new Export();
        $request = Request::create('/prod/export/ftp/test/', 'POST');
        $response = $export->testFtpConnexion(self::$DI['app'], $request);
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertFalse($datas['success']);
        $this->assertArrayHasKey('message', $datas);
        unset($export, $request, $response, $datas);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::exportFtp
     */
    public function testExportFtpNoDocs()
    {
        $export = new Export();
        $request = Request::create('/prod/export/ftp/', 'POST', array('addr' => '', 'login' => '', 'destfolder' => '', 'NAMMKDFOLD' => '', 'obj' => array()));
        $response = $export->exportFtp(self::$DI['app'], $request);
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);
        $this->assertFalse($datas['success']);
        unset($export, $request, $response, $datas);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Prod\Export::exportFtp
     * @dataProvider getMissingArguments
     */
    public function testExportFtpBadRequest($params)
    {
        $export = new Export();
        $request = Request::create('/prod/export/ftp/', 'POST', $params);
        $response = $export->exportFtp(self::$DI['app'], $request);
        unset($export, $request, $response);
    }

    public function getMissingArguments()
    {
        return array(
            array(array()),
            array(array('addr' => '')),
            array(array('addr' => '', 'login' => '')),
            array(array('addr' => '', 'login' => '', 'destfolder' => '')),
            array(array('addr' => '', 'login' => '', 'destfolder' => '', 'NAMMKDFOLD' => '')),
        );
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::connect
     * @covers Alchemy\Phrasea\Controller\Prod\Export::call
     */
    public function testRequireAuthentication()
    {
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/prod/export/multi-export/');
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @todo Test Alchemy\Phrasea\Controller\Prod\Export::exportMail
     */
}
