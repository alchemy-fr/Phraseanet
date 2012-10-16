<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Controller\Prod\Export;
use Symfony\Component\HttpFoundation\Request;

class ExportTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;
    protected static $GV_activeFTP;

    /**
     * Delete inserted rows from FTP export
     */
    public static function tearDownAfterClass()
    {
        self::$DI['app']['phraseanet.registry']->set('GV_activeFTP', self::$GV_activeFTP, \registry::TYPE_BOOLEAN);
        self::$GV_activeFTP = null;

        $conn = self::$DI['app']['phraseanet.appbox']->get_connection();

        $sql = 'DELETE FROM ftp_export WHERE mail = :email_dest';
        $sql2 = 'DELETE FROM ftp_export_elements WHERE (base_id = :base_id AND record_id = :record_id)';

        $stmtFtp = $conn->prepare($sql);
        $stmtFtp->execute(array(':email_dest' => self::$DI['user']->get_email()));
        $stmtFtp->closeCursor();

        $stmtElements = $conn->prepare($sql2);
        $stmtElements->execute(array(':base_id'   => self::$DI['record_1']->get_base_id(), ':record_id' => self::$DI['record_1']->get_record_id()));
        $stmtElements->closeCursor();

        unset($conn, $stmtFtp, $stmtElements);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::displayMultiExport
     */
    public function testDisplayMultiExport()
    {
        $response = self::$DI['client']->request('GET', '/prod/export/multi-export/', array('lst' => self::$DI['record_1']->get_serialize_key()));
        $this->assertTrue($response->isOk());
        unset($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::testFtpConnexion
     */
    public function testTestFtpConnexion()
    {
        $framework = $this;
        self::$DI['app']['phraseanet.ftp.client'] = self::$DI['app']->protect(function($host, $port = 21, $timeout = 90, $ssl = false, $proxy = false, $proxyport = false) use ($framework) {
                return $framework->getMockBuilder('\ftpclient')
                        ->setMethods(array('login', 'close'))
                        ->disableOriginalConstructor()
                        ->getMock();
            });

        $response = $this->XMLHTTPRequest('POST', '/prod/export/ftp/test/', array('lst' => self::$DI['record_1']->get_serialize_key()));
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertArrayHasKey('message', $datas);
        unset($response, $datas);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Prod\Export::testFtpConnexion
     */
    public function testFtpConnexionNoXMLHTTPRequests()
    {
        $framework = $this;
        self::$DI['app']['phraseanet.ftp.client'] = self::$DI['app']->protect(function($host, $port = 21, $timeout = 90, $ssl = false, $proxy = false, $proxyport = false) use ($framework) {
                $ftpStub = $framework->getMockBuilder('\ftpclient')
                    ->setMethods(array('login', 'close'))
                    ->disableOriginalConstructor()
                    ->getMock();

                $ftpStub->expects($framework->once())
                    ->method('login')
                    ->will($framework->throwException(new \Exception()));

                return $ftpStub;
            });

        $response = self::$DI['client']->request('POST', '/prod/export/ftp/test/', array('lst' => self::$DI['record_1']->get_serialize_key()));
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertFalse($datas['success']);
        $this->assertArrayHasKey('message', $datas);
        unset($response, $datas);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::exportFtp
     */
    public function testExportFtpNoDocs()
    {
        $response = self::$DI['client']->request('POST', '/prod/export/ftp/',  array('addr'       => '', 'login'      => '', 'destfolder' => '', 'NAMMKDFOLD' => '', 'obj'        => array()));
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);
        $this->assertFalse($datas['success']);
        unset($response, $datas);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Prod\Export::exportFtp
     * @dataProvider getMissingArguments
     */
    public function testExportFtpBadRequest($params)
    {
        self::$DI['client']->request('POST', '/prod/export/ftp/', $params);
    }

    public function getMissingArguments()
    {
        return array(
            array(array()),
            array(array('addr' => '')),
            array(array('addr'  => '', 'login' => '')),
            array(array('addr'       => '', 'login'      => '', 'destfolder' => '')),
            array(array('addr'       => '', 'login'      => '', 'destfolder' => '', 'NAMMKDFOLD' => '')),
        );
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::exportFtp
     */
    public function testExportFtp()
    {
        self::$GV_activeFTP = self::$DI['app']['phraseanet.registry']->get('GV_activeFTP');
        self::$DI['app']['phraseanet.registry']->set('GV_activeFTP', '1', \registry::TYPE_BOOLEAN);

        //inserted rows from this function are deleted in tearDownAfterClass
        $response = self::$DI['client']->request('POST', '/prod/export/ftp/', array(
                'lst'        => self::$DI['record_2']->get_serialize_key(),
                'user_dest'  => self::$DI['user']->get_id(),
                'addr'       => 'local.phrasea.test',
                'login'      => self::$DI['user']->get_email(),
                'destfolder' => '/home/test/',
                'NAMMKDFOLD' => 'test2/',
                'obj'        => array('preview')
            ));
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);
        $this->assertTrue($datas['success']);
        unset($response, $datas);
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
