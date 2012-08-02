<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class DashboardTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

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
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testRouteDashboardUnauthorized()
    {
        $stubAuthenticatedUser = $this->getMockBuilder('\User_Adapter')
            ->setMethods(array('is_admin'))
            ->disableOriginalConstructor()
            ->getMock();

        $stubAuthenticatedUser->expects($this->any())
            ->method('is_admin')
            ->will($this->returnValue(false));

        $stubCore = $this->getMockBuilder('\Alchemy\Phrasea\Core')
            ->setMethods(array('getAuthenticatedUser'))
            ->getMock();

        $stubCore->expects($this->any())
            ->method('getAuthenticatedUser')
            ->will($this->returnValue($stubAuthenticatedUser));

        $this->app['phraseanet.core'] = $stubCore;

        $this->client->request('GET', '/dashboard/');
    }

    public function testRouteDashboard()
    {
        $stubAuthenticatedUser = $this->getMockBuilder('\User_Adapter')
            ->setMethods(array('is_admin'))
            ->disableOriginalConstructor()
            ->getMock();

        $stubAuthenticatedUser->expects($this->any())
            ->method('is_admin')
            ->will($this->returnValue(true));

        $stubCore = $this->getMockBuilder('\Alchemy\Phrasea\Core')
            ->setMethods(array('getAuthenticatedUser'))
            ->getMock();

        $stubCore->expects($this->any())
            ->method('getAuthenticatedUser')
            ->will($this->returnValue($stubAuthenticatedUser));

        $this->app['phraseanet.core'] = $stubCore;

        $this->client->request('GET', '/dashboard/');

        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testFlushCache()
    {
        $stubAuthenticatedUser = $this->getMockBuilder('\User_Adapter')
            ->setMethods(array('is_admin'))
            ->disableOriginalConstructor()
            ->getMock();

        $stubAuthenticatedUser->expects($this->any())
            ->method('is_admin')
            ->will($this->returnValue(true));

        $stubCore = $this->getMockBuilder('\Alchemy\Phrasea\Core')
            ->setMethods(array('getAuthenticatedUser'))
            ->getMock();

        $stubCore->expects($this->any())
            ->method('getAuthenticatedUser')
            ->will($this->returnValue($stubAuthenticatedUser));

        $this->app['phraseanet.core'] = $stubCore;

        $this->client->request('POST', '/dashboard/flush-cache/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertRegexp('/flush_cache=ok/', $this->client->getResponse()->headers->get('location'));
    }

    public function testSendMailTest()
    {
        $stubAuthenticatedUser = $this->getMockBuilder('\User_Adapter')
            ->setMethods(array('is_admin'))
            ->disableOriginalConstructor()
            ->getMock();

        $stubAuthenticatedUser->expects($this->any())
            ->method('is_admin')
            ->will($this->returnValue(true));

        $stubCore = $this->getMockBuilder('\Alchemy\Phrasea\Core')
            ->setMethods(array('getAuthenticatedUser'))
            ->getMock();

        $stubCore->expects($this->any())
            ->method('getAuthenticatedUser')
            ->will($this->returnValue($stubAuthenticatedUser));

        $this->app['phraseanet.core'] = $stubCore;

        $this->client->request('POST', '/dashboard/send-mail-test/', array(
            'email' => self::$user->get_email()
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertRegexp('/email=/', $this->client->getResponse()->headers->get('location'));
    }

    public function testResetAdminRights()
    {
        $stubAuthenticatedUser = $this->getMockBuilder('\User_Adapter')
            ->setMethods(array('is_admin'))
            ->disableOriginalConstructor()
            ->getMock();

        $stubAuthenticatedUser->expects($this->any())
            ->method('is_admin')
            ->will($this->returnValue(true));

        $stubCore = $this->getMockBuilder('\Alchemy\Phrasea\Core')
            ->setMethods(array('getAuthenticatedUser'))
            ->getMock();

        $stubCore->expects($this->any())
            ->method('getAuthenticatedUser')
            ->will($this->returnValue($stubAuthenticatedUser));

        $this->app['phraseanet.core'] = $stubCore;

        $this->client->request('POST', '/dashboard/reset-admin-rights/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    public function testAddAdmins()
    {
        $stubAuthenticatedUser = $this->getMockBuilder('\User_Adapter')
            ->setMethods(array('is_admin'))
            ->disableOriginalConstructor()
            ->getMock();

        $stubAuthenticatedUser->expects($this->any())
            ->method('is_admin')
            ->will($this->returnValue(true));

        $stubCore = $this->getMockBuilder('\Alchemy\Phrasea\Core')
            ->setMethods(array('getAuthenticatedUser'))
            ->getMock();

        $stubCore->expects($this->any())
            ->method('getAuthenticatedUser')
            ->will($this->returnValue($stubAuthenticatedUser));

        $this->app['phraseanet.core'] = $stubCore;

        $this->client->request('POST', '/dashboard/new/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }
}
