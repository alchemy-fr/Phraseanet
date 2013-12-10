<?php

namespace Alchemy\Tests\Phrasea\Controller\Report;

class ActivityTest extends \PhraseanetAuthenticatedWebTestCase
{
    private $dmin;
    private $dmax;

    public function __construct()
    {
        $this->dmax = new \DateTime('now');
        $this->dmin = new \DateTime('-1 month');
    }

    public function testDoReportConnexionsByUsers()
    {
        self::$DI['client']->request('POST', '/report/activity/users/connexions', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'page'          => 1,
            'limit'         => 10,
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportConnexionsByUsersCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/users/connexions', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'page'          => 1,
            'limit'         => 10,
            'printcsv'      => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDownloadsByUsers()
    {
        self::$DI['client']->request('POST', '/report/activity/users/downloads', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'page'          => 1,
            'limit'         => 10,
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportDownloadsByUsersCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/users/downloads', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'page'          => 1,
            'limit'         => 10,
            'printcsv'      => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportBestOfQuestions()
    {
        self::$DI['client']->request('POST', '/report/activity/questions/best-of', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'limit'         => 10,
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportBestOfQuestionsCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/questions/best-of', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'limit'         => 10,
            'printcsv'      => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportNoBestOfQuestions()
    {
        self::$DI['client']->request('POST', '/report/activity/questions/no-best-of', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'limit'         => 10,
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportNoBestOfQuestionsCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/questions/no-best-of', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'limit'         => 10,
            'printcsv'      => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportSiteActiviyPerHours()
    {
        self::$DI['client']->request('POST', '/report/activity/instance/hours', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportSiteActiviyPerHoursCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/instance/hours', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportSiteActiviyPerDays()
    {
        self::$DI['client']->request('POST', '/report/activity/instance/days', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportSiteActiviyPerDaysCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/instance/days', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportPushedDocuments()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/pushed', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'order'         => 'ASC',
            'champ'         => 'user',
            'page'          => 1,
            'limit'         => 10,
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportPushedDocumentsPrintCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/pushed', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportPushedDocumentsFilterColumns()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/pushed', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'list_column'   => 'user ddate',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportPushedDocumentsFilterResultOnOneColumn()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/pushed', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'filter_column' => 'user',
            'filter_value'  => 'admin',
            'liste'         => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportPushedDocumentsFilterConf()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/pushed', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'conf'          => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportPushedDocumentsGroupBy()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/pushed', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'groupby'       => 'user',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportAddedDocuments()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/added', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'order'         => 'ASC',
            'champ'         => 'user',
            'page'          => 1,
            'limit'         => 10,
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportAddedDocumentsPrintCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/added', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportAddedDocumentsFilterColumns()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/added', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'list_column'   => 'user ddate',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportAddedDocumentsFilterResultOnOneColumn()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/added', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'filter_column' => 'user',
            'filter_value'  => 'admin',
            'liste'         => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportAddedDocumentsFilterConf()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/added', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'conf'          => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportAddedDocumentsGroupBy()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/added', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'groupby'       => 'user',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportEditedDocuments()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/edited', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'order'         => 'ASC',
            'champ'         => 'user',
            'page'          => 1,
            'limit'         => 10,
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportEditedDocumentsPrintCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/edited', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportEditedDocumentsFilterColumns()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/edited', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'list_column'   => 'user ddate',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportEditedDocumentsFilterResultOnOneColumn()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/edited', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'filter_column' => 'user',
            'filter_value'  => 'admin',
            'liste'         => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportEditedDocumentsFilterConf()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/edited', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'conf'          => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportEditedDocumentsGroupBy()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/edited', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'groupby'       => 'user',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportValidatedDocuments()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/validated', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'order'         => 'ASC',
            'champ'         => 'user',
            'page'          => 1,
            'limit'         => 10,
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportValidatedDocumentsPrintCSV()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/validated', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'printcsv'      => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportValidatedDocumentsFilterColumns()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/validated', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'list_column'   => 'user ddate',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportValidatedDocumentsFilterResultOnOneColumn()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/validated', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'filter_column' => 'user',
            'filter_value'  => 'admin',
            'liste'         => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportValidatedDocumentsFilterConf()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/validated', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'conf'          => 'on',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportValidatedDocumentsGroupBy()
    {
        self::$DI['client']->request('POST', '/report/activity/documents/validated', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => self::$DI['collection']->get_sbas_id(),
            'collection'    => self::$DI['collection']->get_coll_id(),
            'groupby'       => 'user',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }
}
