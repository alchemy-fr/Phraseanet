<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ToolsTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;
    protected $tmpFile;

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

    public function testRouteChangeDoc()
    {
        $record = self::$DI['record_1'];

        $crawler = self::$DI['client']->request('POST', '/prod/tools/hddoc/', [
            'sbas_id' => $record->get_sbas_id(),
            'record_id' => $record->get_record_id(),
        ], [
            'newHD' => new UploadedFile(
               $this->tmpFile, 'KIKOO.JPG', 'image/jpg', 2000
            )
        ]);

        $response = self::$DI['client']->getResponse();
        $message = trim($crawler->filterXPath('//div')->text());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::$DI['app']['translator']->trans('Document has been successfully substitued'), $message);
    }

    public function testRouteChangeThumb()
    {
        $record = self::$DI['record_1'];

        $crawler = self::$DI['client']->request('POST', '/prod/tools/chgthumb/', [
            'sbas_id' => $record->get_sbas_id(),
            'record_id' => $record->get_record_id(),
        ], [
            'newThumb' => new UploadedFile(
               $this->tmpFile, 'KIKOO.JPG', 'image/jpg', 2000
            )
        ]);

        $response = self::$DI['client']->getResponse();
        $message = trim($crawler->filterXPath('//div')->text());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::$DI['app']['translator']->trans('Thumbnail has been successfully substitued'), $message);
    }
}
