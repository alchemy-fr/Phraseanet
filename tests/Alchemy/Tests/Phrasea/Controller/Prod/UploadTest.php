<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\Manager;
use DataURI;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class UploadTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    /**
     *
     * @return Client A Client instance
     */
    protected $client;
    protected $tmpFile;
    private static $need_records = false;

    public function setUp()
    {
        parent::setUp();
        $this->tmpFile = sys_get_temp_dir() . '/' . time() . mt_rand(1000, 9999) . '.jpg';
        copy(__DIR__ . '/../../../../../files/cestlafete.jpg', $this->tmpFile);
    }

    public function tearDown()
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        parent::tearDown();
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$DI['app'] = new Application('test');

        self::giveRightsToUser(self::$DI['app'], self::$DI['user']);
        self::$DI['app']['acl']->get(self::$DI['user'])->revoke_access_from_bases([self::$DI['collection_no_access']->get_base_id()]);
        self::$DI['app']['acl']->get(self::$DI['user'])->set_masks_on_base(self::$DI['collection_no_access_by_status']->get_base_id(), '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::getFlashUploadForm
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::call
     */
    public function testFlashUploadForm()
    {
        self::$DI['client']->request('GET', '/prod/upload/flash-version/');
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::getUploadForm
     */
    public function testUploadForm()
    {
        self::$DI['client']->request('GET', '/prod/upload/');
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::getJsonResponse
     */
    public function testUpload()
    {
        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        $data = DataURI\Data::buildFromFile(__DIR__ . '/../../../../../files/cestlafete.jpg');
        $params = [
            'base_id' => self::$DI['collection']->get_base_id(),
            'b64_image' => DataURI\Dumper::dump($data)
        ];

        $files = [
            'files' => [
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            ]
        ];
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, ['HTTP_Accept' => 'application/json']);

        $response = self::$DI['client']->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertTrue($datas['success']);

        if ($datas['element'] == 'record') {
            $id = explode('_', $datas['id']);

            $record = new \record_adapter(self::$DI['app'], $id[0], $id[1]);
            $this->assertTrue($record->get_thumbnail()->is_physically_present());
        }
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::getJsonResponse
     */
    public function testUploadWithoutB64Image()
    {
        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        $params = [
            'base_id' => self::$DI['collection']->get_base_id()
        ];

        $files = [
            'files' => [
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            ]
        ];
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, ['HTTP_Accept' => 'application/json']);

        $response = self::$DI['client']->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertTrue($datas['success']);

        if ($datas['element'] == 'record') {
            $id = explode('_', $datas['id']);

            $record = new \record_adapter(self::$DI['app'], $id[0], $id[1]);

           $this->assertFalse($record->get_thumbnail()->is_physically_present());
        }
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::getJsonResponse
     */
    public function testUploadingTwiceTheSameRecordShouldSendToQuantantine()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailInfoRecordQuarantined');

        $params = [
            'base_id' => self::$DI['collection']->get_base_id()
        ];

        $files = [
            'files' => [
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            ]
        ];
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, ['HTTP_Accept' => 'application/json']);

        $response = self::$DI['client']->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertTrue($datas['success']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadWrongBaseId()
    {
        $params = ['base_id' => 0];
        $files = [
            'files' => [
                new UploadedFile(
                   $this->tmpFile, 'KIKOO.JPG'
                )
            ]
        ];
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, ['HTTP_Accept' => 'application/json']);

        $response = self::$DI['client']->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertFalse($datas['success']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadNoAccessBaseId()
    {
        $params = ['base_id' => self::$DI['collection_no_access']->get_base_id()];
        $files = [
            'files' => [
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            ]
        ];
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, ['HTTP_Accept' => 'application/json']);

        $response = self::$DI['client']->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertFalse($datas['success']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadInvalidFile()
    {
        $file = new UploadedFile(
                $this->tmpFile, 'KIKOO.JPG', 'image/jpeg', 123, UPLOAD_ERR_NO_FILE
        );

        $params = ['base_id' => self::$DI['collection']->get_base_id()];
        $files = ['files' => [$file]];
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, ['HTTP_Accept' => 'application/json']);

        $response = self::$DI['client']->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertFalse($datas['success']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadNoBaseId()
    {
        $params = [];
        $files = [
            'files' => [
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            ]
        ];
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, ['HTTP_Accept' => 'application/json']);

        $response = self::$DI['client']->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertFalse($datas['success']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUpload2Files()
    {
        $params = ['base_id' => self::$DI['collection']->get_base_id()];
        $files = [
            'files' => [
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                ),
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            ]
        ];
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, ['HTTP_Accept' => 'application/json']);

        $response = self::$DI['client']->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertFalse($datas['success']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadForceRecord()
    {
        $params = [
            'base_id'     => self::$DI['collection']->get_base_id(),
            'forceAction' => Manager::FORCE_RECORD,
        ];

        $files = [
            'files' => [
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            ]
        ];
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, ['HTTP_Accept' => 'application/json']);

        $response = self::$DI['client']->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertTrue($datas['success']);
        $this->assertEquals('record', $datas['element']);
        $this->assertEquals(Manager::RECORD_CREATED, $datas['code']);

        $id = explode('_', $datas['id']);
        $record = new \record_adapter(self::$DI['app'], $id[0], $id[1]);
        $this->assertFalse($record->is_grouping());
        $this->assertEquals([], $datas['reasons']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadRecordStatus()
    {
        $params = [
            'base_id'     => self::$DI['collection']->get_base_id(),
            'forceAction' => Manager::FORCE_RECORD,
            'status'      => [ self::$DI['collection']->get_base_id() => [ 4 => 1]],
        ];

        $files = [
            'files' => [
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            ]
        ];
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, ['HTTP_Accept' => 'application/json']);

        $response = self::$DI['client']->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertTrue($datas['success']);
        $this->assertEquals('record', $datas['element']);
        $this->assertEquals(Manager::RECORD_CREATED, $datas['code']);

        $id = explode('_', $datas['id']);
        $record = new \record_adapter(self::$DI['app'], $id[0], $id[1]);
        $this->assertFalse($record->is_grouping());
        $this->assertEquals(1, substr(strrev($record->get_status()), 4, 1));
        $this->assertEquals([], $datas['reasons']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadNoFiles()
    {
        $params = ['base_id' => self::$DI['collection']->get_base_id()];
        $files = [];
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, ['HTTP_Accept' => 'application/json']);

        $response = self::$DI['client']->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertFalse($datas['success']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadWithoutAnything()
    {
        self::$DI['client']->request('POST', '/prod/upload/', [], [], ['HTTP_Accept' => 'application/json']);

        $response = self::$DI['client']->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertFalse($datas['success']);
    }

    public function checkJsonResponse(Response $response)
    {
        $this->assertEquals(200, $response->getStatusCode());

        $datas = json_decode($response->getContent(), true);

        $this->assertTrue(is_array($datas));
        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);
    }
}
