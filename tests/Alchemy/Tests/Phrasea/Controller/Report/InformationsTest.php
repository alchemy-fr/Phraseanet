<?php

namespace Alchemy\Tests\Phrasea\Controller\Report;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\HttpKernel\Client;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class InformationsTest extends \PhraseanetAuthenticatedWebTestCase
{
    /**
     * @var \DateTime
     */
    private $dmin;
    /**
     * @var \DateTime
     */
    private $dmax;

    /**
     * @var Client
     */
    private $client;

    public function setUp()
    {
        parent::setUp();

        $this->dmax = new \DateTime('now');
        $this->dmin = new \DateTime('-1 month');

        $this->client = $this->getClient();
    }

    public function testDoReportUserBadRequest()
    {
        $this->client->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
        ]);

        $response = $this->client->getResponse();

        $this->assertFalse($response->isOk());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testDoReportUser()
    {
        $this->client->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'user'          => $this->getUser()->getId(),
        ]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromConnexion()
    {
        $this->client->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'user'          => $this->getUser()->getId(),
            'from'          => 'CNX',
        ]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromQuestion()
    {
        $this->client->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'from'          => 'ASK',
            'user'          => $this->getUser()->getId(),
        ]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromDownload()
    {
        $this->client->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'from'          => 'GEN',
            'user'          => $this->getUser()->getId(),
        ]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromConnexionCSV()
    {
        $this->client->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'from'          => 'CNX',
            'printcsv'      => 'on',
            'user'          => $this->getUser()->getId(),
        ]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromQuestionCSV()
    {
        $this->client->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'from'          => 'ASK',
            'printcsv'      => 'on',
            'user'          => $this->getUser()->getId(),
        ]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromDownloadCSV()
    {
        $this->client->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'from'          => 'GEN',
            'printcsv'      => 'on',
            'user'          => $this->getUser()->getId(),
        ]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromDownloadOnCustomField()
    {
        $this->client->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'from'          => 'GEN',
            'on'            => 'email',
            'user'          => $this->getUser()->getEmail()
        ]);

        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromConnexionOnCustomField()
    {
        $this->client->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'from'          => 'CNX',
            'on'            => 'email',
            'user'          => $this->getUser()->getEmail()
        ]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportUserFromQuestionOnCustomField()
    {
        $this->client->request('POST', '/report/informations/user', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'from'          => 'ASK',
            'on'            => 'email',
            'user'          => $this->getUser()->getEmail()
        ]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInformationBrowserBadRequest()
    {
        $this->client->request('POST', '/report/informations/browser', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
        ]);

        $response = $this->client->getResponse();

        $this->assertFalse($response->isOk());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testDoReportInfomationsBrowser()
    {
        $this->client->request('POST', '/report/informations/browser', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'user'          => 'chrome',
        ]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInfomationsDocumentsNotFound()
    {
        $this->client->request('POST', '/report/informations/document', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'sbasid'        => 0,
            'rid'           => 0,
        ]);

        $response = $this->client->getResponse();

        $this->assertFalse($response->isOk());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDoReportInfomationsDocuments()
    {
        $this->client->request('POST', '/report/informations/document', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'sbasid'        => $this->getRecord1()->getDataboxId(),
            'rid'           => $this->getRecord1()->getRecordId(),
        ]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInfomationsDocumentsFromTool()
    {
        $this->client->request('POST', '/report/informations/document', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'sbasid'        => $this->getRecord1()->getDataboxId(),
            'rid'           => $this->getRecord1()->getRecordId(),
            'from'          => 'TOOL'
        ]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInfomationsDocumentsFromDashboard()
    {
        $this->client->request('POST', '/report/informations/document', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'sbasid'        => $this->getRecord1()->getDataboxId(),
            'rid'           => $this->getRecord1()->getRecordId(),
            'from'          => 'DASH'
        ]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testDoReportInfomationsDocumentsFromOther()
    {
        $this->client->request('POST', '/report/informations/document', [
            'dmin'          => $this->dmin->format('Y-m-d H:i:s'),
            'dmax'          => $this->dmax->format('Y-m-d H:i:s'),
            'sbasid'        => $this->getCollection()->get_sbas_id(),
            'collection'    => $this->getCollection()->get_coll_id(),
            'sbasid'        => $this->getRecord1()->getDataboxId(),
            'rid'           => $this->getRecord1()->getRecordId(),
            'user'          => $this->getUser()->getId()
        ]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @return User
     */
    private function getUser()
    {
        return self::$DI['user'];
    }
}
