<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class AdminDashboardTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Dashboard::slash
     * @covers \Alchemy\Phrasea\Controller\Admin\Dashboard::connect
     * @covers \Alchemy\Phrasea\Controller\Admin\Dashboard::call
     */
    public function testRouteDashboardUnauthorized()
    {
        $this->setAdmin(false);
        $this->client->request('GET', '/admin/dashboard/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Dashboard::slash
     */
    public function testRouteDashboard()
    {
        $this->setAdmin(true);

        $this->client->request('GET', '/admin/dashboard/', array(
            'flush_cache' => 'ok',
            'email'       => 'sent'
        ));

        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Dashboard::flush
     */
    public function testFlushCache()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/dashboard/flush-cache/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertRegexp('/flush_cache=ok/', $this->client->getResponse()->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Dashboard::sendMail
     */
    public function testSendMailTest()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/dashboard/send-mail-test/', array(
            'email' => self::$DI['user']->get_email()
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertRegexp('/email=/', $this->client->getResponse()->headers->get('location'));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers \Alchemy\Phrasea\Controller\Admin\Dashboard::sendMail
     */
    public function testSendMailTestBadRequest()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/dashboard/send-mail-test/');
    }
    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Dashboard::resetAdminRights
     */
    public function testResetAdminRights()
    {
        $this->setAdmin(true);

        $this->client->request('POST', '/admin/dashboard/reset-admin-rights/');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Dashboard::addAdmins
     */
    public function testAddAdmins()
    {
        $this->setAdmin(true);

        $admins = array_keys(\User_Adapter::get_sys_admins(self::$application));

        $user = \User_Adapter::create(self::$application, uniqid('unit_test_user'), uniqid('unit_test_user'),  uniqid('unit_test_user') ."@email.com", false);

        $admins[] = $user->get_id();

        $this->client->request('POST', '/admin/dashboard/add-admins/', array(
            'admins' => $admins
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());

        $user->delete();
    }
}
