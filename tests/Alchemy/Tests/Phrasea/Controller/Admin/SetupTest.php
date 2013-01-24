<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Symfony\Component\HttpKernel\Client;

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
     */
    public function testGetSlashUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/setup/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Setup::postGlobals
     */
    public function testPostGlobals()
    {
        $registry = $this->getMockBuilder('\registry')
                    ->disableOriginalConstructor()
                    ->getMock();

        $registry->expects($this->atLeastOnce())
            ->method('set')
            ->with(
                $this->stringStartsWith('GV_'),
                $this->anything(),
                $this->isType('string'));

        self::$DI['app']['phraseanet.registry'] = $registry;
        self::$DI['client'] = new Client(self::$DI['app']);
        self::$DI['client']->request('POST', '/admin/setup/');
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }
}
