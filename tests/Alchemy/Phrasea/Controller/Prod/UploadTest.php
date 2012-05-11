<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAbstract.class.inc';
require_once __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Controller/Prod/Upload.php';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UploadTest extends \PhraseanetWebTestCaseAbstract
{
    /**
     *
     * @return Client A Client instance
     */
    protected $client;
    protected static $need_records = false;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    public function createApplication()
    {
        return require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Prod.php';
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::getUploadForm
     */
    public function testUploadForm()
    {
        $response = null;

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Upload::upload
     */
    public function testUpload()
    {
        $response = null;

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }
}
