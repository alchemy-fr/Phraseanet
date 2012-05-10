<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerPrinterTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;
    protected static $need_records = 4;

    public function createApplication()
    {
        return require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Prod.php';
    }

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        $records = array(
            self::$record_1->get_serialize_key(),
            self::$record_2->get_serialize_key()
        );

        $lst = implode(';', $records);

        $crawler = $this->client->request('POST', '/printer/', array('lst' => $lst));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testRoutePrintPdf()
    {
        $records = array(
            self::$record_1->get_serialize_key(),
            self::$record_2->get_serialize_key(),
            self::$record_3->get_serialize_key(),
            self::$record_4->get_serialize_key(),
        );

        $lst = implode(';', $records);

        $layouts = array(
            \Alchemy\Phrasea\Out\Module\PDF::LAYOUT_PREVIEW,
            \Alchemy\Phrasea\Out\Module\PDF::LAYOUT_PREVIEWCAPTION,
            \Alchemy\Phrasea\Out\Module\PDF::LAYOUT_PREVIEWCAPTIONTDM,
            \Alchemy\Phrasea\Out\Module\PDF::LAYOUT_THUMBNAILLIST,
            \Alchemy\Phrasea\Out\Module\PDF::LAYOUT_THUMBNAILGRID
        );

        foreach ($layouts as $layout) {
            $crawler = $this->client->request('POST', '/printer/print.pdf', array(
                'lst' => $lst,
                'lay' => $layout
                )
            );

            $response = $this->client->getResponse();

            $this->assertEquals("application/pdf", $response->headers->get("content-type"));

            $this->assertTrue($response->isOk());
        }
    }
}
