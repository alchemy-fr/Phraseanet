<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Symfony\Component\HttpKernel\Client;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
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
        $database =  self::$DI['app']['conf']->get(['main', 'database']);
        $registry = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\PropertyAccess')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($prop, $default = null) use ($database) {
                if ($prop === ['main', 'database']) {
                    return $database;
                }
                return $default;
            }));

        $registry->expects($this->once())
            ->method('set')
            ->with('registry',$this->isType('array'));

        self::$DI['app']['conf'] = $registry;
        /** @var Client $client */
        $client = self::$DI['client'];
        $client->request('POST', '/admin/setup/', ['_token'   => 'token']);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }
}
