<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Symfony\Component\HttpFoundation\File\UploadedFile;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class ToolsTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;
    protected $tmpFile;

    public function setUp(): void
    {
        parent::setUp();
        $this->tmpFile = sys_get_temp_dir() . '/' . time() . mt_rand(1000, 9999) . '.jpg';
        copy(__DIR__ . '/../../../../../files/cestlafete.jpg', $this->tmpFile);
    }

    public function tearDown(): void
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
               $this->tmpFile, 'KIKOO.JPG', 'image/jpg'
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
               $this->tmpFile, 'KIKOO.JPG', 'image/jpg'
            )
        ]);

        $response = self::$DI['client']->getResponse();
        $message = trim($crawler->filterXPath('//div')->text());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(sprintf(self::$DI['app']['translator']->trans('Subdef "%s" has been successfully substitued'), 'thumbnail'), $message);
    }

    public static function provideRecordModificationRoutes()
    {
        return [
            'hd substitution'        => ['/prod/tools/hddoc/', 'sbas_id', 'prodToolsHDSubstitution', 'newHD'],
            'thumbnail substitution' => ['/prod/tools/chgthumb/', 'sbas_id', 'prodToolsThumbSubstitution', 'newThumb'],
            'thumbnail extraction'   => ['/prod/tools/thumb-extractor/apply/', 'sbas_id', null, null],
            'metadata save'          => ['/prod/tools/metadata/save/', 'databox_id', null, null],
            'auto subtitle'          => ['/prod/tools/auto-subtitle/', 'databox_id', null, null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRecordModificationRoutes')]
    public function testRecordModificationRequiresModifyRight($route, $databoxParam, $formName, $fileParam)
    {
        $record = self::$DI['record_1'];

        $acl = $this->stubACL();
        $acl->method('has_right')->willReturn(true);
        $acl->method('has_access_to_module')->willReturn(true);
        $acl->method('has_access_to_record')->willReturn(true);
        $acl->method('has_right_on_base')->willReturnCallback(function ($baseId, $right) {
            return $right !== \ACL::CANMODIFRECORD;
        });

        $parameters = [
            $databoxParam => $record->getDataboxId(),
            'record_id'   => $record->getRecordId(),
        ];
        if (null !== $formName) {
            $parameters[$formName . '_token'] = $this->setSessionFormToken($formName);
        }

        $files = [];
        if (null !== $fileParam) {
            $files[$fileParam] = new UploadedFile($this->tmpFile, 'KIKOO.JPG', 'image/jpg');
        }

        self::$DI['client']->request('POST', $route, $parameters, $files);

        $this->assertEquals(403, self::$DI['client']->getResponse()->getStatusCode());
    }
}
