<?php

namespace Alchemy\Tests\Phrasea\Controller\Report;

class InformationsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    private $dmin;
    private $dmax;

    public function __construct()
    {
        $this->dmax = new \DateTime('now');
        $this->dmin = new \DateTime('-1 month');
    }

    public function testDoReportUserBadRequest()
    {
        self::$DI['client']->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isOk());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testDoReportUser()
    {
        self::$DI['client']->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'user'          => self::$DI['user']->get_id(),
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromConnexion()
    {
        self::$DI['client']->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'user'          => self::$DI['user']->get_id(),
            'from'          => 'CNX',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromQuestion()
    {
        self::$DI['client']->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'ASK',
            'user'          => self::$DI['user']->get_id(),
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromDownload()
    {
        self::$DI['client']->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'GEN',
            'user'          => self::$DI['user']->get_id(),
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromConnexionCSV()
    {
        self::$DI['client']->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'CNX',
            'printcsv'      => 'on',
            'user'          => self::$DI['user']->get_id(),
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromQuestionCSV()
    {
        self::$DI['client']->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'ASK',
            'printcsv'      => 'on',
            'user'          => self::$DI['user']->get_id(),
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromDownloadCSV()
    {
        self::$DI['client']->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'GEN',
            'printcsv'      => 'on',
            'user'          => self::$DI['user']->get_id(),
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromDownloadOnCustomField()
    {
        self::$DI['client']->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'GEN',
            'on'            => 'usr_mail',
            'user'          => self::$DI['user']->get_email()
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromConnexionOnCustomField()
    {
        self::$DI['client']->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'CNX',
            'on'            => 'usr_mail',
            'user'          => self::$DI['user']->get_email()
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromQuestionOnCustomField()
    {
        self::$DI['client']->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'ASK',
            'on'            => 'usr_mail',
            'user'          => self::$DI['user']->get_email()
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInformationsBrowserBadRequest()
    {
        self::$DI['client']->request('POST', '/report/informations/browser', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isOk());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testDoReportInfomationsBrowser()
    {
        self::$DI['client']->request('POST', '/report/informations/browser', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'user'          => 'chrome',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInfomationsDocumentsNotFound()
    {
        self::$DI['client']->request('POST', '/report/informations/document', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'sbasid'        => 0,
            'rid'           => 0,
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isOk());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDoReportInfomationsDocuments()
    {
        self::$DI['client']->request('POST', '/report/informations/document', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'sbasid'        => self::$DI['record_1']->get_sbas_id(),
            'rid'           => self::$DI['record_1']->get_record_id(),
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInfomationsDocumentsFromTool()
    {
        self::$DI['client']->request('POST', '/report/informations/document', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'sbasid'        => self::$DI['record_1']->get_sbas_id(),
            'rid'           => self::$DI['record_1']->get_record_id(),
            'from'          => 'TOOL'
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInfomationsDocumentsFromDashboard()
    {
        self::$DI['client']->request('POST', '/report/informations/document', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'sbasid'        => self::$DI['record_1']->get_sbas_id(),
            'rid'           => self::$DI['record_1']->get_record_id(),
            'from'          => 'DASH'
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInfomationsDocumentsFromOther()
    {
        self::$DI['client']->request('POST', '/report/informations/document', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'sbasid'        => self::$DI['record_1']->get_sbas_id(),
            'rid'           => self::$DI['record_1']->get_record_id(),
            'user'          => self::$DI['user']->get_id()
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }
}
