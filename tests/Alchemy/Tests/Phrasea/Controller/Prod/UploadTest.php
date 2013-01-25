<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\Manager;
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
    protected static $need_records = false;

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
        self::$DI['user']->ACL()->revoke_access_from_bases(array(self::$DI['collection_no_access']->get_base_id()));
        self::$DI['user']->ACL()->set_masks_on_base(self::$DI['collection_no_access_by_status']->get_base_id(), '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000');
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
        $params = array('base_id' => self::$DI['collection']->get_base_id());
        $files = array(
            'files' => array(
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            )
        );
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

        $response = self::$DI['client']->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertTrue($datas['success']);

        if ($datas['element'] == 'record') {
            $id = explode('_', $datas['id']);

            $record = new \record_adapter($id[0], $id[1]);
        }
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadWrongBaseId()
    {
        $params = array('base_id' => 0);
        $files = array(
            'files' => array(
                new UploadedFile(
                   $this->tmpFile, 'KIKOO.JPG'
                )
            )
        );
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

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
        $params = array('base_id' => self::$DI['collection_no_access']->get_base_id());
        $files = array(
            'files' => array(
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            )
        );
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

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

        $params = array('base_id' => self::$DI['collection']->get_base_id());
        $files = array('files' => array($file));
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

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
        $params = array();
        $files = array(
            'files' => array(
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            )
        );
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

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
        $params = array('base_id' => self::$DI['collection']->get_base_id());
        $files = array(
            'files' => array(
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                ),
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            )
        );
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

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
        $params = array(
            'base_id'     => self::$DI['collection']->get_base_id(),
            'forceAction' => Manager::FORCE_RECORD,
        );

        $files = array(
            'files' => array(
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            )
        );
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

        $response = self::$DI['client']->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertTrue($datas['success']);
        $this->assertEquals('record', $datas['element']);
        $this->assertEquals(Manager::RECORD_CREATED, $datas['code']);

        $id = explode('_', $datas['id']);
        $record = new \record_adapter(self::$DI['app'], $id[0], $id[1]);
        $this->assertFalse($record->is_grouping());
        $this->assertEquals(array(), $datas['reasons']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadRecordStatus()
    {
        $params = array(
            'base_id'     => self::$DI['collection']->get_base_id(),
            'forceAction' => Manager::FORCE_RECORD,
            'status'      => array( self::$DI['collection']->get_base_id() => array( 4 => 1)),
        );

        $files = array(
            'files' => array(
                new UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            )
        );
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

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
        $this->assertEquals(array(), $datas['reasons']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadNoFiles()
    {
        $params = array('base_id' => self::$DI['collection']->get_base_id());
        $files = array();
        self::$DI['client']->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

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
        self::$DI['client']->request('POST', '/prod/upload/', array(), array(), array('HTTP_Accept' => 'application/json'));

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
