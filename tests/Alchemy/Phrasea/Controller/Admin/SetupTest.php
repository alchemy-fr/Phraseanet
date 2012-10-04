<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class SetupTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Setup::getGlobals
     */
    public function testGetSlash()
    {
        self::$DI['client']->request('GET', '/admin/setup/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Setup::getGlobals
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testGetSlashUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/setup/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Setup::postGlobals
     */
    public function testPostGlobals()
    {
//        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/setup/');
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }
}
