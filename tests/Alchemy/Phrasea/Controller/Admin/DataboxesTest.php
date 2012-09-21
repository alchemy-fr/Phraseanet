<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class DataboxesTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Databases::getDatabases
     * @covers Alchemy\Phrasea\Controller\Admin\Databases::connect
     * @dataProvider msgProvider
     */
    public function testGetSlash($type, $errorMsgId)
    {
        $this->client->request('GET', '/admin/databoxes/', array(
            $type => $errorMsgId
        ));

        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function msgProvider()
    {
        return array(
            array('error', 'already-started'),
            array('error', 'scheduler-started'),
            array('error', 'unknow'),
            array('error', 'bad-email'),
            array('error', 'special-chars'),
            array('error', 'base-failed'),
            array('error', 'database-failed'),
            array('error', 'no-empty'),
            array('success', 'restart'),
            array('success', 'mount-ok'),
            array('success', 'database-ok'),
        );
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Databases::getDatabases
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testGetSlashUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/admin/databoxes/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Databases::databasesUpgrade
     */
    public function testPostUpgrade()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/databoxes/upgrade/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }


    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::databaseMount
     */
    public function testMountBase()
    {
        $this->setAdmin(true);

        $base = $this->createDatabox();
        $base->unmount_databox(self::$application['phraseanet.appbox']);

        $this->client->request('POST', '/admin/databoxes/mount/', array(
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
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::createDatabase
     */
    public function testCreateDatabaseEmpty()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/databoxes/', array(
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

        $this->client->request('POST', '/admin/databoxes/', array(
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

        $this->client->request('POST', '/admin/databoxes/', array(
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
}
