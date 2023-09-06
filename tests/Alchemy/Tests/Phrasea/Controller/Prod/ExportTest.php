<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;
use Alchemy\Phrasea\Model\Entities\User;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 * @todo Test Alchemy\Phrasea\Controller\Prod\Export::exportMail
 */
class ExportTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;
    private static $GV_activeFTP;

    public function tearDown()
    {
        if (self::$GV_activeFTP) {
            self::$DI['app']['conf']->set(['registry', 'ftp', 'ftp-enabled'], true);
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

        $response = $this->XMLHTTPRequest('POST', '/prod/export/ftp/test/', ['lst' => self::$DI['record_1']->get_serialize_key()]);
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
        $randomValue = $this->setSessionFormToken('prodExportFTP');

        self::$DI['client']->request('POST', '/prod/export/ftp/',  [
            'address' => 'test.ftp',
            'login'      => 'login',
            'dest_folder' => 'documents',
            'prefix_folder' => 'documents',
            'obj'        => ['preview'],
            'prodExportFTP_token' => $randomValue
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
        $randomValue = $this->setSessionFormToken('prodExportFTP');

        return [
            [['prodExportFTP_token' => $randomValue]],
            [['address' => '', 'prodExportFTP_token' => $randomValue]],
            [['address'  => '', 'login' => '', 'prodExportFTP_token' => $randomValue]],
            [['address'       => '', 'login'      => '', 'dest_folder' => '', 'prodExportFTP_token' => $randomValue]],
            [['address'       => '', 'login'      => '', 'dest_folder' => '', 'prefix_folder' => '', 'prodExportFTP_token' => $randomValue]],
        ];
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::exportFtp
     */
    public function testExportFtp()
    {
        $app = $this->getApplication();
        $randomValue = $this->setSessionFormToken('prodExportFTP');

        $bkp = $app['conf']->get('registry');

        if (!$app['conf']->get(['registry', 'ftp', 'ftp-enabled'])) {
            $app['conf']->set(['registry', 'ftp', 'ftp-enabled'], true);
            self::$GV_activeFTP = true;
        }

        /** @var User $user */
        $user = self::$DI['user'];

        //inserted rows from this function are deleted in tearDownAfterClass
        $this->getClient()->request('POST', '/prod/export/ftp/', [
            'lst'        => $this->getRecord1()->getId(),
            'user_dest'  => $user->getId(),
            'address'    => 'local.phrasea.test',
            'login'      => $user->getEmail(),
            'dest_folder' => '/home/test/',
            'prefix_folder' => 'test2/',
            'obj'        => ['preview'],
            'prodExportFTP_token' => $randomValue
        ]);

        $response = $this->getClient()->getResponse();
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);
        $this->assertTrue($datas['success']);
        unset($response, $datas);

        $app['conf']->set('registry', $bkp);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::exportMail
     */
    public function testExportMail()
    {
        //  deliver method removed in the listener
//        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailRecordsExport');

        $randomValue = $this->setSessionFormToken('prodExportEmail');

        $this->getClient()->request('POST', '/prod/export/mail/', [
            'lst'        => $this->getRecord1()->getId(),
            'destmail'   => 'user@example.com',
            'obj'        => ['preview'],
            'prodExportEmail_token' => $randomValue
        ]);

        $response = $this->getClient()->getResponse();
        $this->assertTrue($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Export::connect
     * @covers Alchemy\Phrasea\Controller\Prod\Export::call
     */
    public function testRequireAuthentication()
    {
        $this->logout($this->getApplication());
        $this->getClient()->request('POST', '/prod/export/multi-export/');
        $this->assertTrue($this->getClient()->getResponse()->isRedirect());
    }
}
