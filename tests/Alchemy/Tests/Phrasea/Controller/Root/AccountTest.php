<?php

namespace Alchemy\Tests\Phrasea\Controller\Root;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Alchemy\Phrasea\Model\Entities\User;

class AccountTest extends \PhraseanetAuthenticatedWebTestCase
{
    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::displayAccount
     * @covers \Alchemy\Phrasea\Controller\Root\Account::call
     */
    public function testGetAccount()
    {
        $crawler = self::$DI['client']->request('GET', '/account/');

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());

        $actionForm = $crawler->filter('form[name=account]')->attr('action');
        $methodForm = $crawler->filter('form[name=account]')->attr('method');

        $this->assertEquals('/account/', $actionForm);
        $this->assertEquals('post', $methodForm);
    }

    /**
     * @dataProvider provideFlashMessages
     */
    public function testGetAccountNotice($type, $message)
    {
        self::$DI['app']->addFlash($type, $message);
        $crawler = self::$DI['client']->request('GET', '/account/');

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());

        $this->assertFlashMessage($crawler, $type, 1, $message);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::accountAccess
     */
    public function testGetAccountAccess()
    {
        self::$DI['client']->request('GET', '/account/access/');

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testGetResetMailWithToken()
    {
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_EMAIL, self::$DI['user']->getId(), null, 'new_email@email.com');
        $crawler = self::$DI['client']->request('GET', '/account/reset-email/', ['token'   => $token]);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/', $response->headers->get('location'));

        $this->assertEquals('new_email@email.com', self::$DI['user']->getEmail());
        self::$DI['user']->setEmail('noone@example.com');
        try {
            self::$DI['app']['tokens']->helloToken($token);
            $this->fail('Token has not been removed');
        } catch (NotFoundHttpException $e) {

        }

        $this->assertFlashMessagePopulated(self::$DI['app'], 'success', 1);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testGetResetMailWithBadToken()
    {
        self::$DI['client']->request('GET', '/account/reset-email/', ['token'   => '134dT0k3n']);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/', $response->headers->get('location'));

        $this->assertFlashMessagePopulated(self::$DI['app'], 'error', 1);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailBadRequest()
    {
        self::$DI['client']->request('POST', '/account/reset-email/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailBadPassword()
    {
        self::$DI['client']->request('POST', '/account/reset-email/', [
            'form_password'      => 'changeme',
            'form_email'         => 'new@email.com',
            'form_email_confirm' => 'new@email.com',
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/', $response->headers->get('location'));

        $this->assertFlashMessagePopulated(self::$DI['app'], 'error', 1);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailBadEmail()
    {
        $password = \random::generatePassword();
        self::$DI['app']['manipulator.user']->setPassword(self::$DI['app']['authentication']->getUser(), $password);
        self::$DI['client']->request('POST', '/account/reset-email/', [
            'form_password'      => $password,
            'form_email'         => "invalid#!&&@@email.x",
            'form_email_confirm' => 'invalid#!&&@@email.x',
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/', $response->headers->get('location'));

        $this->assertFlashMessagePopulated(self::$DI['app'], 'error', 1);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailEmailNotIdentical()
    {
        $password = \random::generatePassword();
        self::$DI['app']['manipulator.user']->setPassword(self::$DI['app']['authentication']->getUser(), $password);
        self::$DI['client']->request('POST', '/account/reset-email/', [
            'form_password'      => $password,
            'form_email'         => 'email1@email.com',
            'form_email_confirm' => 'email2@email.com',
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/', $response->headers->get('location'));

        $this->assertFlashMessagePopulated(self::$DI['app'], 'error', 1);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailEmail()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailRequestEmailUpdate');

        $password = \random::generatePassword();
        self::$DI['app']['manipulator.user']->setPassword(
            self::$DI['app']['authentication']->getUser(),
            $password
        );
        self::$DI['client']->request('POST', '/account/reset-email/', [
            'form_password'      => $password,
            'form_email'         => 'email1@email.com',
            'form_email_confirm' => 'email1@email.com',
        ]);

        self::$DI['client']->followRedirects();
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/', $response->headers->get('location'));

        $this->assertFlashMessagePopulated(self::$DI['app'], 'info', 1);
    }

    /**
     * @dataProvider provideFlashMessages
     */
    public function testGetResetMailNotice($type, $message)
    {
        self::$DI['app']->addFlash($type, $message);

        $crawler = self::$DI['client']->request('GET', '/account/reset-email/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        $this->assertFlashMessage($crawler, $type, 1, $message);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::accountSessionsAccess
     */
    public function testGetAccountSecuritySessions()
    {
        self::$DI['client']->request('GET', '/account/security/sessions/');

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::accountAuthorizedApps
     */
    public function testGetAccountSecurityApplications()
    {
        self::$DI['client']->request('GET', '/account/security/applications/');

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetPassword
     */
    public function testGetResetPassword()
    {
        self::$DI['client']->request('GET', '/account/reset-password/');

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @dataProvider provideFlashMessages
     */
    public function testGetResetPasswordPassError($type, $message)
    {
        self::$DI['app']->addFlash($type, $message);

        $crawler = self::$DI['client']->request('GET', '/account/reset-password/');

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());

        $this->assertFlashMessage($crawler, $type, 1, $message);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::updateAccount
     */
    public function testUpdateAccount()
    {
        $bases = $notifs = [];

        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $bases[] = $collection->get_base_id();
            }
        }

        if (0 === count($bases)) {
            $this->markTestSkipped('No collections');
        }

        foreach (self::$DI['app']['events-manager']->list_notifications_available(self::$DI['app']['authentication']->getUser()->getId()) as $notifications) {
            foreach ($notifications as $notification) {
                $notifs[] = $notification['id'];
            }
        }

        array_shift($notifs);

        self::$DI['client']->request('POST', '/account/', [
            'demand'               => $bases,
            'form_gender'          => User::GENDER_MR,
            'form_firstname'       => 'gros',
            'form_lastname'        => 'minet',
            'form_address'         => 'rue du lac',
            'form_zip'             => '75005',
            'form_phone'           => '+33645787878',
            'form_fax'             => '+33145787845',
            'form_function'        => 'astronaute',
            'form_company'         => 'NASA',
            'form_activity'        => 'Space',
            'form_geonameid'       => '1839',
            'form_addressFTP'      => '',
            'form_loginFTP'        => '',
            'form_pwdFTP'          => '',
            'form_destFTP'         => '',
            'form_prefixFTPfolder' => '',
            'form_retryFTP'        => '',
            'notifications'        => $notifs,
            'form_defaultdataFTP'  => ['document', 'preview', 'caption'],
            'mail_notifications' => '1'
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('minet', self::$DI['app']['authentication']->getUser()->getLastName());

        $sql = 'SELECT base_id FROM demand WHERE usr_id = :usr_id AND en_cours="1" ';
        $stmt = self::$DI['app']['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => self::$DI['app']['authentication']->getUser()->getId()]);
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->assertCount(count($bases), $rs);
    }

    public function testAUthorizedAppGrantAccessBadRequest()
    {
        self::$DI['client']->request('GET', '/account/security/application/3/grant/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    public function testAUthorizedAppGrantAccessNotSuccessfull()
    {
        self::$DI['client']->request('GET', '/account/security/application/0/grant/', [], [], ['HTTP_ACCEPT'           => 'application/json', 'HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
        $json = json_decode($response->getContent());
        $this->assertInstanceOf('StdClass', $json);
        $this->assertObjectHasAttribute('success', $json);
        $this->assertFalse($json->success);
    }

    /**
     * @dataProvider revokeProvider
     */
    public function testAUthorizedAppGrantAccessSuccessfull($revoke, $expected)
    {
        self::$DI['client']->request('GET', '/account/security/application/' . self::$DI['oauth2-app-user']->get_id() . '/grant/', [
            'revoke' => $revoke
            ], [], [
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
        $json = json_decode($response->getContent());
        $this->assertInstanceOf('StdClass', $json);
        $this->assertObjectHasAttribute('success', $json);
        $this->assertTrue($json->success);

        $account = \API_OAuth2_Account::load_with_user(
                self::$DI['app']
                , self::$DI['oauth2-app-user']
                , self::$DI['user']
        );

        $this->assertEquals($expected, $account->is_revoked());
    }

    public function revokeProvider()
    {
        return [
            ['1', true],
            ['0', false],
            [null, false],
            ['titi', true],
        ];
    }

    /**
     * @dataProvider passwordProvider
     */
    public function testPostRenewPasswordBadArguments($oldPassword, $password, $passwordConfirm)
    {
        self::$DI['app']['manipulator.user']->setPassword(self::$DI['app']['authentication']->getUser(), $oldPassword);

        $crawler = self::$DI['client']->request('POST', '/account/reset-password/', [
            'password' => [
                'password' => $password,
                'confirm'  => $passwordConfirm
            ],
            'oldPassword'     => $oldPassword,
            '_token'          => 'token',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isRedirect());
        $this->assertFormOrFlashError($crawler, 1);
    }

    public function testPostRenewPasswordBadOldPassword()
    {
        $crawler = self::$DI['client']->request('POST', '/account/reset-password/', [
            'password' => [
                'password' => 'password',
                'confirm'  => 'password'
            ],
            'oldPassword'     => 'oulala',
            '_token'          => 'token',
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertFalse($response->isRedirect());
        $this->assertFlashMessage($crawler, 'error', 1);
    }

    public function testPostRenewPasswordNoToken()
    {
        $password = \random::generatePassword();

        self::$DI['app']['manipulator.user']->setPassword(self::$DI['app']['authentication']->getUser(), $password);

        $crawler = self::$DI['client']->request('POST', '/account/reset-password/', [
            'password' => [
                'password' => 'password',
                'confirm'  => 'password'
            ],
            'oldPassword'     => $password,
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isRedirect());
        $this->assertFormError($crawler, 1);
    }

    public function testPostRenewPassword()
    {
        $password = \random::generatePassword();

        self::$DI['app']['manipulator.user']->setPassword(self::$DI['app']['authentication']->getUser(), $password);

        self::$DI['client']->request('POST', '/account/reset-password/', [
            'password' => [
                'password' => 'password',
                'confirm'  => 'password'
            ],
            'oldPassword'     => $password,
            '_token'          => 'token',
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/', $response->headers->get('location'));

        $this->assertFlashMessagePopulated(self::$DI['app'], 'success', 1);
    }

    public function passwordProvider()
    {
        return [
            [\random::generatePassword(), 'password', 'not_identical_password'],
        ];
    }
}
