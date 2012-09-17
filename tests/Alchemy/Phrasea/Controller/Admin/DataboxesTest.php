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
}
