<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

/**
 * @todo Test Alchemy\Phrasea\Controller\Prod\Export::exportMail
 */
class RecordsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Records::whatCanIDelete
     */
    public function testWhatCanIDelete()
    {
        self::$DI['client']->request('POST', '/prod/records/delete/what/', array('lst' => self::$DI['record_1']->get_serialize_key()));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Records::doDeleteRecords
     */
    public function testDoDeleteRecords()
    {
        $file = new Alchemy\Phrasea\Border\File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../testfiles/cestlafete.jpg'), self::$DI['collection']);
        $record = \record_adapter::createFromFile($file, self::$DI['app']);
        $this->XMLHTTPRequest('POST', '/prod/records/delete/', array('lst' => $record->get_serialize_key()));
        $response = self::$DI['client']->getResponse();
        $datas = (array) json_decode($response->getContent());
        $this->assertContains($record->get_serialize_key(), $datas);
        try {
           new \record_adapter(self::$DI['app'], $record->get_sbas_id(), $record->get_record_id());
            $this->fail('Record not deleted');
        } catch(\Exception $e) {

        }
        unset($response, $datas, $record);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Records::renewUrl
     */
    public function testRenewUrl()
    {
        $file = new Alchemy\Phrasea\Border\File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../testfiles/cestlafete.jpg'), self::$DI['collection']);
        $record = \record_adapter::createFromFile($file, self::$DI['app']);
        $this->XMLHTTPRequest('POST', '/prod/records/renew-url/', array('lst' => $record->get_serialize_key()));
        $response = self::$DI['client']->getResponse();
        $datas = (array) json_decode($response->getContent());
        $this->assertTrue(count($datas) > 0);
        $record->delete();
        unset($response, $datas, $record);
    }

}
