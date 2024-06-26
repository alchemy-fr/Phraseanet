<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
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
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
        $record = self::$DI['record_1'];

        $randomValue = bin2hex(random_bytes(35));
        self::$DI['app']['session']->set('prodToolsHDSubstitution_token', $randomValue);

        $crawler = self::$DI['client']->request('POST', '/prod/tools/hddoc/', [
            'sbas_id' => $record->get_sbas_id(),
            'record_id' => $record->get_record_id(),
            'prodToolsHDSubstitution_token' => $randomValue
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
        $randomValue = bin2hex(random_bytes(35));
        self::$DI['app']['session']->set('prodToolsThumbSubstitution_token', $randomValue);

        $crawler = self::$DI['client']->request('POST', '/prod/tools/chgthumb/', [
            'sbas_id' => $record->get_sbas_id(),
            'record_id' => $record->get_record_id(),
            'prodToolsThumbSubstitution_token' => $randomValue
        ], [
            'newThumb' => new UploadedFile(
               $this->tmpFile, 'KIKOO.JPG', 'image/jpg', 2000
            )
        ]);

        $response = self::$DI['client']->getResponse();
        $message = trim($crawler->filterXPath('//div')->text());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(sprintf(self::$DI['app']['translator']->trans('Subdef "%s" has been successfully substitued'), 'thumbnail'), $message);
    }
}
