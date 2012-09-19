<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class AccountTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected static $authorizedApp;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        try {
            self::$authorizedApp = \API_OAuth2_Application::create(self::$application, self::$user, 'test API v1');
        } catch (\Exception $e) {

        }
    }

    public static function tearDownAfterClass()
    {
        if (self::$authorizedApp) {
            self::$authorizedApp->delete();
        }

         parent::tearDownAfterClass();
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::displayAccount
     * @covers \Alchemy\Phrasea\Controller\Root\Account::call
     */
    public function testGetAccount()
    {
        $crawler = $this->client->request('GET', '/account/');

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());

        $actionForm = $crawler->filter('form[name=account]')->attr('action');
        $methodForm = $crawler->filter('form[name=account]')->attr('method');

        $this->assertEquals('/account/', $actionForm);
        $this->assertEquals('post', $methodForm);
    }

    /**
     * @dataProvider msgProvider
     */
    public function testGetAccountNotice($msg)
    {
        $crawler = $this->client->request('GET', '/account/', array(
            'notice' => $msg
            ));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());

        $this->assertEquals(1, $crawler->filter('.notice')->count());
    }

    public function msgProvider()
    {
        return array(
            array('pass-ok'),
            array('pass-ko'),
            array('account-update-ok'),
            array('account-update-bad'),
            array('demand-ok'),
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::accountAccess
     */
    public function testGetAccountAccess()
    {
        $this->client->request('GET', '/account/access/');

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailWithToken()
    {
        $token = \random::getUrlToken(self::$application, \random::TYPE_EMAIL, self::$user->get_id(), null, 'new_email@email.com');
        $this->client->request('POST', '/account/reset-email/', array('token'   => $token));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/?update=ok', $response->headers->get('location'));

        $this->assertEquals('new_email@email.com', self::$user->get_email());
        self::$user->set_email('noone@example.com');
        try {
            \random::helloToken(self::$application, $token);
            $this->fail('TOken has not been removed');
        } catch (\Exception_NotFound $e) {

        }
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailWithBadToken()
    {
        $this->client->request('POST', '/account/reset-email/', array('token'   => '134dT0k3n'));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/?update=ko', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testPostResetMailBadRequest()
    {
        $this->client->request('POST', '/account/reset-email/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailBadPassword()
    {
        $this->client->request('POST', '/account/reset-email/', array(
            'form_password'      => 'changeme',
            'form_email'         => 'new@email.com',
            'form_email_confirm' => 'new@email.com',
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/?notice=bad-password', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailBadEmail()
    {
        $password = \random::generatePassword();
        self::$application['phraseanet.user']->set_password($password);
        $this->client->request('POST', '/account/reset-email/', array(
            'form_password'      => $password,
            'form_email'         => "invalid#!&&@@email.x",
            'form_email_confirm' => 'invalid#!&&@@email.x',
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/?notice=mail-invalid', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailEmailNotIdentical()
    {
        $password = \random::generatePassword();
        self::$application['phraseanet.user']->set_password($password);
        $this->client->request('POST', '/account/reset-email/', array(
            'form_password'      => $password,
            'form_email'         => 'email1@email.com',
            'form_email_confirm' => 'email2@email.com',
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/?notice=mail-match', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailEmail()
    {
        $password = \random::generatePassword();
        self::$application['phraseanet.user']->set_password($password);
        $this->client->request('POST', '/account/reset-email/', array(
            'form_password'      => $password,
            'form_email'         => 'email1@email.com',
            'form_email_confirm' => 'email1@email.com',
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/?update=mail-send', $response->headers->get('location'));
    }

    /**
     * @dataProvider noticeProvider
     */
    public function testGetResetMailNotice($notice)
    {
        $crawler = $this->client->request('GET', '/account/reset-email/', array(
            'notice' => $notice
            ));

        $this->assertTrue($this->client->getResponse()->isOk());

        $this->assertEquals(2, $crawler->filter('.notice')->count());
    }

    public function noticeProvider()
    {
        return array(
            array('mail-server'),
            array('mail-match'),
            array('mail-invalid'),
            array('bad-password'),
        );
    }

    /**
     * @dataProvider updateMsgProvider
     */
    public function testGetResetMailUpdate($updateMessage)
    {
        $crawler = $this->client->request('GET', '/account/reset-email/', array(
            'update' => $updateMessage
            ));

        $this->assertTrue($this->client->getResponse()->isOk());

        $this->assertEquals(1, $crawler->filter('.alert-info')->count());
    }

    public function updateMsgProvider()
    {
        return array(
            array('ok'),
            array('ko'),
            array('mail-send'),
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::accountSessionsAccess
     */
    public function testGetAccountSecuritySessions()
    {
        $this->client->request('GET', '/account/security/sessions/');

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::accountAuthorizedApps
     */
    public function testGetAccountSecurityApplications()
    {
        $this->client->request('GET', '/account/security/applications/');

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetPassword
     */
    public function testGetResetPassword()
    {
        $this->client->request('GET', '/account/reset-password/');

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @dataProvider passwordMsgProvider
     */
    public function testGetResetPasswordPassError($msg)
    {
        $crawler = $this->client->request('GET', '/account/reset-password/', array(
            'pass-error' => $msg
            ));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());

        $this->assertEquals(1, $crawler->filter('.alert-error')->count());
    }

    public function passwordMsgProvider()
    {
        return array(
            array('pass-match'),
            array('pass-short'),
            array('pass-invalid'),
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::updateAccount
     */
    public function testUpdateAccount()
    {
        $evtMngr = self::$application['events-manager'];
        $register = new \appbox_register(self::$application['phraseanet.appbox']);
        $bases = $notifs = array();

        foreach (self::$application['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $bases[] = $collection->get_base_id();
            }
        }

        if (0 === count($bases)) {
            $this->markTestSkipped('No collections');
        }

        foreach ($evtMngr->list_notifications_available(self::$application['phraseanet.user']->get_id()) as $notifications) {
            foreach ($notifications as $notification) {
                $notifs[] = $notification['id'];
            }
        }

        array_shift($notifs);

        $this->client->request('POST', '/account/', array(
            'demand'               => $bases,
            'form_gender'          => 'M',
            'form_firstname'       => 'gros',
            'form_lastname'        => 'minet',
            'form_address'         => 'rue du lac',
            'form_zip'             => '75005',
            'form_phone'           => '+33645787878',
            'form_fax'             => '+33145787845',
            'form_function'        => 'astronaute',
            'form_company'         => 'NASA',
            'form_activity'        => 'Space',
            'form_geonameid'       => '',
            'form_addrFTP'         => '',
            'form_loginFTP'        => '',
            'form_pwdFTP'          => '',
            'form_destFTP'         => '',
            'form_prefixFTPfolder' => '',
            'notifications'        => $notifs,
            'form_defaultdataFTP'  => array('document', 'preview', 'caption'),
            'mail_notifications' => '1'
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('minet', self::$application['phraseanet.user']->get_lastname());

        $ret = $register->get_collection_awaiting_for_user(self::$application, self::$user);

        $this->assertEquals(count($ret), count($bases));
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testAUthorizedAppGrantAccessBadRequest()
    {
        $this->client->request('GET', '/account/security/application/3/grant/');
    }

    public function testAUthorizedAppGrantAccessNotSuccessfull()
    {
        $this->client->request('GET', '/account/security/application/3/grant/', array(), array(), array('HTTP_ACCEPT'           => 'application/json', 'HTTP_X-Requested-With' => 'XMLHttpRequest'));
        $response = $this->client->getResponse();

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
        if (null === self::$authorizedApp) {
            $this->markTestSkipped('Application could not be created');
        }

        $this->client->request('GET', '/account/security/application/' . self::$authorizedApp->get_id() . '/grant/', array(
            'revoke' => $revoke
            ), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
        $json = json_decode($response->getContent());
        $this->assertInstanceOf('StdClass', $json);
        $this->assertObjectHasAttribute('success', $json);
        $this->assertTrue($json->success);

        $account = \API_OAuth2_Account::load_with_user(
                self::$application
                , self::$authorizedApp
                , self::$user
        );

        $this->assertEquals($expected, $account->is_revoked());
    }

    public function revokeProvider()
    {
        return array(
            array('1', true),
            array('0', false),
            array(null, false),
            array('titi', true),
        );
    }

    /**
     * @dataProvider passwordProvider
     */
    public function testPostRenewPasswordBadArguments($oldPassword, $password, $passwordConfirm, $redirect)
    {
        self::$application['phraseanet.user']->set_password($oldPassword);

        $this->client->request('POST', '/account/reset-password/', array(
            'form_password'         => $password,
            'form_password_confirm' => $passwordConfirm,
            'form_old_password'     => $oldPassword
        ));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals($redirect, $response->headers->get('location'));
    }

    public function testPostRenewPasswordBadOldPassword()
    {
        $this->client->request('POST', '/account/reset-password/', array(
            'form_password'         => 'password',
            'form_password_confirm' => 'password',
            'form_old_password'     => 'oulala'
        ));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/?notice=pass-ko', $response->headers->get('location'));
    }

    public function testPostRenewPassword()
    {
        $password = \random::generatePassword();

        self::$application['phraseanet.user']->set_password($password);

        $this->client->request('POST', '/account/reset-password/', array(
            'form_password'         => 'password',
            'form_password_confirm' => 'password',
            'form_old_password'     => $password
        ));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/?notice=pass-ok', $response->headers->get('location'));
    }

    public function passwordProvider()
    {
        return array(
            array(\random::generatePassword(), 'password', 'not_identical_password', '/account/reset-password/?pass-error=pass-match'),
            array(\random::generatePassword(), 'min', 'min', '/account/reset-password/?pass-error=pass-short'),
            array(\random::generatePassword(), 'invalid password \n', 'invalid password \n', '/account/reset-password/?pass-error=pass-invalid'),
        );
    }
}
