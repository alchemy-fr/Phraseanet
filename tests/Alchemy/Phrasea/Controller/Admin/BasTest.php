<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class BasTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;
    protected $StubbedACL;

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Admin.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
        $this->StubbedACL = $this->getMockBuilder('\ACL')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function setAdmin($bool)
    {
        $stubAuthenticatedUser = $this->getMockBuilder('\User_Adapter')
            ->setMethods(array('is_admin','ACL'))
            ->disableOriginalConstructor()
            ->getMock();

        $stubAuthenticatedUser->expects($this->any())
            ->method('is_admin')
            ->will($this->returnValue($bool));

        $this->StubbedACL->expects($this->any())
            ->method('has_right_on_base')
            ->will($this->returnValue($bool));

        $stubAuthenticatedUser->expects($this->any())
            ->method('ACL')
            ->will($this->returnValue($this->StubbedACL));

        $stubCore = $this->getMockBuilder('\Alchemy\Phrasea\Core')
            ->setMethods(array('getAuthenticatedUser'))
            ->getMock();

        $stubCore->expects($this->any())
            ->method('getAuthenticatedUser')
            ->will($this->returnValue($stubAuthenticatedUser));

        $this->app['phraseanet.core'] = $stubCore;
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
//        $this->assertRegexp('/' . str_replace("/", "\/", $location) . '/', $response->headers->get('location'));
        $this->assertEquals($location, $response->headers->get('location'));
    }

    public function createOneCollection()
    {
        $collection = \collection::create(array_shift($this->app['phraseanet.appbox']->get_databoxes()), $this->app['phraseanet.appbox'], 'TESTTODELETE', $this->app['phraseanet.core']->getAuthenticatedUser());

        $this->app['phraseanet.core']->getAuthenticatedUser()->ACL()->update_rights_to_base($collection->get_base_id(), array('canadmin' => 1));

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

        $this->client->request('GET', '/bas/' . self::$collection->get_base_id() . '/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getSuggestedValues
     */
    public function testGetSuggestedValues()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/bas/' . self::$collection->get_base_id() . '/suggested-values/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getDetails
     */
    public function testInformationsDetails()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $file = new \Alchemy\Phrasea\Border\File($this->app['phraseanet.core']['mediavorus']->guess(new \SplFileInfo(__DIR__ . '/../../../../testfiles/test001.CR2')), $collection);
        \record_adapter::createFromFile($file);

        $this->client->request('GET', '/bas/' . $collection->get_base_id() . '/informations/details/');
        $this->assertTrue($this->client->getResponse()->isOk());

        $collection->delete();
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::submitSuggestedValues
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testPostSuggestedValuesBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/suggested-values/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::submitSuggestedValues
     */
    public function testPostSuggestedValueUnauthorized()
    {
        $this->setAdmin(false);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/suggested-values/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::submitSuggestedValues
     */
    public function testPostSuggestedValue()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/suggested-values/', array(
            'str' => '<?xml version="1.0" encoding="UTF-8"?> <baseprefs> <status>0</status> <sugestedValues> <Object> <value>aa</value> </Object> </sugestedValues> </baseprefs>'
            ), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::enable
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testPostEnableBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/enable/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::enable
     */
    public function testPostEnableUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/enable/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::enable
     */
    public function testPostEnable()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/enable/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::disabled
     */
    public function testPostDisabledBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/disabled/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::disabled
     */
    public function testPostDisabledUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/disabled/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::disabled
     */
    public function testPostDisabled()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/disabled/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setOrderAdmins
     */
    public function testPostOrderAdminsUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/order/admins/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setOrderAdmins
     */
    public function testPostOrderAdmins()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/order/admins/', array(
            'admins' => array(self::$user_alt1->get_id())
        ));

        $this->checkRedirection($this->client->getResponse(), '/admin/bas/' . self::$collection->get_base_id() . '/?operation=ok');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setPublicationDisplay
     */
    public function testPostPublicationDisplayBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/publication/display/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setPublicationDisplay
     */
    public function testPostPublicationDisplayUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/publication/display/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setPublicationDisplay
     */
    public function testPublicationDisplayBadRequestMissingArguments()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/publication/display/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setPublicationDisplay
     */
    public function testPublicationDisplay()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/publication/display/', array(
            'pub_wm' => 'wm',
            ), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::rename
     */
    public function testPostNameBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/rename/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::rename
     */
    public function testPostNameUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/rename/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::rename
     */
    public function testPostNameBadRequestMissingArguments()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/rename/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::rename
     */
    public function testPostName()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/rename/', array(
            'name' => 'test2',
            ), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
        $this->assertEquals(self::$collection->get_name(), 'test2');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::emptyCollection
     */
    public function testPostEmptyCollectionBadRequestFormat()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/empty/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::emptyCollection
     */
    public function testPostEmptyCollectionUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/empty/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::emptyCollection
     */
    public function testPostEmptyCollection()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $file = new \Alchemy\Phrasea\Border\File($this->app['phraseanet.core']['mediavorus']->guess(new \SplFileInfo(__DIR__ . '/../../../../testfiles/test001.CR2')), $collection);
        \record_adapter::createFromFile($file);

        if ($collection->get_record_amount() === 0) {
            $this->markTestSkipped('No record were added');
        }

        $this->client->request('POST', '/bas/' . $collection->get_base_id() . '/empty/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
        $this->assertEquals(0, $collection->get_record_amount());
        $collection->delete();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::unmount
     */
    public function testPostUnmountCollectionBadRequestFormat()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/unmount/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::unmount
     */
    public function testPostUnmountCollectionUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/unmount/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::unmount
     */
    public function testPostUnmountCollection()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $this->client->request('POST', '/bas/' . $collection->get_base_id() . '/unmount/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);

        $collection->delete();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setMiniLogo
     */
    public function testSetMiniLogoBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/picture/mini-logo/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setStamp
     */
    public function testSetStampBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/picture/stamp-logo/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setWatermark
     */
    public function testSetWatermarkBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/picture/watermark/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setBanner
     */
    public function testSetBannerBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/picture/banner/');
    }

    /**
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setMiniLogo
     */
    public function testSetMiniLogo()
    {
        $this->setAdmin(true);

        $target = tempnam(sys_get_temp_dir(), 'p4logo') . '.jpg';
        $this->app['phraseanet.core']['file-system']->copy(__DIR__ . '/../../../../testfiles/p4logo.jpg', $target);
        $files = array(
            'newLogo' => new \Symfony\Component\HttpFoundation\File\UploadedFile($target, 'logo.jpg')
        );
        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/picture/mini-logo/', array(), $files);
        $this->checkRedirection($this->client->getResponse(), '/admin/bas/' . self::$collection->get_base_id() . '/?operation=ok');
        $this->assertEquals(1, count(\collection::getLogo(self::$collection->get_base_id())));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteLogo
     */
    public function testDeleteMiniLogoBadRequestFormat()
    {
        $this->setAdmin(true);

        $this->client->request('DELETE', '/bas/' . self::$collection->get_base_id() . '/picture/mini-logo/');
    }

    /**
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteLogo
     */
    public function testDeleteMiniLogo()
    {
        if (count(\collection::getLogo(self::$collection->get_base_id())) === 0) {
            $this->markTestSkipped('No logo setted');
        }

        $this->setAdmin(true);

        $this->client->request('DELETE', '/bas/' . self::$collection->get_base_id() . '/picture/mini-logo/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));
        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
        /**todo check why file is not deleted */
//        $this->assertEquals(0, count(\collection::getLogo(self::$collection->get_base_id())));
    }

    /**
     *  @covers Alchemy\Phrasea\Controller\Admin\Bas::setWatermark
     */
    public function testSetWm()
    {
        $this->setAdmin(true);

        $target = tempnam(sys_get_temp_dir(), 'p4logo') . '.jpg';
        $this->app['phraseanet.core']['file-system']->copy(__DIR__ . '/../../../../testfiles/p4logo.jpg', $target);
        $files = array(
            'newWm' => new \Symfony\Component\HttpFoundation\File\UploadedFile($target, 'logo.jpg')
        );
        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/picture/watermark/', array(), $files);
        $this->checkRedirection($this->client->getResponse(), '/admin/bas/' . self::$collection->get_base_id() . '/?operation=ok');
        $this->assertEquals(1, count(\collection::getWatermark(self::$collection->get_base_id())));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteWatermark
     */
    public function testDeleteWmBadRequestFormat()
    {
        $this->setAdmin(true);

        $this->client->request('DELETE', '/bas/' . self::$collection->get_base_id() . '/picture/watermark/');
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

        $this->client->request('DELETE', '/bas/' . self::$collection->get_base_id() . '/picture/watermark/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));
        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
         /**todo check why file is not deleted */
//        $this->assertEquals(0, count(\collection::getWatermark(self::$collection->get_base_id())));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setStamp
     */
    public function testSetStamp()
    {
        $this->setAdmin(true);

        $target = tempnam(sys_get_temp_dir(), 'p4logo') . '.jpg';
        $this->app['phraseanet.core']['file-system']->copy(__DIR__ . '/../../../../testfiles/p4logo.jpg', $target);
        $files = array(
            'newStamp' => new \Symfony\Component\HttpFoundation\File\UploadedFile($target, 'logo.jpg')
        );
        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/picture/stamp-logo/', array(), $files);
        $this->checkRedirection($this->client->getResponse(), '/admin/bas/' . self::$collection->get_base_id() . '/?operation=ok');
        $this->assertEquals(1, count(\collection::getStamp(self::$collection->get_base_id())));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteStamp
     */
    public function testDeleteStampBadRequestFormat()
    {
        $this->setAdmin(true);

        $this->client->request('DELETE', '/bas/' . self::$collection->get_base_id() . '/picture/stamp-logo/');
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

        $this->client->request('DELETE', '/bas/' . self::$collection->get_base_id() . '/picture/stamp-logo/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
         /**todo check why file is not deleted */
//        $this->assertEquals(0, count(\collection::getStamp(self::$collection->get_base_id())));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::setBanner
     */
    public function testSetBanner()
    {
        $this->setAdmin(true);

        $target = tempnam(sys_get_temp_dir(), 'p4logo') . '.jpg';
        $this->app['phraseanet.core']['file-system']->copy(__DIR__ . '/../../../../testfiles/p4logo.jpg', $target);
        $files = array(
            'newBanner' => new \Symfony\Component\HttpFoundation\File\UploadedFile($target, 'logo.jpg')
        );
        $this->client->request('POST', '/bas/' . self::$collection->get_base_id() . '/picture/banner/', array(), $files);
        $this->checkRedirection($this->client->getResponse(), '/admin/bas/' . self::$collection->get_base_id() . '/?operation=ok');
        $this->assertEquals(1, count(\collection::getPresentation(self::$collection->get_base_id())));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::deleteBanner
     */
    public function testDeleteBannerBadRequestFormat()
    {
        $this->setAdmin(true);

        $this->client->request('DELETE', '/bas/' . self::$collection->get_base_id() . '/picture/banner/');
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

        $this->client->request('DELETE', '/bas/' . self::$collection->get_base_id() . '/picture/banner/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
         /**todo check why file is not deleted */
//        $this->assertEquals(0, count(\collection::getPresentation(self::$collection->get_base_id())));
    }


    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getCollection
     */
    public function testGetCollectionUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/bas/' . self::$collection->get_base_id() . '/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getSuggestedValues
     */
    public function testGetSuggestedValuesUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/bas/' . self::$collection->get_base_id() . '/suggested-values/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::getDetails
     */
    public function testInformationsDetailsUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/bas/' . self::$collection->get_base_id() . '/informations/details/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::delete
     */
    public function testDeleteCollectionBadRequestFormat()
    {
        $this->setAdmin(false);

        $this->client->request('DELETE', '/bas/' . self::$collection->get_base_id() . '/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::delete
     */
    public function testDeleteCollectionUnauthorized()
    {
        $this->setAdmin(false);

        $this->client->request('DELETE', '/bas/' . self::$collection->get_base_id() . '/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Bas::delete
     */
    public function testDeleteCollection()
    {
        $this->setAdmin(true);

        $collection = $this->createOneCollection();

        $this->client->request('DELETE', '/bas/' . $collection->get_base_id() . '/',array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));

        $json = $this->getJson($this->client->getResponse());
        $this->assertTrue($json->success);
        try {
            \collection::get_from_base_id($collection->get_base_id());
            $this->fail('Collection not deleted');
        } catch (\Exception $e) {

        }
    }
}
