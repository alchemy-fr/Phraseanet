<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ControllerToolsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;
    protected $tmpFile;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
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

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Prod.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    public function testRouteChangeDoc()
    {
        $record = static::$records['record_1'];

        $crawler = $this->client->request('POST', '/tools/hddoc/', array(
            'sbas_id' => $record->get_sbas_id(),
            'record_id' => $record->get_record_id(),
        ), array(
            'newHD' => new UploadedFile(
               $this->tmpFile, 'KIKOO.JPG', 'image/jpg', 2000
            )
        ));

        $response = $this->client->getResponse();
        $message = trim($crawler->filterXPath('//div')->text());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(_('Document has been successfully substitued'), $message);
    }

    public function testRouteChangeThumb()
    {
        $record = static::$records['record_1'];

        $crawler = $this->client->request('POST', '/tools/chgthumb/', array(
            'sbas_id' => $record->get_sbas_id(),
            'record_id' => $record->get_record_id(),
        ), array(
            'newThumb' => new UploadedFile(
               $this->tmpFile, 'KIKOO.JPG', 'image/jpg', 2000
            )
        ));

        $response = $this->client->getResponse();
        $message = trim($crawler->filterXPath('//div')->text());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(_('Thumbnail has been successfully substitued'), $message);
    }
}
