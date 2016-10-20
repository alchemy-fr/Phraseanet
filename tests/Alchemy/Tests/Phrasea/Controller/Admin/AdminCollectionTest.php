<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Border\File;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class AdminCollectionTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;
    public static $createdCollections = [];

    public function setUp()
    {
        parent::setUp();
        self::resetUsersRights(self::$DI['app'], self::$DI['user']);
    }

    public function tearDown()
    {
        self::$DI['app']['acl'] = new ACLProvider(self::$DI['app']);
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

        self::$createdCollections = [];
        // /!\ re enable collection
        self::$DI['collection']->enable(self::$DI['app']['phraseanet.appbox']);

        parent::tearDown();
    }

    public function getJson(Response $response)
    {
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('success', $content, $response->getContent());
        $this->assertObjectHasAttribute('msg', $content, $response->getContent());

        return $content;
    }

    public function checkRedirection($response, $location)
    {
        $this->assertTrue($response->isRedirect());
        $this->assertEquals($location, $response->headers->get('location'));
    }

    public function createOneCollection()
    {
        $databoxes = self::$DI['app']->getDataboxes();
        $collection = \collection::create(self::$DI['app'], array_shift($databoxes), self::$DI['app']['phraseanet.appbox'], 'TESTTODELETE');

        self::$createdCollections[] = $collection;

        return $collection;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::connect
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getCollection
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::call
     */
    public function testGetCollection()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('GET', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getSuggestedValues
     */
    public function testGetSuggestedValues()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('GET', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/suggested-values/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getDetails
     */
    public function testInformationsDetails()
    {
        $this->setAdmin(true);

        $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../../files/test001.jpg'), self::$DI['collection']);
        \record_adapter::createFromFile($file, self::$DI['app']);

        self::$DI['client']->request('GET', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/informations/details/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::submitSuggestedValues
     */
    public function testPostSuggestedValuesNotJson()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/suggested-values/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::submitSuggestedValues
     */
    public function testPostSuggestedValueUnauthorized()
    {
        $this->setAdmin(false);

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/suggested-values/');
        $this->assertXMLHTTPBadJsonResponse($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::submitSuggestedValues
     */
    public function testPostSuggestedValue()
    {
        $this->setAdmin(true);

        $prefs = '<?xml version="1.0" encoding="UTF-8"?> <baseprefs> <status>0</status> <sugestedValues> <Object> <value>my_new_value</value> </Object> </sugestedValues> </baseprefs>';

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/suggested-values/', [
            'str' => $prefs
        ]);

        $json = $this->getJson($response);
        $this->assertTrue($json->success);

        $collection = $collection = \collection::getByBaseId(self::$DI['app'], self::$DI['collection']->get_base_id());
        $this->assertTrue( ! ! strrpos($collection->get_prefs(), 'my_new_value'));
        unset($collection);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::submitSuggestedValues
     */
    public function testPostSuggestedValuebadXml()
    {
        $this->setAdmin(true);

        $prefs = '<?xml version="1.0" encoding="UTF-alues> </baseprefs>';

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/suggested-values/', [
            'str' => $prefs
        ]);

        $json = $this->getJson($response);
        $this->assertFalse($json->success);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::enable
     */
    public function testPostEnableNotJson()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/enable/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::enable
     */
    public function testPostEnableUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/enable/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::enable
     */
    public function testPostEnable()
    {
        $this->setAdmin(true);

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/enable/');

        $json = $this->getJson($response);
        $this->assertTrue($json->success);

        $collection = \collection::getByBaseId(self::$DI['app'], self::$DI['collection']->get_base_id());
        $this->assertTrue($collection->is_active());
        unset($collection);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::disabled
     */
    public function testPostDisabledNotJson()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/disabled/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::disabled
     */
    public function testPostDisabledUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/disabled/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::disabled
     */
    public function testPostDisabled()
    {
        $this->setAdmin(true);

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/disabled/');

        $json = $this->getJson($response);
        $this->assertTrue($json->success);
        $collection = \collection::getByBaseId(self::$DI['app'], self::$DI['collection']->get_base_id());
        $this->assertFalse($collection->is_active());
        unset($collection);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setOrderAdmins
     */
    public function testPostOrderAdminsUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/order/admins/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setOrderAdmins
     */
    public function testPostOrderAdmins()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/order/admins/', [
            'admins' => [self::$DI['user_alt1']->getId()]
        ]);

        $this->checkRedirection(self::$DI['client']->getResponse(), '/admin/collection/' . self::$DI['collection']->get_base_id() . '/?success=1');

        $this->assertTrue(self::$DI['app']->getAclForUser(self::$DI['user_alt1'])->has_right_on_base(self::$DI['collection']->get_base_id(), \ACL::ORDER_MASTER));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setPublicationDisplay
     */
    public function testPostPublicationDisplayNotJson()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/publication/display/', [
            'pub_wm' => 'wm',
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setPublicationDisplay
     */
    public function testPostPublicationDisplayUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/publication/display/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setPublicationDisplay
     */
    public function testPublicationDisplayBadRequestMissingArguments()
    {
        $this->setAdmin(true);

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/publication/display/');
        $this->assertXMLHTTPBadJsonResponse($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setPublicationDisplay
     */
    public function testPublicationDisplay()
    {
        $this->setAdmin(true);

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/publication/display/', [
            'pub_wm' => 'wm',
        ]);

        $json = $this->getJson($response);
        $this->assertTrue($json->success);
        $collection = \collection::getByBaseId(self::$DI['app'], self::$DI['collection']->get_base_id());
        $this->assertNotNull($collection->get_pub_wm());
        unset($collection);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::rename
     */
    public function testPostNameNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        self::$DI['client']->request('POST', '/admin/collection/' . $collection->get_base_id() . '/rename/', [
            'name' => 'test_rename_coll'
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertEquals('/admin/collection/' . $collection->get_base_id() . '/?success=1&reload-tree=1', self::$DI['client']->getResponse()->headers->get('location'));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::labels
     */
    public function testPostLabelsNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        self::$DI['client']->request('POST', '/admin/collection/' . $collection->get_base_id() . '/labels/', [
            'labels' => [
                'en' => 'english label',
                'fr' => 'french label',
                'ru' => 'russian label',
            ]
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertContains('/admin/collection/'.$collection->get_base_id().'/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::rename
     */
    public function testPostNameUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/rename/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::labels
     */
    public function testPostLabelsUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/labels/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::rename
     */
    public function testPostNameBadRequestMissingArguments()
    {
        $this->setAdmin(true);

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/rename/');
        $this->assertXMLHTTPBadJsonResponse($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::labels
     */
    public function testPostLabelsBadRequestMissingArguments()
    {
        $this->setAdmin(true);

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/labels/');
        $this->assertXMLHTTPBadJsonResponse($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::rename
     */
    public function testPostName()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . $collection->get_base_id() . '/rename/', [
            'name' => 'test_rename_coll'
        ]);

        $json = $this->getJson($response);
        $this->assertTrue($json->success);

        // Collection has to be reloaded since it was modified outside of the current process
        $databox = $this->getApplication()->findDataboxById($collection->get_sbas_id());
        $collection = \collection::getByCollectionId($this->getApplication(), $databox, $collection->get_coll_id());

        $this->assertEquals($collection->get_name(), 'test_rename_coll');

        $collection->unmount();
        $collection->delete();
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::labels
     */
    public function testPostLabels()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . $collection->get_base_id() . '/labels/', [
            'labels' => [
                'nl' => 'netherlands label',
                'de' => 'german label',
                'fr' => 'label français',
                'en' => 'label à l\'anglaise',
                'ru' => 'label à la russe',
            ]
        ]);

        $json = $this->getJson($response);
        $this->assertTrue($json->success);

        $databox = $this->getApplication()->findDataboxById($collection->get_sbas_id());
        $collection = \collection::getByCollectionId($this->getApplication(), $databox, $collection->get_coll_id());

        $this->assertEquals($collection->get_label('de'), 'german label');
        $this->assertEquals($collection->get_label('nl'), 'netherlands label');
        $this->assertEquals($collection->get_label('fr'), 'label français');
        $this->assertEquals($collection->get_label('en'), 'label à l\'anglaise');
        $collection->unmount();
        $collection->delete();
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::emptyCollection
     */
    public function testPostEmptyCollectionNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        self::$DI['client']->request('POST', '/admin/collection/' . $collection->get_base_id() . '/empty/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::emptyCollection
     */
    public function testPostEmptyCollectionUnauthorizedException()
    {
        $this->setAdmin(false);

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/empty/');
        $this->assertXMLHTTPBadJsonResponse($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::emptyCollection
     */
    public function testPostEmptyCollection()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../../files/test001.jpg'), $collection);
        \record_adapter::createFromFile($file, self::$DI['app']);

        if ($collection->get_record_amount() === 0) {
            $this->markTestSkipped('No record were added');
        }

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . $collection->get_base_id() . '/empty/');

        $json = $this->getJson($response);
        $this->assertTrue($json->success);
        $this->assertEquals(0, $collection->get_record_amount());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::emptyCollection
     */
    public function testPostEmptyCollectionWithHighRecordAmount()
    {
        $this->markTestSkipped('This tests lasts for 40 sec.');

        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $databox = self::$DI['app']->findDataboxById($collection->get_sbas_id());
        $sql = '
            INSERT INTO record
              (coll_id, record_id, parent_record_id, moddate, credate
                , type, sha256, uuid, originalname, mime)
            VALUES
              (:coll_id, null, :parent_record_id, NOW(), NOW()
              , :type, :sha256, :uuid
              , :originalname, :mime)';

        $stmt = $databox->get_connection()->prepare($sql);
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
            $i ++;
        }

        $stmt->closeCursor();

        if ($collection->get_record_amount() < 500) {
            $this->markTestSkipped('No enough records added');
        }

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . $collection->get_base_id() . '/empty/');

        $json = $this->getJson($response);
        $this->assertTrue($json->success);

        if (count(self::$DI['app']['orm.em']->getRepository('Phraseanet:Task')->findAll()) === 0) {
            $this->fail('Task for empty collection has not been created');
        }
    }

    /**
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setMiniLogo
     */
    public function testSetMiniLogoBadRequest()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/picture/mini-logo/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setStamp
     */
    public function testSetStampBadRequest()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/picture/stamp-logo/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setWatermark
     */
    public function testSetWatermarkBadRequest()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/picture/watermark/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }


    /**
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setMiniLogo
     */
    public function testSetMiniLogo()
    {
        $this->setAdmin(true);

        $target = tempnam(sys_get_temp_dir(), 'p4logo') . '.jpg';
        self::$DI['app']['filesystem']->copy(__DIR__ . '/../../../../../files/p4logo.jpg', $target);
        $files = [
            'newLogo' => new \Symfony\Component\HttpFoundation\File\UploadedFile($target, 'logo.jpg')
        ];
        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/picture/mini-logo/', [], $files);
        $this->checkRedirection(self::$DI['client']->getResponse(), '/admin/collection/' . self::$DI['collection']->get_base_id() . '/?success=1');
        $this->assertEquals(1, count(\collection::getLogo(self::$DI['collection']->get_base_id(), self::$DI['app'])));
    }

    /**
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteLogo
     */
    public function testDeleteMiniLogoNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        self::$DI['client']->request('POST', '/admin/collection/' . $collection->get_base_id() . '/picture/mini-logo/delete/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteLogo
     */
    public function testDeleteMiniLogo()
    {
        if (count(\collection::getLogo(self::$DI['collection']->get_base_id(), self::$DI['app'])) === 0) {
            $this->markTestSkipped('No logo setted');
        }

        $this->setAdmin(true);

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/picture/mini-logo/delete/');
        $json = $this->getJson($response);
        $this->assertTrue($json->success);
    }

    /**
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setWatermark
     */
    public function testSetWm()
    {
        $this->setAdmin(true);

        $target = tempnam(sys_get_temp_dir(), 'p4logo') . '.jpg';
        self::$DI['app']['filesystem']->copy(__DIR__ . '/../../../../../files/p4logo.jpg', $target);
        $files = [
            'newWm' => new \Symfony\Component\HttpFoundation\File\UploadedFile($target, 'logo.jpg')
        ];
        /** @var \collection $collection */
        $collection = self::$DI['collection'];
        /** @var Client $client */
        $client = self::$DI['client'];
        $client->request('POST', '/admin/collection/' . $collection->get_base_id() . '/picture/watermark/', [], $files);
        $this->checkRedirection($client->getResponse(), '/admin/collection/' . $collection->get_base_id() . '/?success=1');
        $this->assertEquals(1, count(\collection::getWatermark($collection->get_base_id())));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteWatermark
     */
    public function testDeleteWmBadNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        self::$DI['client']->request('POST', '/admin/collection/' . $collection->get_base_id() . '/picture/watermark/delete/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteWatermark
     */
    public function testDeleteWm()
    {
        if (count(\collection::getWatermark(self::$DI['collection']->get_base_id())) === 0) {
            $this->markTestSkipped('No watermark setted');
        }
        $this->setAdmin(true);

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/picture/watermark/delete/');

        $json = $this->getJson($response);
        $this->assertTrue($json->success);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setStamp
     */
    public function testSetStamp()
    {
        $this->setAdmin(true);

        $target = tempnam(sys_get_temp_dir(), 'p4logo') . '.jpg';
        self::$DI['app']['filesystem']->copy(__DIR__ . '/../../../../../files/p4logo.jpg', $target);
        $files = [
            'newStamp' => new \Symfony\Component\HttpFoundation\File\UploadedFile($target, 'logo.jpg')
        ];
        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/picture/stamp-logo/', [], $files);
        $this->checkRedirection(self::$DI['client']->getResponse(), '/admin/collection/' . self::$DI['collection']->get_base_id() . '/?success=1');
        $this->assertEquals(1, count(\collection::getStamp(self::$DI['collection']->get_base_id())));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteStamp
     */
    public function testDeleteStampBadNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        self::$DI['client']->request('POST', '/admin/collection/' .$collection->get_base_id() . '/picture/stamp-logo/delete/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteStamp
     */
    public function testDeleteStamp()
    {
        if (count(\collection::getStamp(self::$DI['collection']->get_base_id())) === 0) {
            $this->markTestSkipped('No stamp setted');
        }

        $this->setAdmin(true);

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/picture/stamp-logo/delete/');

        $json = $this->getJson($response);
        $this->assertTrue($json->success);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getCollection
     */
    public function testGetCollectionUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getSuggestedValues
     */
    public function testGetSuggestedValuesUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/suggested-values/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getDetails
     */
    public function testInformationsDetailsUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/informations/details/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::delete
     */
    public function testDeleteCollectionNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        self::$DI['client']->request('POST', '/admin/collection/' . $collection->get_base_id() . '/delete/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::delete
     */
    public function testDeleteCollectionUnauthorized()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/delete/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::delete
     */
    public function testDeleteCollection()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . $collection->get_base_id() . '/delete/');

        $json = $this->getJson($response);
        $this->assertTrue($json->success);
        try {
            \collection::getByBaseId(self::$DI['app'], $collection->get_base_id());
            $this->fail('Collection not deleted');
        } catch (\Exception $e) {

        }
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::delete
     */
    public function testDeleteCollectionNoEmpty()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../../files/test001.jpg'), $collection);
        \record_adapter::createFromFile($file, self::$DI['app']);

        if ($collection->get_record_amount() === 0) {
            $this->markTestSkipped('No record were added');
        }

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . $collection->get_base_id() . '/delete/');

        $json = $this->getJson($response);
        $this->assertFalse($json->success);
        $collection->empty_collection();
    }

     /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::unmount
     */
    public function testPostUnmountCollectionNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        self::$DI['client']->request('POST', '/admin/collection/' . $collection->get_base_id() . '/unmount/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::unmount
     */
    public function testPostUnmountCollectionUnauthorizedException()
    {
        $this->setAdmin(false);

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$DI['collection']->get_base_id() . '/unmount/');
        $this->assertXMLHTTPBadJsonResponse($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::unmount
     */
    public function testPostUnmountCollection()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $response = $this->XMLHTTPRequest('POST', '/admin/collection/' . $collection->get_base_id() . '/unmount/');

        $json = $this->getJson($response);
        $this->assertTrue($json->success);

        try {
            \collection::getByBaseId(self::$DI['app'], $collection->get_base_id());
            $this->fail('Collection not unmounted');
        } catch (\Exception_Databox_CollectionNotFound $e) {

        }

        unset($collection);
    }
}
