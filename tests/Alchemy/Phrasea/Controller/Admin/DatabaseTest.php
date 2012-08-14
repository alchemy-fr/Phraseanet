<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class DatabaseTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
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

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabase
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::connect
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::call
     */
    public function testGetDatabox()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/database/' . self::$collection->get_sbas_id() . '/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getReorder
     */
    public function testGetCollectionOrder()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/database/' . self::$collection->get_sbas_id() . '/collections/order/');
        $this->assertTrue($this->client->getResponse()->isOk());
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

        $this->client->request('GET', '/database/' . self::$collection->get_sbas_id() . '/cgus/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabaseCGU
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testGetCGU()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/database/' . self::$collection->get_sbas_id() . '/cgus/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::progressBarInfos
     */
    public function testGetInformationDocumentBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/database/' . self::$collection->get_sbas_id() . '/informations/documents/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::progressBarInfos
     */
    public function testGetInformationDocument()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/database/' . self::$collection->get_sbas_id() . '/informations/documents/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));

        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDetails
     */
    public function testGetInformationDetails()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/database/' . self::$collection->get_sbas_id() . '/informations/details/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getNewCollection
     */
    public function testGetNewCollection()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/database/' . self::$collection->get_sbas_id() . '/collection/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabase
     */
    public function testGetDataboxUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/database/' . self::$collection->get_sbas_id() . '/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getReorder
     */
    public function testGetCollectionOrderUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/database/' . self::$collection->get_sbas_id() . '/collections/order/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDatabaseCGU
     */
    public function testGetCGUUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/database/' . self::$collection->get_sbas_id() . '/cgus/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getDetails
     *
     */
    public function testGetInformationDocumentUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/database/' . self::$collection->get_sbas_id() . '/informations/documents/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::progressBarInfos
     */
    public function testGetInformationDetailsUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/database/' . self::$collection->get_sbas_id() . '/informations/details/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::getNewCollection
     */
    public function testGetNewCollectionUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/database/' . self::$collection->get_sbas_id() . '/collection/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::reindex
     */
    public function testPostReindexBadRequestFormat()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/database/' . self::$collection->get_sbas_id() . '/reindex/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::reindex
     */
    public function testPostReindex()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/database/' . self::$collection->get_sbas_id() . '/reindex/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('sbas_id', $content, $response->getContent());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::setIndexable
     */
    public function testPostIndexableBadRequestFormat()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/database/' . self::$collection->get_sbas_id() . '/reindex/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::setIndexable
     */
    public function testPostIndexable()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/database/' . self::$collection->get_sbas_id() . '/indexable/', array(
            'indexable' => 1
        ), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('sbas_id', $content, $response->getContent());

        $this->assertTrue( ! ! $this->app['phraseanet.appbox']->is_databox_indexable(new \databox(self::$collection->get_sbas_id())));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::clearLogs
     */
    public function testPostClearLogBadRequestFormat()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/database/' . self::$collection->get_sbas_id() . '/clear-logs/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::clearLogs
     */
    public function testPostClearLogs()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/database/' . self::$collection->get_sbas_id() . '/clear-logs/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('sbas_id', $content, $response->getContent());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::changeViewName
     */
    public function testChangeViewBadRequestFormat()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/database/' . self::$collection->get_sbas_id() . '/view-name/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::changeViewName
     */
    public function testPostViewNameBadRequestArguments()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/database/' . self::$collection->get_sbas_id() . '/view-name/', array(), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));
    }
    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::changeViewName
     */
    public function testPostViewName()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/database/' . self::$collection->get_sbas_id() . '/view-name/', array(
            'viewname' => 'salut'
        ), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('sbas_id', $content, $response->getContent());

        $databox = new \databox(self::$collection->get_sbas_id());
        $this->assertEquals('salut', $databox->get_viewname());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::createDatabase
     */
    public function testCreateDatabaseEmpty()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/database/', array(
            'new_dbname' => ''
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/admin/databases/?error=no-empty', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::createDatabase
     */
    public function testCreateDatabaseSpecialChar()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/database/', array(
            'new_dbname' => 'ééààèè'
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/admin/databases/?error=special-chars', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::createDatabase
     */
    public function testCreateDatabase()
    {
//        $this->setAdmin(true);
//
//        $dbName = 'unit_test_db';
//
//        $conn = $this->app['phraseanet.appbox']->get_connection();
//
//        try {
//            $stmt = $conn->prepare( 'CREATE DATABASE `' . $dbName . '`
//              CHARACTER SET utf8 COLLATE utf8_unicode_ci');
//            $stmt->execute();
//            $stmt->closeCursor();
//        } catch (\Exception $e) {
//            $this->markTestSkipped('Could not create Databox');
//        }
//
//        $this->client->request('POST', '/database/', array(
//            'new_dbname' => $dbName,
//            'new_data_template' => 'fr-simple',
//        ));
//
//        $response = $this->client->getResponse();
//        $this->assertTrue($response->isRedirect());
//        $this->assertRegexp('/\/admin\/databases\/?success=base-ok/', $response->headers->get('location'));
//
//        try {
//            $stmt = $conn->prepare( 'DROP DATABASE ' . $dbName);
//            $stmt->execute();
//            $stmt->closeCursor();
//        } catch (\Exception $e) {
//
//        }
//
//        unset($conn);
    }
}
