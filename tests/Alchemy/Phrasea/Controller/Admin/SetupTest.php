<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class SetupTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Setup::getGlobals
     */
    public function testGetSlash()
    {
//        $this->setAdmin(true);exit;
        $this->client->request('GET', '/admin/setup/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Setup::getGlobals
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testGetSlashUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/admin/setup/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Setup::postGlobals
     */
    public function testPostGlobals()
    {
//        $this->setAdmin(true);

        $this->client->request('POST', '/admin/setup/');
        $this->assertTrue($this->client->getResponse()->isRedirect());
    }
}
