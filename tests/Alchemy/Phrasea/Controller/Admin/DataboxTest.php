<?php

use Alchemy\Phrasea\Application;

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class DataboxTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;
    protected static $createdCollections = array();

    public function setUp()
    {
        self::$DI['app'] = new Application('test');
        self::dropDatabase();
        parent::setUp();
    }

    public static function tearDownAfterClass()
    {
        foreach (self::$createdCollections as $collection) {
            try {
                $collection->unmount_collection(self::$DI['app']);
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
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
        $collection = \collection::create(self::$DI['app'], array_shift($databoxes), self::$DI['app']['phraseanet.appbox'], 'TESTTODELETE');

        self::$DI['app']['phraseanet.user']->ACL();

        self::$createdCollections[] = $collection;

        return $collection;
    }

    public function checkRedirection($response, $location)
    {
        $this->assertTrue($response->isRedirect());
        $this->assertEquals($location, $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabase
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::connect
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::call
     */
    public function testGetDatabox()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getReorder
     */
    public function testGetCollectionOrder()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/collections/order/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::setReorder
     */
    public function testSetReorder()
    {
        $databox = $this->createDatabox();

        $this->setAdmin(true);

        $collection = \collection::create(self::$DI['app'], $databox, self::$DI['app']['phraseanet.appbox'], 'TESTTODELETE');

        $this->XMLHTTPRequest('POST', '/admin/databox/' . $databox->get_sbas_id() . '/collections/order/', array(
            'order' => array(
                2 => $collection->get_base_id()
            )));

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

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
            ->with($this->equalTo(self::$DI['collection']->get_sbas_id()), 'bas_modify_struct')
            ->will($this->returnValue(false));

        $this->setAdmin(true);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/cgus/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabaseCGU
     */
    public function testGetCGU()
    {
        $this->StubbedACL->expects($this->once())
            ->method('has_right_on_sbas')
            ->with($this->equalTo(self::$DI['collection']->get_sbas_id()), 'bas_modify_struct')
            ->will($this->returnValue(true));

        $this->setAdmin(true);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/cgus/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::updateDatabaseCGU
     */
    public function testUpdateDatabaseCGNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        self::$DI['client']->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/cgus/', array(
            'TOU' => array('fr_FR' => 'Test update CGUS')
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::updateDatabaseCGU
     */
    public function testUpdateDatabaseCGU()
    {
        $this->StubbedACL->expects($this->once())
            ->method('has_right_on_sbas')
            ->with($this->equalTo(self::$DI['collection']->get_sbas_id()), 'bas_modify_struct')
            ->will($this->returnValue(true));

        $this->setAdmin(true);

        $cgusUpdate = 'Test update CGUS';

        $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/cgus/', array(
            'TOU' => array('fr_FR' => $cgusUpdate)
        ));

        $this->checkRedirection(self::$DI['client']->getResponse(), '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/cgus/?success=1');

        $databox = self::$DI['app']['phraseanet.appbox']->get_databox(self::$DI['collection']->get_sbas_id());
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

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/informations/documents/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::progressBarInfos
     */
    public function testGetInformationDocument()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/informations/documents/');

        $json = $this->getJson(self::$DI['client']->getResponse());
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

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/informations/details/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getNewCollection
     */
    public function testGetNewCollection()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/collection/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabase
     */
    public function testGetDataboxUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getReorder
     */
    public function testGetCollectionOrderUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/collections/order/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabaseCGU
     */
    public function testGetCGUUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/cgus/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDetails
     *
     */
    public function testGetInformationDocumentUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->XMLHTTPRequest('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/informations/documents/');
        $this->assertXMLHTTPBadJsonResponse(self::$DI['client']->getResponse());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::progressBarInfos
     */
    public function testGetInformationDetailsUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/informations/details/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getNewCollection
     */
    public function testGetNewCollectionUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/collection/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::reindex
     */
    public function testPostReindexNotJson()
    {
        $collection = $this->createOneCollection();

        self::$DI['client']->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/reindex/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::reindex
     */
    public function testPostReindex()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/reindex/');
        $response = self::$DI['client']->getResponse();
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

        self::$DI['client']->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/indexable/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::setIndexable
     */
    public function testPostIndexable()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/indexable/', array(
            'indexable' => 1
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('sbas_id', $content, $response->getContent());

        $this->assertTrue(!!self::$DI['app']['phraseanet.appbox']->is_databox_indexable(new \databox(self::$DI['app'], self::$DI['collection']->get_sbas_id())));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::clearLogs
     */
    public function testPostClearLogNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOnecollection();

        self::$DI['client']->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/clear-logs/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::clearLogs
     */
    public function testPostClearLogs()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/clear-logs/');

        $response = self::$DI['client']->getResponse();
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

        self::$DI['client']->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/view-name/', array(
            'viewname' => 'hello'
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::changeViewName
     */
    public function testPostViewNameBadRequestArguments()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/view-name/');
        $this->assertXMLHTTPBadJsonResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::changeViewName
     */
    public function testPostViewName()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/view-name/', array(
            'viewname' => 'new_databox_name'
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('sbas_id', $content, $response->getContent());

        $databox = new \databox(self::$DI['app'], self::$DI['collection']->get_sbas_id());
        $this->assertEquals('new_databox_name', $databox->get_viewname());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::deleteBase
     */
    public function testDeleteBase()
    {
        $this->setAdmin(true);

        $base = $this->createDatabox();

        $this->XMLHTTPRequest('POST', '/admin/databox/' . $base->get_sbas_id() . '/delete/');

        $json = $this->getJson(self::$DI['client']->getResponse());
        $this->assertTrue($json->success);
        $this->assertObjectHasAttribute('sbas_id', $json);

        try {
            self::$DI['app']['phraseanet.appbox']->get_databox((int) $json->sbas_id);
            $this->fail('Databox not deleted');
        } catch (\Exception_DataboxNotFound $e) {

        }
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::mountCollection
     */
    public function testMountCollection()
    {
        $this->markTestSkipped();
        $this->setAdmin(true);

        $collection = $this->createOneCollection();
        $collection->unmount_collection(self::$DI['app']);

        self::$DI['client']->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/collection/' . $collection->get_coll_id() . '/mount/', array(
            'othcollsel' => self::$DI['collection']->get_base_id()
        ));

        $this->checkRedirection(self::$DI['client']->getResponse(), '/admin/databox/' . $collection->get_sbas_id() . '/?mount=ok');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::sendLogoPdf
     */
    public function testSendLogoPdf()
    {
        $this->setAdmin(true);

        $target = tempnam(sys_get_temp_dir(), 'p4logo') . '.jpg';
        self::$DI['app']['filesystem']->copy(__DIR__ . '/../../../../testfiles/p4logo.jpg', $target);
        $files = array(
            'newLogoPdf' => new \Symfony\Component\HttpFoundation\File\UploadedFile($target, 'logo.jpg')
        );
        self::$DI['client']->request('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/logo/', array(), $files);
        $this->checkRedirection(self::$DI['client']->getResponse(), '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/?success=1');
        $this->assertNotEmpty(\databox::getPrintLogo(self::$DI['collection']->get_sbas_id()));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::deleteLogoPdf
     */
    public function testDeleteLogoPdf()
    {
        $this->setAdmin(true);

        if ('' === trim(\databox::getPrintLogo(self::$DI['collection']->get_sbas_id()))) {
            $this->markTestSkipped('No logo setted');
        }

        $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/logo/delete/');

        $json = $this->getJson(self::$DI['client']->getResponse());
        $this->assertTrue($json->success);
        $this->assertEmpty(\databox::getPrintLogo(self::$DI['collection']->get_sbas_id()));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::unmountDatabase
     */
    public function testUnmountDatabox()
    {
        $this->setAdmin(true);

        $base = $this->createDatabox();

        $this->XMLHTTPRequest('POST', '/admin/databox/' . $base->get_sbas_id() . '/unmount/');

        $json = $this->getJson(self::$DI['client']->getResponse());
        $this->assertTrue($json->success);
        $this->assertObjectHasAttribute('sbas_id', $json);

        try {
            self::$DI['app']['phraseanet.appbox']->get_databox((int) $json->sbas_id);
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
        $collection = \collection::create(self::$DI['app'], $base, self::$DI['app']['phraseanet.appbox'], 'TESTTODELETE');
        self::$createdCollections[] = $collection;
        $file = new \Alchemy\Phrasea\Border\File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../testfiles/test001.CR2'), $collection);
        \record_adapter::createFromFile($file, self::$DI['app']);

        if ($collection->get_record_amount() === 0) {
            $this->markTestSkipped('No record were added');
        }

        $this->XMLHTTPRequest('POST', '/admin/databox/' . $base->get_sbas_id() . '/empty/');

        $json = $this->getJson(self::$DI['client']->getResponse());
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
        $collection = \collection::create(self::$DI['app'], $base, self::$DI['app']['phraseanet.appbox'], 'TESTTODELETE');
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

        $json = $this->getJson(self::$DI['client']->getResponse());
        $this->assertTrue($json->success);

        $taskManager = new \task_manager(self::$DI['app']);
        $tasks = $taskManager->getTasks();

        $found = false;
        foreach ($tasks as $task) {
            if (get_class($task) === 'task_period_emptyColl') {
                $found = true;
                $task->delete();
            }
        }

        if (!$found) {
            $this->fail('Task for empty collection has not been created');
        }
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::saveThesaurus
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::get_thesaurus
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::get_dom_thesaurus
     */
    public function test_get_thesaurus()
    {
        $testValue = rand(1000, 9999);
        $xmlth = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n"
            . "<thesaurus version=\"2.0.5\" creation_date=\"20100101000000\" modification_date=\"20100101000000\" nextid=\"4\">"
            . "<testnode value=\"" . $testValue . "\"/>"
            . "</thesaurus>\n";

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xmlth);

        $databox = $this->createDatabox();
        $databox->saveThesaurus($dom);

        // get back as xml text
        $newxml = $databox->get_thesaurus();
        // xml must be ok
        $this->assertTrue($dom->loadXML($newxml));
        // xml must match but with updated date
        $this->assertTrue("20100101000000" != $dom->documentElement->getAttribute("modification_date"));
        // check xml was saved
        $this->assertTrue($testValue == $dom->documentElement->firstChild->getAttribute("value"));

        // get back as dom
        $newdom = $databox->get_dom_thesaurus();
        $this->assertTrue("20100101000000" != $newdom->documentElement->getAttribute("modification_date"));
        $this->assertTrue($testValue == $newdom->documentElement->firstChild->getAttribute("value"));

        /**
         * @todo : check results with bad thesaurus
         */
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::saveCterms
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::get_cterms
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::get_dom_cterms
     */
    public function test_get_cterms()
    {
        $testValue = rand(1000, 9999);
        $xmlth = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n"
            . "<cterms version=\"2.0.5\" creation_date=\"20100101000000\" modification_date=\"20100101000000\" nextid=\"4\">"
            . "<testnode value=\"" . $testValue . "\"/>"
            . "</cterms>\n";

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xmlth);

        $databox = $this->createDatabox();
        $databox->saveCterms($dom);

        // get back as xml text
        $newxml = $databox->get_cterms();
        // xml must be ok
        $this->assertTrue($dom->loadXML($newxml));
        // xml must match but with updated date
        $this->assertTrue("20100101000000" != $dom->documentElement->getAttribute("modification_date"));
        // check xml was saved
        $this->assertTrue($testValue == $dom->documentElement->firstChild->getAttribute("value"));

        // get back as dom
        $newdom = $databox->get_dom_cterms();
        $this->assertTrue("20100101000000" != $newdom->documentElement->getAttribute("modification_date"));
        $this->assertTrue($testValue == $newdom->documentElement->firstChild->getAttribute("value"));

        /**
         * @todo : check results with bad cterms
         */
    }
}
