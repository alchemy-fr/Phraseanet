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
        $auth = new \Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);

        self::$DI['client']->request('GET', '/report/dashboard');

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testRouteDashboardJson()
    {
        $auth = new \Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);

        $this->XMLHTTPRequest('GET', '/report/dashboard', array(
            'dmin' => $this->dmin->format('d-m-Y'),
            'dmax' => $this->dmin->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportClientPrintCSV()
    {
        self::$DI['client']->request('POST', '/report/clients', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportConnexionsByUsers()
    {
        self::$DI['client']->request('POST', '/report/activity/users/connexions', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'page'          => 1,
            'limit'         => 10,
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportConnexionsByUsersCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/users/connexions', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'page'          => 1,
            'limit'         => 10,
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDownloadsByUsers()
    {
        self::$DI['client']->request('POST', '/report/activity/users/downloads', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'page'          => 1,
            'limit'         => 10,
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDownloadsByUsersCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/users/downloads', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'page'          => 1,
            'limit'         => 10,
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportBestOfQuestions()
    {
        self::$DI['client']->request('POST', '/report/activity/questions/best-of', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'limit'         => 10,
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportBestOfQuestionsCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/questions/best-of', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'limit'         => 10,
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportNoBestOfQuestions()
    {
        self::$DI['client']->request('POST', '/report/activity/questions/no-best-of', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'limit'         => 10,
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportNoBestOfQuestionsCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/questions/no-best-of', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'limit'         => 10,
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportSiteActiviyPerHours()
    {
        self::$DI['client']->request('POST', '/report/activity/instance/hours', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportSiteActiviyPerHoursCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/instance/hours', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportSiteActiviyPerDays()
    {
        self::$DI['client']->request('POST', '/report/activity/instance/days', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportSiteActiviyPerDaysCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/instance/days', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportPushedDocuments()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/pushed', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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

    public function testDoReportPushedDocumentsPrintCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/pushed', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportPushedDocumentsFilterColumns()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/pushed', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'list_column'   => 'user ddate',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportPushedDocumentsFilterResultOnOneColumn()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/pushed', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'filter_column' => 'user',
            'filter_value'  => 'admin',
            'liste'         => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportPushedDocumentsFilterConf()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/pushed', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'conf'          => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportPushedDocumentsGroupBy()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/pushed', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'groupby'       => 'user',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportAddedDocuments()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/added', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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

    public function testDoReportAddedDocumentsPrintCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/added', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportAddedDocumentsFilterColumns()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/added', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'list_column'   => 'user ddate',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportAddedDocumentsFilterResultOnOneColumn()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/added', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'filter_column' => 'user',
            'filter_value'  => 'admin',
            'liste'         => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportAddedDocumentsFilterConf()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/added', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'conf'          => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportAddedDocumentsGroupBy()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/added', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'groupby'       => 'user',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportEditedDocuments()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/edited', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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

    public function testDoReportEditedDocumentsPrintCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/edited', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportEditedDocumentsFilterColumns()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/edited', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'list_column'   => 'user ddate',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportEditedDocumentsFilterResultOnOneColumn()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/edited', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'filter_column' => 'user',
            'filter_value'  => 'admin',
            'liste'         => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportEditedDocumentsFilterConf()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/edited', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'conf'          => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportEditedDocumentsGroupBy()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/edited', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'groupby'       => 'user',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportValidatedDocuments()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/validated', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
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

    public function testDoReportValidatedDocumentsPrintCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/validated', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportValidatedDocumentsFilterColumns()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/validated', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'list_column'   => 'user ddate',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportValidatedDocumentsFilterResultOnOneColumn()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/validated', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'filter_column' => 'user',
            'filter_value'  => 'admin',
            'liste'         => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportValidatedDocumentsFilterConf()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/validated', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'conf'          => 'on',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportValidatedDocumentsGroupBy()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/validated', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'groupby'       => 'user',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserBadRequest()
    {
        self::$DI['client']->request('POST', '/report/informations/user', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isOk());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testDoReportUser()
    {
        self::$DI['client']->request('POST', '/report/informations/user', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'user'          => self::$DI['user']->get_id(),
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromConnexion()
    {
        self::$DI['client']->request('POST', '/report/informations/user', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'user'          => self::$DI['user']->get_id(),
            'from'          => 'CNX',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromQuestion()
    {
        self::$DI['client']->request('POST', '/report/informations/user', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'ASK',
            'user'          => self::$DI['user']->get_id(),
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromDownload()
    {
        self::$DI['client']->request('POST', '/report/informations/user', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'GEN',
            'user'          => self::$DI['user']->get_id(),
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromConnexionCSV()
    {
        self::$DI['client']->request('POST', '/report/informations/user', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'CNX',
            'printcsv'      => 'on',
            'user'          => self::$DI['user']->get_id(),
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromQuestionCSV()
    {
        self::$DI['client']->request('POST', '/report/informations/user', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'ASK',
            'printcsv'      => 'on',
            'user'          => self::$DI['user']->get_id(),
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromDownloadCSV()
    {
        self::$DI['client']->request('POST', '/report/informations/user', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'GEN',
            'printcsv'      => 'on',
            'user'          => self::$DI['user']->get_id(),
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromDownloadOnCustomField()
    {
        self::$DI['client']->request('POST', '/report/informations/user', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'GEN',
            'on'            => 'usr_mail',
            'user'          => self::$DI['user']->get_email()
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromConnexionOnCustomField()
    {
        self::$DI['client']->request('POST', '/report/informations/user', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'CNX',
            'on'            => 'usr_mail',
            'user'          => self::$DI['user']->get_email()
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromQuestionOnCustomField()
    {
        self::$DI['client']->request('POST', '/report/informations/user', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'from'          => 'ASK',
            'on'            => 'usr_mail',
            'user'          => self::$DI['user']->get_email()
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInformationsBrowserBadRequest()
    {
        self::$DI['client']->request('POST', '/report/informations/browser', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isOk());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testDoReportInfomationsBrowser()
    {
        self::$DI['client']->request('POST', '/report/informations/browser', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'user'          => 'chrome',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInfomationsDocumentsNotFound()
    {
        self::$DI['client']->request('POST', '/report/informations/document', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'sbasid'        => 0,
            'rid'           => 0,
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isOk());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDoReportInfomationsDocuments()
    {
        self::$DI['client']->request('POST', '/report/informations/document', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'sbasid'        => self::$DI['record_1']->get_sbas_id(),
            'rid'           => self::$DI['record_1']->get_record_id(),
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInfomationsDocumentsFromTool()
    {
        self::$DI['client']->request('POST', '/report/informations/document', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'sbasid'        => self::$DI['record_1']->get_sbas_id(),
            'rid'           => self::$DI['record_1']->get_record_id(),
            'from'          => 'TOOL'
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInfomationsDocumentsFromDashboard()
    {
        self::$DI['client']->request('POST', '/report/informations/document', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'sbasid'        => self::$DI['record_1']->get_sbas_id(),
            'rid'           => self::$DI['record_1']->get_record_id(),
            'from'          => 'DASH'
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInfomationsDocumentsFromOther()
    {
        self::$DI['client']->request('POST', '/report/informations/document', array(
            'dmin'          => $this->dmin->format('d-m-Y'),
            'dmax'          => $this->dmax->format('d-m-Y'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'sbasid'        => self::$DI['record_1']->get_sbas_id(),
            'rid'           => self::$DI['record_1']->get_record_id(),
            'user'          => self::$DI['user']->get_id()
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }
}

