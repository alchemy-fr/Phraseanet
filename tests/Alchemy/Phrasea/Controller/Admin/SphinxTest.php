<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class SphinxTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Sphinx::getConfiguration
     * @covers Alchemy\Phrasea\Controller\Admin\Sphinx::connect
     */
    public function testGetConfiguration()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/admin/sphinx/configuration/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Sphinx::getConfiguration
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testGetConfigurationUnauthorizedException()
    {
        $this->setAdmin(false);

        $this->client->request('GET', '/admin/sphinx/configuration/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Sphinx::submitConfiguration
     */
    public function testPostConfiguration()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/sphinx/configuration/');
        $this->assertTrue($this->client->getResponse()->isRedirect());
    }
}
