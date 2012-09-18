<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Silex\WebTestCase;
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
        copy(__DIR__ . '/../../../../testfiles/cestlafete.jpg', $this->tmpFile);
    }

    public function tearDown()
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::getFlashUploadForm
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::call
     */
    public function testFlashUploadForm()
    {
        $this->client->request('GET', '/prod/upload/flash-version/');
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::getUploadForm
     */
    public function testUploadForm()
    {
        $this->client->request('GET', '/prod/upload/');
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::getJsonResponse
     */
    public function testUpload()
    {
        $params = array('base_id' => self::$collection->get_base_id());
        $files = array(
            'files' => array(
                new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            )
        );
        $this->client->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

        $response = $this->client->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertTrue($datas['success']);

        if ($datas['element'] == 'record') {
            $id = explode('_', $datas['id']);

            $record = new record_adapter($id[0], $id[1]);
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
                new \Symfony\Component\HttpFoundation\File\UploadedFile(
                   $this->tmpFile, 'KIKOO.JPG'
                )
            )
        );
        $this->client->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

        $response = $this->client->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertFalse($datas['success']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadNoAccessBaseId()
    {
        $params = array('base_id' => self::$collection_no_access->get_base_id());
        $files = array(
            'files' => array(
                new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            )
        );
        $this->client->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

        $response = $this->client->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertFalse($datas['success']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadInvalidFile()
    {
        $file = new \Symfony\Component\HttpFoundation\File\UploadedFile(
                $this->tmpFile, 'KIKOO.JPG', 'image/jpeg', 123, UPLOAD_ERR_NO_FILE
        );

        $params = array('base_id' => self::$collection->get_base_id());
        $files = array('files' => array($file));
        $this->client->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

        $response = $this->client->getResponse();

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
                new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            )
        );
        $this->client->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

        $response = $this->client->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertFalse($datas['success']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUpload2Files()
    {
        $params = array('base_id' => self::$collection->get_base_id());
        $files = array(
            'files' => array(
                new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                ),
                new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            )
        );
        $this->client->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

        $response = $this->client->getResponse();

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
            'base_id'     => self::$collection->get_base_id(),
            'forceAction' => \Alchemy\Phrasea\Border\Manager::FORCE_RECORD,
        );

        $files = array(
            'files' => array(
                new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            )
        );
        $this->client->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

        $response = $this->client->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertTrue($datas['success']);
        $this->assertEquals('record', $datas['element']);
        $this->assertEquals(\Alchemy\Phrasea\Border\Manager::RECORD_CREATED, $datas['code']);

        $id = explode('_', $datas['id']);
        $record = new \record_adapter($id[0], $id[1]);
        $this->assertFalse($record->is_grouping());
        $this->assertEquals(array(), $datas['reasons']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadRecordStatus()
    {
        $params = array(
            'base_id'     => self::$collection->get_base_id(),
            'forceAction' => \Alchemy\Phrasea\Border\Manager::FORCE_RECORD,
            'status'      => array( self::$collection->get_base_id() => array( 4 => 1)),
        );

        $files = array(
            'files' => array(
                new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    $this->tmpFile, 'KIKOO.JPG'
                )
            )
        );
        $this->client->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

        $response = $this->client->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertTrue($datas['success']);
        $this->assertEquals('record', $datas['element']);
        $this->assertEquals(\Alchemy\Phrasea\Border\Manager::RECORD_CREATED, $datas['code']);

        $id = explode('_', $datas['id']);
        $record = new \record_adapter($id[0], $id[1]);
        $this->assertFalse($record->is_grouping());
        $this->assertEquals(1, substr(strrev($record->get_status()), 4, 1));
        $this->assertEquals(array(), $datas['reasons']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadNoFiles()
    {
        $params = array('base_id' => self::$collection->get_base_id());
        $files = array();
        $this->client->request('POST', '/prod/upload/', $params, $files, array('HTTP_Accept' => 'application/json'));

        $response = $this->client->getResponse();

        $this->checkJsonResponse($response);

        $datas = json_decode($response->getContent(), true);

        $this->assertFalse($datas['success']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUploadWithoutAnything()
    {
        $this->client->request('POST', '/prod/upload/', array(), array(), array('HTTP_Accept' => 'application/json'));

        $response = $this->client->getResponse();

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
