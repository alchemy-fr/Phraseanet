<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Symfony\Component\HttpKernel\Client;

class SetupTest extends \PhraseanetAuthenticatedWebTestCase
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
        $registry = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\PropertyAccess')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($prop, $default = null) {
                return $default;
            }));

        $registry->expects($this->once())
            ->method('set')
            ->with('registry',$this->isType('array'));

        self::$DI['app']['conf'] = $registry;
        self::$DI['client']->request('POST', '/admin/setup/', ['_token'   => 'token']);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }
}
