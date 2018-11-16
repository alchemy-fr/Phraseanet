<?php

namespace Alchemy\Tests\Phrasea\Controller\Report;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
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

    public function testDoReportSiteActivityPerDays()
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

    public function testDoReportSiteActivityPerDaysCSV()
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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
        $this->markTestSkipped(
            'Report is broken since table "log_colls" is deleted.'
        );

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
