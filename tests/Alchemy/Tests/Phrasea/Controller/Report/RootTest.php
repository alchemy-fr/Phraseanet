<?php

namespace Alchemy\Tests\Phrasea\Controller\Report;

class RootTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    private $dmin;
    private $dmax;

    public function __construct()
    {
        $this->dmax = new \DateTime('now');
        $this->dmin = new \DateTime('-1 month');
    }

    public function testRouteDashboard()
    {
        $this->authenticate(self::$DI['app']);

        self::$DI['client']->request('GET', '/report/dashboard');

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testRouteDashboardJson()
    {
        $this->authenticate(self::$DI['app']);

        $this->XMLHTTPRequest('GET', '/report/dashboard', array(
            'dmin' => $this->dmin->format('Y-m-d'),
            'dmax' => $this->dmin->format('Y-m-d'),
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testRouteInitReport()
    {
        self::$DI['client']->request('POST', '/report/init', array('popbases' => array('1_1')));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportConnexions()
    {
        self::$DI['client']->request('POST', '/report/connexions', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'order'         => 'ASC',
            'champ'         => 'user',
            'page'          => 1,
            'limit'         => 10,
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportConnexionsPrintCSV()
    {
        self::$DI['client']->request('POST', '/report/connexions', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportConnexionsFilterColumns()
    {
        self::$DI['client']->request('POST', '/report/connexions', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'list_column'   => 'user ddate',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportConnexionsFilterResultOnOneColumn()
    {
        self::$DI['client']->request('POST', '/report/connexions', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'filter_column' => 'user',
            'filter_value'  => 'admin',
            'liste'         => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportConnexionsFilterConf()
    {
        self::$DI['client']->request('POST', '/report/connexions', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'conf'          => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportConnexionsGroupBy()
    {
        self::$DI['client']->request('POST', '/report/connexions', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'groupby'       => 'user',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportQuestions()
    {
        self::$DI['client']->request('POST', '/report/questions', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'order'         => 'ASC',
            'champ'         => 'user',
            'page'          => 1,
            'limit'         => 10,
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportQuestionsPrintCSV()
    {
        self::$DI['client']->request('POST', '/report/questions', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportQuestionsFilterColumns()
    {
        self::$DI['client']->request('POST', '/report/questions', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'list_column'   => 'user ddate',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportQuestionsFilterResultOnOneColumn()
    {
        self::$DI['client']->request('POST', '/report/questions', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'filter_column' => 'user',
            'filter_value'  => 'admin',
            'liste'         => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportQuestionsFilterConf()
    {
        self::$DI['client']->request('POST', '/report/questions', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'conf'          => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportQuestionsGroupBy()
    {
        self::$DI['client']->request('POST', '/report/questions', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'groupby'       => 'user',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDownloads()
    {
        self::$DI['client']->request('POST', '/report/downloads', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'order'         => 'ASC',
            'champ'         => 'user',
            'page'          => 1,
            'limit'         => 10,
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDownloadsPrintCSV()
    {
        self::$DI['client']->request('POST', '/report/downloads', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDownloadsFilterColumns()
    {
        self::$DI['client']->request('POST', '/report/downloads', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'list_column'   => 'user ddate',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDownloadsFilterResultOnOneColumn()
    {
        self::$DI['client']->request('POST', '/report/downloads', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'filter_column' => 'user',
            'filter_value'  => 'admin',
            'liste'         => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDownloadsFilterConf()
    {
        self::$DI['client']->request('POST', '/report/downloads', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'conf'          => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDownloadsGroupBy()
    {
        self::$DI['client']->request('POST', '/report/downloads', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'groupby'       => 'user',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDocuments()
    {
        self::$DI['client']->request('POST', '/report/documents', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'order'         => 'ASC',
            'champ'         => 'final',
            'tbl'           => 'DOC',
            'page'          => 1,
            'limit'         => 10,
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDocumentsPrintCSV()
    {
        self::$DI['client']->request('POST', '/report/documents', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDocumentsFilterColumns()
    {
        self::$DI['client']->request('POST', '/report/documents', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'list_column'   => 'file mime',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDocumentsFilterResultOnOneColumn()
    {
        self::$DI['client']->request('POST', '/report/documents', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'filter_column' => 'mime',
            'filter_value'  => 'pdf',
            'liste'         => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDocumentsFilterConf()
    {
        self::$DI['client']->request('POST', '/report/documents', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'conf'          => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDocumentsGroupBy()
    {
        self::$DI['client']->request('POST', '/report/documents', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'groupby'       => 'mime',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportClients()
    {
        self::$DI['client']->request('POST', '/report/clients', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportClientPrintCSV()
    {
        self::$DI['client']->request('POST', '/report/clients', array(
            'dmin'          => $this->dmin->format('Y-m-d'),
            'dmax'          => $this->dmax->format('Y-m-d'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }
}
