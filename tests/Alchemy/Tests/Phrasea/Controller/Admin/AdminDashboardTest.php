<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Model\Entities\User;

#[\PHPUnit\Framework\Attributes\CoversMethod(\Alchemy\Phrasea\Controller\Admin\Dashboard::class, 'slash')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Alchemy\Phrasea\Controller\Admin\Dashboard::class, 'connect')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Alchemy\Phrasea\Controller\Admin\Dashboard::class, 'call')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Alchemy\Phrasea\Controller\Admin\Dashboard::class, 'flush')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Alchemy\Phrasea\Controller\Admin\Dashboard::class, 'sendMail')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Alchemy\Phrasea\Controller\Admin\Dashboard::class, 'resetAdminRights')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Alchemy\Phrasea\Controller\Admin\Dashboard::class, 'addAdmins')]
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class AdminDashboardTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    public function testRouteDashboardUnauthorized()
    {
        $this->setAdmin(false);
        self::$DI['client']->request('GET', '/admin/dashboard/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    public function testRouteDashboard()
    {
        $this->setAdmin(true);
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
        self::$DI['app']['phraseanet.configuration-tester']->getRequirements();
        self::$DI['client']->request('GET', '/admin/dashboard/', [
            'flush_cache' => 'ok',
            'email'       => 'sent'
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testFlushCache()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/dashboard/flush-cache/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertMatchesRegularExpression('/flush_cache=ok/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function testSendMailTest()
    {
        $this->setAdmin(true);

        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailCheck');

        self::$DI['client']->request('POST', '/admin/dashboard/send-mail-test/', [
            'email' => 'user-test@phraseanet.com'
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertMatchesRegularExpression('/email=/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function testSendMailTestWithWrongMail()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/dashboard/send-mail-test/', [
            'email' => 'user-test-phraseanet.com'
        ]);

        $this->assertEquals(400, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testSendMailTestBadRequest()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/dashboard/send-mail-test/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }
    public function testResetAdminRights()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/dashboard/reset-admin-rights/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    public function testAddAdmins()
    {
        $this->setAdmin(true);

        $admins = array_map(function (User $user) {
            return $user->getId();
        }, self::$DI['app']['repo.users']->findAdmins());

        $user = self::$DI['app']['manipulator.user']->createUser(uniqid('unit_test_user'), uniqid('unit_test_user'),  uniqid('unit_test_user') ."@email.com");

        $admins[] = $user->getId();

        self::$DI['client']->request('POST', '/admin/dashboard/add-admins/', [
            'admins' => $admins
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        self::$DI['app']['manipulator.user']->delete($user);
    }
}
