<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class SetupTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Admin.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Setup::getGlobals
     */
    public function testGetSlash()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/setup/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Setup::getGlobals
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testGetSlashUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/setup/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Setup::postGlobals
     */
    public function testPostGlobals()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/setup/');
        $this->assertTrue($this->client->getResponse()->isRedirect());
    }
}
