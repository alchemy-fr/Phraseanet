<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

/**
 * @todo Test Alchemy\Phrasea\Controller\Prod\Export::exportMail
 */
class ExportTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;
    protected static $GV_activeFTP;

    public function tearDown()
    {
        if (self::$GV_activeFTP) {
            self::$DI['app']['phraseanet.registry']->set('GV_activeFTP', true, \registry::TYPE_BOOLEAN);
        }

        self::$GV_activeFTP = null;
        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::displayMultiExport
     */
    public function testDisplayMultiExport()
    {
        self::$DI['client']->request('POST', '/prod/export/multi-export/', ['lst' => self::$DI['record_1']->get_serialize_key()]);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::testFtpConnexion
     */
    public function testTestFtpConnexion()
    {
        $framework = $this;
        self::$DI['app']['phraseanet.ftp.client'] = self::$DI['app']->protect(function ($host, $port = 21, $timeout = 90, $ssl = false, $proxy = false, $proxyport = false) use ($framework) {
            return $framework->getMockBuilder('\ftpclient')
                    ->setMethods(['login', 'close'])
                    ->disableOriginalConstructor()
                    ->getMock();
        });

        $this->XMLHTTPRequest('POST', '/prod/export/ftp/test/', ['lst' => self::$DI['record_1']->get_serialize_key()]);
        $response = self::$DI['client']->getResponse();
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertArrayHasKey('message', $datas);
        unset($response, $datas);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::testFtpConnexion
     */
    public function testFtpConnexionNoXMLHTTPRequests()
    {
        $framework = $this;
        self::$DI['app']['phraseanet.ftp.client'] = self::$DI['app']->protect(function ($host, $port = 21, $timeout = 90, $ssl = false, $proxy = false, $proxyport = false) use ($framework) {
            $ftpStub = $framework->getMockBuilder('\ftpclient')
                ->setMethods(['login', 'close'])
                ->disableOriginalConstructor()
                ->getMock();

            $ftpStub->expects($framework->once())
                ->method('login')
                ->will($framework->throwException(new \Exception()));

            return $ftpStub;
        });

        self::$DI['client']->request('POST', '/prod/export/ftp/test/', ['lst' => self::$DI['record_1']->get_serialize_key()]);
        $response = self::$DI['client']->getResponse();
        $datas = (array) json_decode($response->getContent());

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::exportFtp
     */
    public function testExportFtpNoDocs()
    {
        self::$DI['client']->request('POST', '/prod/export/ftp/',  [
            'address' => 'test.ftp',
            'login'      => 'login',
            'dest_folder' => 'documents',
            'prefix_folder' => 'documents',
            'obj'        => ['preview']
        ]);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);
        $this->assertFalse($datas['success']);
        unset($response, $datas);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::exportFtp
     * @dataProvider getMissingArguments
     */
    public function testExportFtpBadRequest($params)
    {
        self::$DI['client']->request('POST', '/prod/export/ftp/', $params);

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    public function getMissingArguments()
    {
        return [
            [[]],
            [['address' => '']],
            [['address'  => '', 'login' => '']],
            [['address'       => '', 'login'      => '', 'dest_folder' => '']],
            [['address'       => '', 'login'      => '', 'dest_folder' => '', 'prefix_folder' => '']],
        ];
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::exportFtp
     */
    public function testExportFtp()
    {
        if (!self::$DI['app']['phraseanet.registry']->get('GV_activeFTP')) {
           self::$DI['app']['phraseanet.registry']->set('GV_activeFTP', true, \registry::TYPE_BOOLEAN);
           self::$GV_activeFTP = true;
        }
        //inserted rows from this function are deleted in tearDownAfterClass
        self::$DI['client']->request('POST', '/prod/export/ftp/', [
            'lst'        => self::$DI['record_1']->get_serialize_key(),
            'user_dest'  => self::$DI['user']->get_id(),
            'address'    => 'local.phrasea.test',
            'login'      => self::$DI['user']->get_email(),
            'dest_folder' => '/home/test/',
            'prefix_folder' => 'test2/',
            'obj'        => ['preview']
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);
        $this->assertTrue($datas['success']);
        unset($response, $datas);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::exportMail
     */
    public function testExportMail()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailRecordsExport');

        self::$DI['client']->request('POST', '/prod/export/mail/', [
            'lst'        => self::$DI['record_1']->get_serialize_key(),
            'destmail'   => 'user@example.com',
            'obj'        => ['preview'],
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
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
}
