<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class AdminCollectionTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;
    public static $createdCollections = array();
    protected static $useExceptionHandler = true;

    public function tearDown()
    {
        self::$application['phraseanet.user'] = self::$DI['user'];
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
        self::$createdCollections = array();
        // /!\ re enable collection
        self::$collection->enable(self::$application['phraseanet.appbox']);
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

    public function checkRedirection($response, $location)
    {
        $this->assertTrue($response->isRedirect());
        $this->assertEquals($location, $response->headers->get('location'));
    }

    public function createOneCollection()
    {
        $collection = \collection::create(self::$application, array_shift(self::$application['phraseanet.appbox']->get_databoxes()), self::$application['phraseanet.appbox'], 'TESTTODELETE');

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

        $this->client->request('GET', '/admin/collection/' . self::$collection->get_base_id() . '/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getSuggestedValues
     */
    public function testGetSuggestedValues()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/admin/collection/' . self::$collection->get_base_id() . '/suggested-values/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getDetails
     */
    public function testInformationsDetails()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $file = new \Alchemy\Phrasea\Border\File(self::$application['mediavorus']->guess(__DIR__ . '/../../../../testfiles/test001.CR2'), $collection);
        \record_adapter::createFromFile($file, self::$application);

        $this->client->request('GET', '/admin/collection/' . $collection->get_base_id() . '/informations/details/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::submitSuggestedValues
     */
    public function testPostSuggestedValuesNotJson()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/suggested-values/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::submitSuggestedValues
     */
    public function testPostSuggestedValueUnauthorized()
    {
        $this->setAdmin(false);

        $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$collection->get_base_id() . '/suggested-values/');
        $this->assertXMLHTTPBadJsonResponse($this->client->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::submitSuggestedValues
     */
    public function testPostSuggestedValue()
    {
        $this->setAdmin(true);

        $prefs = '<?xml version="1.0" encoding="UTF-8"?> <baseprefs> <status>0</status> <sugestedValues> <Object> <value>my_new_value</value> </Object> </sugestedValues> </baseprefs>';

        $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$collection->get_base_id() . '/suggested-values/', array(
            'str' => $prefs
        ));

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);

        $collection = $collection = \collection::get_from_base_id(self::$application, self::$collection->get_base_id());
        $this->assertTrue( ! ! strrpos($collection->get_prefs(), 'my_new_value'));
        unset($collection);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::submitSuggestedValues
     */
    public function testPostSuggestedValuebadXml()
    {
        $this->setAdmin(true);

        $prefs = '<? version="1.0" encoding="UTF-alues> </baseprefs>';

        $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$collection->get_base_id() . '/suggested-values/', array(
            'str' => $prefs
        ));

        $json = $this->getJson($this->client->getResponse());
        $this->assertFalse($json->success);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::enable
     */
    public function testPostEnableNotJson()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/enable/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::enable
     */
    public function testPostEnableUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/enable/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::enable
     */
    public function testPostEnable()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$collection->get_base_id() . '/enable/');

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);

        $collection = \collection::get_from_base_id(self::$application, self::$collection->get_base_id());
        $this->assertTrue($collection->is_active());
        unset($collection);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::disabled
     */
    public function testPostDisabledNotJson()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/disabled/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::disabled
     */
    public function testPostDisabledUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/disabled/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::disabled
     */
    public function testPostDisabled()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$collection->get_base_id() . '/disabled/');

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
        $collection = \collection::get_from_base_id(self::$application, self::$collection->get_base_id());
        $this->assertFalse($collection->is_active());
        unset($collection);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setOrderAdmins
     */
    public function testPostOrderAdminsUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/order/admins/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setOrderAdmins
     */
    public function testPostOrderAdmins()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/order/admins/', array(
            'admins' => array(self::$DI['user_alt1']->get_id())
        ));

        $this->checkRedirection($this->client->getResponse(), '/admin/collection/' . self::$collection->get_base_id() . '/?success=1');

        $this->assertTrue(self::$DI['user_alt1']->ACL()->has_right_on_base(self::$collection->get_base_id(), 'order_master'));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setPublicationDisplay
     */
    public function testPostPublicationDisplayNotJson()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/publication/display/', array(
            'pub_wm' => 'wm',
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setPublicationDisplay
     */
    public function testPostPublicationDisplayUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/publication/display/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setPublicationDisplay
     */
    public function testPublicationDisplayBadRequestMissingArguments()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$collection->get_base_id() . '/publication/display/');
        $this->assertXMLHTTPBadJsonResponse($this->client->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setPublicationDisplay
     */
    public function testPublicationDisplay()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$collection->get_base_id() . '/publication/display/', array(
            'pub_wm' => 'wm',
        ));

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
        $collection = \collection::get_from_base_id(self::$application, self::$collection->get_base_id());
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

        $this->client->request('POST', '/admin/collection/' . $collection->get_base_id() . '/rename/', array(
            'name' => 'test_rename_coll'
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::rename
     */
    public function testPostNameUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/rename/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::rename
     */
    public function testPostNameBadRequestMissingArguments()
    {
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$collection->get_base_id() . '/rename/');
        $this->assertXMLHTTPBadJsonResponse($this->client->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::rename
     */
    public function testPostName()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $this->XMLHTTPRequest('POST', '/admin/collection/' . $collection->get_base_id() . '/rename/', array(
            'name' => 'test_rename_coll'
        ));

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
        $this->assertEquals($collection->get_name(), 'test_rename_coll');
        $collection->unmount_collection(self::$application);
        $collection->delete();
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::emptyCollection
     */
    public function testPostEmptyCollectionNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $this->client->request('POST', '/admin/collection/' . $collection->get_base_id() . '/empty/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::emptyCollection
     */
    public function testPostEmptyCollectionUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$collection->get_base_id() . '/empty/');
        $this->assertXMLHTTPBadJsonResponse($this->client->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::emptyCollection
     */
    public function testPostEmptyCollection()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $file = new \Alchemy\Phrasea\Border\File(self::$application['mediavorus']->guess(__DIR__ . '/../../../../testfiles/test001.CR2'), $collection);
        \record_adapter::createFromFile($file, self::$application);

        if ($collection->get_record_amount() === 0) {
            $this->markTestSkipped('No record were added');
        }

        $this->XMLHTTPRequest('POST', '/admin/collection/' . $collection->get_base_id() . '/empty/');

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
        $this->assertEquals(0, $collection->get_record_amount());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::emptyCollection
     */
    public function testPostEmptyCollectionWithHighRecordAmount()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $databox = self::$application['phraseanet.appbox']->get_databox($collection->get_sbas_id());
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
            $stmt->execute(array(
                ':coll_id'          => $collection->get_coll_id(),
                ':parent_record_id' => 0,
                ':type'             => 'unknown',
                ':sha256'           => null,
                ':uuid'             => \uuid::generate_v4(),
                ':originalname'     => null,
                ':mime'             => null,
            ));
            $i ++;
        }

        $stmt->closeCursor();

        if ($collection->get_record_amount() < 500) {
            $this->markTestSkipped('No enough records added');
        }

        $this->XMLHTTPRequest('POST', '/admin/collection/' . $collection->get_base_id() . '/empty/');

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

        if ( ! $found) {
            $this->fail('Task for empty collection has not been created');
        }
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setMiniLogo
     */
    public function testSetMiniLogoBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/picture/mini-logo/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setStamp
     */
    public function testSetStampBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/picture/stamp-logo/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setWatermark
     */
    public function testSetWatermarkBadRequest()
    {
        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/picture/watermark/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setBanner
     */
    public function testSetBannerBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/picture/banner/');
    }

    /**
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setMiniLogo
     */
    public function testSetMiniLogo()
    {
        $this->setAdmin(true);

        $target = tempnam(sys_get_temp_dir(), 'p4logo') . '.jpg';
        self::$application['filesystem']->copy(__DIR__ . '/../../../../testfiles/p4logo.jpg', $target);
        $files = array(
            'newLogo' => new \Symfony\Component\HttpFoundation\File\UploadedFile($target, 'logo.jpg')
        );
        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/picture/mini-logo/', array(), $files);
        $this->checkRedirection($this->client->getResponse(), '/admin/collection/' . self::$collection->get_base_id() . '/?success=1');
        $this->assertEquals(1, count(\collection::getLogo(self::$collection->get_base_id(), self::$application)));
    }

    /**
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteLogo
     */
    public function testDeleteMiniLogoNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $this->client->request('POST', '/admin/collection/' . $collection->get_base_id() . '/picture/mini-logo/delete/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteLogo
     */
    public function testDeleteMiniLogo()
    {
        if (count(\collection::getLogo(self::$collection->get_base_id(), self::$application)) === 0) {
            $this->markTestSkipped('No logo setted');
        }

        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$collection->get_base_id() . '/picture/mini-logo/delete/');
        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
    }

    /**
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setWatermark
     */
    public function testSetWm()
    {
        $this->setAdmin(true);

        $target = tempnam(sys_get_temp_dir(), 'p4logo') . '.jpg';
        self::$application['filesystem']->copy(__DIR__ . '/../../../../testfiles/p4logo.jpg', $target);
        $files = array(
            'newWm' => new \Symfony\Component\HttpFoundation\File\UploadedFile($target, 'logo.jpg')
        );
        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/picture/watermark/', array(), $files);
        $this->checkRedirection($this->client->getResponse(), '/admin/collection/' . self::$collection->get_base_id() . '/?success=1');
        $this->assertEquals(1, count(\collection::getWatermark(self::$collection->get_base_id())));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteWatermark
     */
    public function testDeleteWmBadNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $this->client->request('POST', '/admin/collection/' . $collection->get_base_id() . '/picture/watermark/delete/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteWatermark
     */
    public function testDeleteWm()
    {
        if (count(\collection::getWatermark(self::$collection->get_base_id())) === 0) {
            $this->markTestSkipped('No watermark setted');
        }
        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$collection->get_base_id() . '/picture/watermark/delete/');

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setStamp
     */
    public function testSetStamp()
    {
        $this->setAdmin(true);

        $target = tempnam(sys_get_temp_dir(), 'p4logo') . '.jpg';
        self::$application['filesystem']->copy(__DIR__ . '/../../../../testfiles/p4logo.jpg', $target);
        $files = array(
            'newStamp' => new \Symfony\Component\HttpFoundation\File\UploadedFile($target, 'logo.jpg')
        );
        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/picture/stamp-logo/', array(), $files);
        $this->checkRedirection($this->client->getResponse(), '/admin/collection/' . self::$collection->get_base_id() . '/?success=1');
        $this->assertEquals(1, count(\collection::getStamp(self::$collection->get_base_id())));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteStamp
     */
    public function testDeleteStampBadNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $this->client->request('POST', '/admin/collection/' .$collection->get_base_id() . '/picture/stamp-logo/delete/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteStamp
     */
    public function testDeleteStamp()
    {
        if (count(\collection::getStamp(self::$collection->get_base_id())) === 0) {
            $this->markTestSkipped('No stamp setted');
        }

        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$collection->get_base_id() . '/picture/stamp-logo/delete/');

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setBanner
     */
    public function testSetBanner()
    {
        $this->setAdmin(true);

        $target = tempnam(sys_get_temp_dir(), 'p4logo') . '.jpg';
        self::$application['filesystem']->copy(__DIR__ . '/../../../../testfiles/p4logo.jpg', $target);
        $files = array(
            'newBanner' => new \Symfony\Component\HttpFoundation\File\UploadedFile($target, 'logo.jpg')
        );
        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/picture/banner/', array(), $files);
        $this->checkRedirection($this->client->getResponse(), '/admin/collection/' . self::$collection->get_base_id() . '/?success=1');
        $this->assertEquals(1, count(\collection::getPresentation(self::$collection->get_base_id())));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteBanner
     */
    public function testDeleteBannerNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $this->client->request('POST', '/admin/collection/' . $collection->get_base_id() . '/picture/banner/delete/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteBanner
     */
    public function testDeleteBanner()
    {
        if (count(\collection::getPresentation(self::$collection->get_base_id())) === 0) {
            $this->markTestSkipped('No Banner setted');
        }

        $this->setAdmin(true);

        $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$collection->get_base_id() . '/picture/banner/delete/');

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getCollection
     */
    public function testGetCollectionUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/admin/collection/' . self::$collection->get_base_id() . '/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getSuggestedValues
     */
    public function testGetSuggestedValuesUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/admin/collection/' . self::$collection->get_base_id() . '/suggested-values/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getDetails
     */
    public function testInformationsDetailsUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/admin/collection/' . self::$collection->get_base_id() . '/informations/details/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::delete
     */
    public function testDeleteCollectionNotJson()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $this->client->request('POST', '/admin/collection/' . $collection->get_base_id() . '/delete/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::delete
     */
    public function testDeleteCollectionUnauthorized()
    {
        $this->setAdmin(false);

        $this->client->request('POST', '/admin/collection/' . self::$collection->get_base_id() . '/delete/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::delete
     */
    public function testDeleteCollection()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $this->XMLHTTPRequest('POST', '/admin/collection/' . $collection->get_base_id() . '/delete/');

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
        try {
            \collection::get_from_base_id(self::$application, $collection->get_base_id());
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

        $file = new \Alchemy\Phrasea\Border\File(self::$application['mediavorus']->guess(__DIR__ . '/../../../../testfiles/test001.CR2'), $collection);
        \record_adapter::createFromFile($file, self::$application);

        if ($collection->get_record_amount() === 0) {
            $this->markTestSkipped('No record were added');
        }

        $this->XMLHTTPRequest('POST', '/admin/collection/' . $collection->get_base_id() . '/delete/');

        $json = $this->getJson($this->client->getResponse());
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

        $this->client->request('POST', '/admin/collection/' . $collection->get_base_id() . '/unmount/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::unmount
     */
    public function testPostUnmountCollectionUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->XMLHTTPRequest('POST', '/admin/collection/' . self::$collection->get_base_id() . '/unmount/');
        $this->assertXMLHTTPBadJsonResponse($this->client->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::unmount
     */
    public function testPostUnmountCollection()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $this->XMLHTTPRequest('POST', '/admin/collection/' . $collection->get_base_id() . '/unmount/');

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);

        try {
            \collection::get_from_base_id(self::$application, $collection->get_base_id());
            $this->fail('Collection not unmounted');
        } catch (\Exception_Databox_CollectionNotFound $e) {

        }

        unset($collection);
    }
}
