<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\File;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class DataboxTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;
    private static $createdCollections = [];

    public function setUp()
    {
        parent::setUp();
        $this->dropDatabase();
    }

    public function tearDown()
    {
        if (!self::$createdCollections) {
            parent::tearDown();

            return;
        }

        foreach (self::$createdCollections as $collection) {
            try {
                $collection->unmount();
            } catch (\Exception $e) {

            }

            try {
                $collection->delete();
            } catch (\Exception $e) {

            }
        }

        self::$createdCollections = null;
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
        $databoxes = self::$DI['app']->getDataboxes();
        $collection = \collection::create(self::$DI['app'], array_shift($databoxes), self::$DI['app']['phraseanet.appbox'], 'TESTTODELETE');

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

        $app = $this->getApplication();
        $collection = \collection::create($app, $databox, $app['phraseanet.appbox'], 'TESTTODELETE');

        $response = $this->XMLHTTPRequest('POST', '/admin/databox/' . $databox->get_sbas_id() . '/collections/order/', [
            'order' => [
                2 => $collection->get_base_id()
            ]
        ]);

        $this->assertTrue($response->isOk());

        $databox->unmount_databox();
        $databox->delete();
        /**
         * @todo test if order is set
         */
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabaseCGU
     */
    public function testGetCGUHasNoRights()
    {
        $this->setAdmin(true, [
            'has_right_on_sbas'=> function (\PHPUnit_Framework_MockObject_MockObject $acl) {
                $acl->expects($this->once())
                    ->method('has_right_on_sbas')
                    ->with($this->equalTo(self::$DI['collection']->get_sbas_id()), \ACL::BAS_MODIFY_STRUCT)
                    ->will($this->returnValue(false));
            }
        ]);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/cgus/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabaseCGU
     */
    public function testGetCGU()
    {
        $this->setAdmin(true, [
            'has_right_on_sbas'=> function (\PHPUnit_Framework_MockObject_MockObject $acl) {
                $acl->expects($this->once())
                    ->method('has_right_on_sbas')
                    ->with($this->equalTo(self::$DI['collection']->get_sbas_id()), \ACL::BAS_MODIFY_STRUCT)
                    ->will($this->returnValue(true));
            }
        ]);

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

        self::$DI['client']->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/cgus/', [
            'TOU' => ['fr' => 'Test update CGUS']
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::updateDatabaseCGU
     */
    public function testUpdateDatabaseCGU()
    {
        $this->setAdmin(true, [
            'has_right_on_sbas'=> function (\PHPUnit_Framework_MockObject_MockObject $acl) {
                $acl->expects($this->once())
                    ->method('has_right_on_sbas')
                    ->with($this->equalTo(self::$DI['collection']->get_sbas_id()), \ACL::BAS_MODIFY_STRUCT)
                    ->will($this->returnValue(true));
            }
        ]);

        $cgusUpdate = 'Test update CGUS';

        $response = $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/cgus/', [
            'TOU' => ['fr' => $cgusUpdate]
        ]);

        $this->checkRedirection($response, '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/cgus/?success=1');

        $databox = self::$DI['app']->findDataboxById(self::$DI['collection']->get_sbas_id());
        $cgus = $databox->get_cgus();
        $this->assertEquals($cgus['fr']['value'], $cgusUpdate);
        unset($databox);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::progressBarInfos
     */
    public function testGetInformationDocumentBadRequest()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/informations/documents/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::progressBarInfos
     */
    public function testGetInformationDocument()
    {
        $this->setAdmin(true);

        $response = $this->XMLHTTPRequest('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/informations/documents/');

        $json = $this->getJson($response);
        $this->assertTrue($json->success);
        $this->assertObjectHasAttribute('sbas_id', $json);
        $this->assertObjectHasAttribute('indexable', $json);
        $this->assertObjectHasAttribute('counts', $json);
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
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabase
     */
    public function testGetDataboxUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getReorder
     */
    public function testGetCollectionOrderUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/collections/order/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabaseCGU
     */
    public function testGetCGUUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/cgus/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDetails
     *
     */
    public function testGetInformationDocumentUnauthorizedException()
    {
        $this->setAdmin(false);

        $response = $this->XMLHTTPRequest('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/informations/documents/');
        $this->assertXMLHTTPBadJsonResponse($response);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::progressBarInfos
     */
    public function testGetInformationDetailsUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/informations/details/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getNewCollection
     */
    public function testGetNewCollectionUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/collection/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
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

        $response = $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/reindex/');
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

        $response = $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/indexable/', [
            'indexable' => 1
        ]);

        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('sbas_id', $content, $response->getContent());

        $app = $this->getApplication();
        $this->assertTrue(!!$app->getApplicationBox()->is_databox_indexable($app->findDataboxById(self::$DI['collection']->get_sbas_id())));
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

        $response = $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/clear-logs/');

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

        self::$DI['client']->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/view-name/', [
            'viewname' => 'hello'
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::changeViewName
     */
    public function testPostViewNameBadRequestArguments()
    {
        $this->setAdmin(true);

        $response = $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/view-name/');
        $this->assertXMLHTTPBadJsonResponse($response);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::changeViewName
     */
    public function testPostViewName()
    {
        $this->setAdmin(true);

        $databox = self::$DI['app']->findDataboxById(self::$DI['collection']->get_sbas_id());
        $databox->set_viewname('old_databox_name');

        $this->assertEquals('old_databox_name', $databox->get_viewname());

        $response = $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/view-name/', [
            'viewname' => 'new_databox_name'
        ]);

        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('sbas_id', $content, $response->getContent());

        $databox = self::$DI['app']->findDataboxById(self::$DI['collection']->get_sbas_id());
        $this->assertEquals('new_databox_name', $databox->get_viewname());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::deleteBase
     */
    public function testDeleteBase()
    {
        $this->setAdmin(true);

        $base = $this->createDatabox();

        $response = $this->XMLHTTPRequest('POST', '/admin/databox/' . $base->get_sbas_id() . '/delete/');

        $json = $this->getJson($response);
        $this->assertTrue($json->success);
        $this->assertObjectHasAttribute('sbas_id', $json);

        try {
            self::$DI['app']->findDataboxById((int) $json->sbas_id);
            $this->fail('Databox not deleted');
        } catch (NotFoundHttpException $e) {

        }
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::mountCollection
     */
    public function testMountCollection()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();
        $collection->unmount();

        self::$DI['client']->request('POST', '/admin/databox/' . $collection->get_sbas_id() . '/collection/' . $collection->get_coll_id() . '/mount/', [
            'othcollsel' => self::$DI['collection']->get_base_id()
        ]);

        // delete mounted collection
        $sql = "DELETE FROM bas ORDER BY base_id DESC LIMIT 1";
        $stmt = self::$DI['app']->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
        unset($stmt);

        $this->checkRedirection(self::$DI['client']->getResponse(), '/admin/databox/' . $collection->get_sbas_id() . '/?mount=ok');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::sendLogoPdf
     */
    public function testSendLogoPdf()
    {
        $this->setAdmin(true);

        $target = tempnam(sys_get_temp_dir(), 'p4logo') . '.jpg';
        self::$DI['app']['filesystem']->copy(__DIR__ . '/../../../../../files/p4logo.jpg', $target);
        $files = [
            'newLogoPdf' => new \Symfony\Component\HttpFoundation\File\UploadedFile($target, 'logo.jpg')
        ];
        self::$DI['client']->request('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/logo/', [], $files);
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

        $response = $this->XMLHTTPRequest('POST', '/admin/databox/' . self::$DI['collection']->get_sbas_id() . '/logo/delete/');

        $json = $this->getJson($response);
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

        $response = $this->XMLHTTPRequest('POST', '/admin/databox/' . $base->get_sbas_id() . '/unmount/');

        $json = $this->getJson($response);
        $this->assertTrue($json->success);
        $this->assertObjectHasAttribute('sbas_id', $json);

        try {
            self::$DI['app']->findDataboxById((int) $json->sbas_id);
            $this->fail('Databox not unmounted');
        } catch (NotFoundHttpException $e) {

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
        $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../../files/test001.jpg'), $collection);
        \record_adapter::createFromFile($file, self::$DI['app']);

        if ($collection->get_record_amount() === 0) {
            $this->markTestSkipped('No record were added');
        }

        $response = $this->XMLHTTPRequest('POST', '/admin/databox/' . $base->get_sbas_id() . '/empty/');

        $json = $this->getJson($response);
        $this->assertTrue($json->success);
        $this->assertEquals(0, $collection->get_record_amount());

        $base->unmount_databox();
        $base->delete();
    }

    public function testSetLabelsDoesNotWorkIfNotAdmin()
    {
        $this->setAdmin(false);
        $base = self::$DI['record_1']->get_databox();
        self::$DI['client']->request('POST', '/admin/databox/' . $base->get_sbas_id() . '/labels/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    public function testSetLabels()
    {
        $this->setAdmin(true);
        $base = self::$DI['record_1']->get_databox();
        $response = $this->XMLHTTPRequest('POST', '/admin/databox/' . $base->get_sbas_id() . '/labels/', [
            'labels' => [
                'fr' => 'frenchy label',
                'en' => '',
                'de' => 'Jaja label',
                'nl' => 'dutch label',
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);

        $base = $this->getApplication()->findDataboxById($base->get_sbas_id());

        $this->assertEquals('frenchy label', $base->get_label('fr', false));
        $this->assertEquals('', $base->get_label('en', false));
        $this->assertEquals('Jaja label', $base->get_label('de', false));
        $this->assertEquals('dutch label', $base->get_label('nl', false));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Database::emptyDatabase
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
            $stmt->execute([
                ':coll_id'          => $collection->get_coll_id(),
                ':parent_record_id' => 0,
                ':type'             => 'unknown',
                ':sha256'           => null,
                ':uuid'             => Uuid::uuid4(),
                ':originalname'     => null,
                ':mime'             => null,
            ]);
            $i++;
        }

        $stmt->closeCursor();

        if ($collection->get_record_amount() < 500) {
            $this->markTestSkipped('No enough records added');
        }

        $response = $this->XMLHTTPRequest('POST', '/admin/databox/' . $base->get_sbas_id() . '/empty/');

        $json = $this->getJson($response);
        $this->assertTrue($json->success);

        if (count(self::$DI['app']['orm.em']->getRepository('Phraseanet:Task')->findAll()) === 0) {
            $this->fail('Task for empty collection has not been created');
        }

        $base->unmount_databox();
        $base->delete();
    }
}
