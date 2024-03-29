<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class PrinterTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        $records = [
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key()
        ];

        $lst = implode(';', $records);

        self::$DI['client']->request('POST', '/prod/printer/', ['lst' => $lst]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testRoutePrintPdf()
    {
        $randomValue = $this->setSessionFormToken('prodPrint');

        $records = [
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key(),
            self::$DI['record_3']->get_serialize_key(),
            self::$DI['record_4']->get_serialize_key(),
        ];

        $lst = implode(';', $records);

        $layouts = [
            \Alchemy\Phrasea\Out\Module\PDF::LAYOUT_PREVIEW,
            \Alchemy\Phrasea\Out\Module\PDF::LAYOUT_PREVIEWCAPTION,
            \Alchemy\Phrasea\Out\Module\PDF::LAYOUT_PREVIEWCAPTIONTDM,
            \Alchemy\Phrasea\Out\Module\PDF::LAYOUT_THUMBNAILLIST,
            \Alchemy\Phrasea\Out\Module\PDF::LAYOUT_THUMBNAILGRID
        ];

        foreach ($layouts as $layout) {
            self::$DI['client']->request('POST', '/prod/printer/print.pdf', [
                'lst' => $lst,
                'lay' => $layout,
                'prodPrint_token' => $randomValue
                ]
            );

            $response = self::$DI['client']->getResponse();

            $this->assertTrue($response->isOk());
            $this->assertEquals("application/pdf", $response->headers->get("content-type"));
            $this->assertEquals(0, $response->getMaxAge());
            $this->assertTrue($response->headers->has('pragma'));
            $this->assertEquals('public', $response->headers->get('pragma'));
        }
    }
}
