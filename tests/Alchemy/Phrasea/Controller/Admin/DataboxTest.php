<?php

use Alchemy\Phrasea\Core\Configuration;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\DriversContainer;
use Symfony\Component\Filesystem\Filesystem;

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class DataboxTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;
    protected static $createdCollections = array();
    protected static $createdDataboxes = array();

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::dropDatabase();
    }

    public function setUp()
    {
        self::dropDatabase();
        parent::setUp();
    }

    public static function tearDownAfterClass()
    {
        foreach (self::$createdCollections as $collection) {
            try {
                $collection->unmount_collection(self::$application);
            } catch (\Exception $e) {

            }

            try {
                $collection->delete();
            } catch (\Exception $e) {

            }
        }

        self::$createdCollections = null;

        self::dropDatabase();
        parent::tearDownAfterClass();
    }

    public function tearDown()
    {
        self::dropDatabase();
        parent::tearDown();
    }

    public function getJson($response)
    {
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('success', $content, $response->getContent());
        $this->assertObjectHasAttribute('msg', $content, $response->getContent());

        return $content;
    }

    public function createOneCollection()
    {
        $collection = \collection::create(self::$application, array_shift(self::$application['phraseanet.appbox']->get_databoxes()), self::$application['phraseanet.appbox'], 'TESTTODELETE');

        self::$application['phraseanet.user']->ACL();

        self::$createdCollections[] = $collection;

        return $collection;
    }

    public function createDatabox()
    {
        $registry = self::$application['phraseanet.registry'];

        $this->createDatabase();

        $configuration = self::$application['phraseanet.configuration'];

        $choosenConnexion = $configuration->getPhraseanet()->get('database');
        $connexion = $configuration->getConnexion($choosenConnexion);

        try {
            $conn = new \connection_pdo('databox_creation', $connexion->get('host'), $connexion->get('port'), $connexion->get('user'), $connexion->get('password'), 'unit_test_db', array(), $registry);
        } catch (\PDOException $e) {

            $this->markTestSkipped('Could not reach DB');
        }

        $databox = \databox::create(
                self::$application, $conn, new \SplFileInfo($registry->get('GV_RootPath') . 'lib/conf.d/data_templates/fr-simple.xml'), $registry
        );

        self::$createdDataboxes[] = $databox;

        $databox->registerAdmin(self::$application['phraseanet.user']);

        return $databox;
    }

    public function checkRedirection($response, $location)
    {
        $this->assertTrue($response->isRedirect());
        $this->assertEquals($location, $response->headers->get('location'));
    }

    public static function dropDatabase()
    {
        $stmt = self::$application['phraseanet.appbox']
            ->get_connection()
            ->prepare('DROP DATABASE IF EXISTS `unit_test_db`');
        $stmt->execute();
        $stmt = self::$application['phraseanet.appbox']
            ->get_connection()
            ->prepare('DELETE FROM sbas WHERE dbname = "unit_test_db"');
        $stmt->execute();
    }

    protected function createDatabase()
    {
        self::dropDatabase();

        $stmt = self::$application['phraseanet.appbox']
            ->get_connection()
            ->prepare('CREATE DATABASE `unit_test_db`
              CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabase
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::connect
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::call
     */
    public function testGetDatabox()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/admin/databox/' . self::$collection->get_sbas_id() . '/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getReorder
     */
    public function testGetCollectionOrder()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/admin/databox/' . self::$collection->get_sbas_id() . '/collections/order/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::setReorder
     */
    public function testSetReorder()
    {
        $databox = $this->createDatabox();

        $this->setAdmin(true);

        $collection = \collection::create(self::$application, $databox, self::$application['phraseanet.appbox'], 'TESTTODELETE');

        $this->XMLHTTPRequest('POST', '/admin/databox/' . $databox->get_sbas_id() . '/collections/order/', array(
            'order' => array(
                2 => $collection->get_base_id()
            )));

        $this->assertTrue($this->client->getResponse()->isOk());

        /**
         * @todo test if order is set
         */
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabaseCGU
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testGetCGUHasNoRights()
    {
        $this->StubbedACL->expects($this->once())
            ->method('has_right_on_sbas')
            ->with($this->equalTo(self::$collection->get_sbas_id()), 'bas_modify_struct')
            ->will($this->returnValue(false));

        $this->setAdmin(true);

        $this->client->request('GET', '/admin/databox/' . self::$collection->get_sbas_id() . '/cgus/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabaseCGU
     */
    public function testGetCGU()
    {
        $this->StubbedACL->expects($this->once())
            ->method('has_right_on_sbas')
            ->with($this->equalTo(self::$collection->get_sbas_id()), 'bas_modify_struct')
            ->will($this->returnValue(true));

        $this->setAdmin(true);

        $this->client->request('GET', '/admin/databox/' . self::$collection->get_sbas_id() . '/cgus/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::updateDatabaseCGU
     */
    public function testUpdateDatabaseCGNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $this->client->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/cgus/', array(
            'TOU' => array('fr_FR' => 'Test update CGUS')
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::updateDatabaseCGU
     */
    public function testUpdateDatabaseCGU()
    {
        $this->StubbedACL->expects($this->once())
            ->method('has_right_on_sbas')
            ->with($this->equalTo(self::$collection->get_sbas_id()), 'bas_modify_struct')
            ->will($this->returnValue(true));

        $this->setAdmin(true);

        $cgusUpdate = 'Test update CGUS';

        $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$collection->get_sbas_id() . '/cgus/', array(
            'TOU' => array('fr_FR' => $cgusUpdate)
        ));

        $this->checkRedirection($this->client->getResponse(), '/admin/databox/' . self::$collection->get_sbas_id() . '/cgus/?success=1');

        $databox = self::$application['phraseanet.appbox']->get_databox(self::$collection->get_sbas_id());
        $cgus = $databox->get_cgus();
        $this->assertEquals($cgus['fr_FR']['value'], $cgusUpdate);
        unset($databox);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::progressBarInfos
     */
    public function testGetInformationDocumentBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/admin/databox/' . self::$collection->get_sbas_id() . '/informations/documents/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::progressBarInfos
     */
    public function testGetInformationDocument()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('GET', '/admin/databox/' . self::$collection->get_sbas_id() . '/informations/documents/');

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
        $this->assertObjectHasAttribute('sbas_id', $json);
        $this->assertObjectHasAttribute('indexable', $json);
        $this->assertObjectHasAttribute('records', $json);
        $this->assertObjectHasAttribute('xml_indexed', $json);
        $this->assertObjectHasAttribute('thesaurus_indexed', $json);
        $this->assertObjectHasAttribute('viewname', $json);
        $this->assertObjectHasAttribute('printLogoURL', $json);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDetails
     */
    public function testGetInformationDetails()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/admin/databox/' . self::$collection->get_sbas_id() . '/informations/details/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getNewCollection
     */
    public function testGetNewCollection()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/admin/databox/' . self::$collection->get_sbas_id() . '/collection/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabase
     */
    public function testGetDataboxUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/admin/databox/' . self::$collection->get_sbas_id() . '/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getReorder
     */
    public function testGetCollectionOrderUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/admin/databox/' . self::$collection->get_sbas_id() . '/collections/order/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabaseCGU
     */
    public function testGetCGUUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/admin/databox/' . self::$collection->get_sbas_id() . '/cgus/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDetails
     *
     */
    public function testGetInformationDocumentUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->XMLHTTPRequest('GET', '/admin/databox/' . self::$collection->get_sbas_id() . '/informations/documents/');
        $this->assertXMLHTTPBadJsonResponse($this->client->getResponse());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::progressBarInfos
     */
    public function testGetInformationDetailsUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/admin/databox/' . self::$collection->get_sbas_id() . '/informations/details/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getNewCollection
     */
    public function testGetNewCollectionUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/admin/databox/' . self::$collection->get_sbas_id() . '/collection/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::reindex
     */
    public function testPostReindexNotJson()
    {
        $collection = $this->createOneCollection();

        $this->client->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/reindex/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::reindex
     */
    public function testPostReindex()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$collection->get_sbas_id() . '/reindex/');
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('sbas_id', $content, $response->getContent());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::setIndexable
     */
    public function testPostIndexableNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOnecollection();

        $this->client->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/indexable/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::setIndexable
     */
    public function testPostIndexable()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$collection->get_sbas_id() . '/indexable/', array(
            'indexable' => 1
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('sbas_id', $content, $response->getContent());

        $this->assertTrue(!!self::$application['phraseanet.appbox']->is_databox_indexable(new \databox(self::$application, self::$collection->get_sbas_id())));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::clearLogs
     */
    public function testPostClearLogNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOnecollection();

        $this->client->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/clear-logs/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::clearLogs
     */
    public function testPostClearLogs()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$collection->get_sbas_id() . '/clear-logs/');

        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('sbas_id', $content, $response->getContent());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::changeViewName
     */
    public function testChangeViewNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOnecollection();

        $this->client->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/view-name/', array(
            'viewname' => 'hello'
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::changeViewName
     */
    public function testPostViewNameBadRequestArguments()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$collection->get_sbas_id() . '/view-name/');
        $this->assertXMLHTTPBadJsonResponse($this->client->getResponse());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::changeViewName
     */
    public function testPostViewName()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$collection->get_sbas_id() . '/view-name/', array(
            'viewname' => 'new_databox_name'
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('sbas_id', $content, $response->getContent());

        $databox = new \databox(self::$application, self::$collection->get_sbas_id());
        $this->assertEquals('new_databox_name', $databox->get_viewname());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::createDatabase
     */
    public function testCreateDatabaseEmpty()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/databox/', array(
            'new_dbname' => ''
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/admin/databoxes/?error=no-empty', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::createDatabase
     */
    public function testCreateDatabaseSpecialChar()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/databox/', array(
            'new_dbname' => 'ééààèè'
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/admin/databoxes/?error=special-chars', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::createDatabase
     */
    public function testCreateDatabase()
    {
        $this->setAdmin(true);

        $this->createDatabase();

        $this->client->request('POST', '/admin/databox/', array(
            'new_dbname'        => 'unit_test_db',
            'new_data_template' => 'fr-simple',
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $uriRedirect = $response->headers->get('location');
        $this->assertTrue(!!strrpos($uriRedirect, 'success=1'));
        $explode = explode('/', $uriRedirect);
        $databoxId = $explode[3];
        $databox = self::$application['phraseanet.appbox']->get_databox($databoxId);
        $databox->unmount_databox(self::$application['phraseanet.appbox']);
        $databox->delete();

        unset($stmt, $databox);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::deleteBase
     */
    public function testDeleteBase()
    {
        $this->setAdmin(true);

        $base = $this->createDatabox();

        $this->XMLHTTPRequest('POST', '/admin/databox/' . $base->get_sbas_id() . '/delete/');

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
        $this->assertObjectHasAttribute('sbas_id', $json);

        try {
            self::$application['phraseanet.appbox']->get_databox((int) $json->sbas_id);
            $this->fail('Databox not deleted');
        } catch (\Exception_DataboxNotFound $e) {

        }
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::databaseMount
     */
    public function testMountBase()
    {
        $this->setAdmin(true);

        $base = $this->createDatabox();
        $base->unmount_databox(self::$application['phraseanet.appbox']);

        $this->client->request('POST', '/admin/databox/mount/', array(
            'new_dbname' => 'unit_test_db'
        ));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $uriRedirect = $response->headers->get('location');


        $this->assertTrue(!!strrpos($uriRedirect, 'success=1'));
        $explode = explode('/', $uriRedirect);
        $databoxId = $explode[3];

        try {
            $databox = self::$application['phraseanet.appbox']->get_databox($databoxId);
            $databox->unmount_databox(self::$application['phraseanet.appbox']);
            $databox->delete();
        } catch (\Exception_DataboxNotFound $e) {
            $this->fail('databox not mounted');
        }

        unset($databox);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::mountCollection
     */
    public function testMountCollection()
    {
        $this->markTestSkipped();
        $this->setAdmin(true);

        $collection = $this->createOneCollection();
        $collection->unmount_collection(self::$application);

        $this->client->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/collection/' . $collection->get_coll_id() . '/mount/', array(
            'othcollsel' => self::$collection->get_base_id()
        ));

        $this->checkRedirection($this->client->getResponse(), '/admin/databox/' . $collection->get_sbas_id() . '/?mount=ok');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::sendLogoPdf
     */
    public function testSendLogoPdf()
    {
        $this->setAdmin(true);

        $target = tempnam(sys_get_temp_dir(), 'p4logo') . '.jpg';
        self::$application['filesystem']->copy(__DIR__ . '/../../../../testfiles/p4logo.jpg', $target);
        $files = array(
            'newLogoPdf' => new \Symfony\Component\HttpFoundation\File\UploadedFile($target, 'logo.jpg')
        );
        $this->client->request('POST', '/admin/databox/' . self::$collection->get_sbas_id() . '/logo/', array(), $files);
        $this->checkRedirection($this->client->getResponse(), '/admin/databox/' . self::$collection->get_sbas_id() . '/?success=1');
        $this->assertNotEmpty(\databox::getPrintLogo(self::$collection->get_sbas_id()));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::deleteLogoPdf
     */
    public function testDeleteLogoPdf()
    {
        $this->setAdmin(true);

        if ('' === trim(\databox::getPrintLogo(self::$collection->get_sbas_id()))) {
            $this->markTestSkipped('No logo setted');
        }

        $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$collection->get_sbas_id() . '/logo/delete/');

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
        $this->assertEmpty(\databox::getPrintLogo(self::$collection->get_sbas_id()));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::unmountDatabase
     */
    public function testUnmountDatabox()
    {
        $this->setAdmin(true);

        $base = $this->createDatabox();

        $this->XMLHTTPRequest('POST', '/admin/databox/' . $base->get_sbas_id() . '/unmount/');

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
        $this->assertObjectHasAttribute('sbas_id', $json);

        try {
            self::$application['phraseanet.appbox']->get_databox((int) $json->sbas_id);
            $this->fail('Databox not unmounted');
        } catch (\Exception_DataboxNotFound $e) {

        }

        $base->delete();
        unset($base);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::emptyDatabase
     */
    public function testEmptyDatabase()
    {
        $this->setAdmin(true);

        $base = $this->createDatabox();
        $collection = \collection::create(self::$application, $base, self::$application['phraseanet.appbox'], 'TESTTODELETE');
        self::$createdCollections[] = $collection;
        $file = new \Alchemy\Phrasea\Border\File(self::$application['mediavorus']->guess(__DIR__ . '/../../../../testfiles/test001.CR2'), $collection);
        \record_adapter::createFromFile($file, self::$application);

        if ($collection->get_record_amount() === 0) {
            $this->markTestSkipped('No record were added');
        }

        $this->XMLHTTPRequest('POST', '/admin/databox/' . $base->get_sbas_id() . '/empty/');

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
        $this->assertEquals(0, $collection->get_record_amount());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::emptyDatabase
     */
    public function testPostEmptyBaseWithHighRecordAmount()
    {
        $this->setAdmin(true);

        $base = $this->createDatabox();
        $collection = \collection::create(self::$application, $base, self::$application['phraseanet.appbox'], 'TESTTODELETE');
        self::$createdCollections[] = $collection;

        $sql = 'INSERT INTO record
              (coll_id, record_id, parent_record_id, moddate, credate
                , type, sha256, uuid, originalname, mime)
            VALUES
              (:coll_id, null, :parent_record_id, NOW(), NOW()
              , :type, :sha256, :uuid
              , :originalname, :mime)';

        $stmt = $base->get_connection()->prepare($sql);
        $i = 0;
        while ($i < 502) {
            $stmt->execute(array(
                ':coll_id'          => $collection->get_coll_id(),
                ':parent_record_id' => 0,
                ':type'             => 'unknown',
                ':sha256'           => null,
                ':uuid'             => \uuid::generate_v4(),
                ':originalname'     => null,
                ':mime'             => null,
            ));
            $i++;
        }

        $stmt->closeCursor();

        if ($collection->get_record_amount() < 500) {
            $this->markTestSkipped('No enough records added');
        }

        $this->XMLHTTPRequest('POST', '/admin/databox/' . $base->get_sbas_id() . '/empty/');

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);

        $taskManager = new \task_manager(self::$application);
        $tasks = $taskManager->getTasks();

        $found = false;
        foreach ($tasks as $task) {
            if ($task->getName() === \task_period_emptyColl::getName()) {
                $found = true;
                $task->delete();
            }
        }

        if (!$found) {
            $this->fail('Task for empty collection has not been created');
        }
    }
}
