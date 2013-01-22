<?php

namespace Alchemy\Tests\Phrasea\Controller\Root;

use Alchemy\Phrasea\Application;

class AccountTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected static $authorizedApp;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        try {
            self::$authorizedApp = \API_OAuth2_Application::create(new Application('test'), self::$DI['user'], 'test API v1');
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
        $crawler = self::$DI['client']->request('GET', '/account/');

        $response = self::$DI['client']->getResponse();

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
        $crawler = self::$DI['client']->request('GET', '/account/', array(
            'notice' => $msg
            ));

        $response = self::$DI['client']->getResponse();

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
        self::$DI['client']->request('GET', '/account/access/');

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailWithToken()
    {
        $token = \random::getUrlToken(self::$DI['app'], \random::TYPE_EMAIL, self::$DI['user']->get_id(), null, 'new_email@email.com');
        self::$DI['client']->request('POST', '/account/reset-email/', array('token'   => $token));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/?update=ok', $response->headers->get('location'));

        $this->assertEquals('new_email@email.com', self::$DI['user']->get_email());
        self::$DI['user']->set_email('noone@example.com');
        try {
            \random::helloToken(self::$DI['app'], $token);
            $this->fail('TOken has not been removed');
        } catch (\Exception_NotFound $e) {

        }
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailWithBadToken()
    {
        self::$DI['client']->request('POST', '/account/reset-email/', array('token'   => '134dT0k3n'));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/?update=ko', $response->headers->get('location'));
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
        self::$DI['client']->request('POST', '/account/reset-email/', array(
            'form_password'      => 'changeme',
            'form_email'         => 'new@email.com',
            'form_email_confirm' => 'new@email.com',
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/?notice=bad-password', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailBadEmail()
    {
        $password = \random::generatePassword();
        self::$DI['app']['phraseanet.user']->set_password($password);
        self::$DI['client']->request('POST', '/account/reset-email/', array(
            'form_password'      => $password,
            'form_email'         => "invalid#!&&@@email.x",
            'form_email_confirm' => 'invalid#!&&@@email.x',
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/?notice=mail-invalid', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailEmailNotIdentical()
    {
        $password = \random::generatePassword();
        self::$DI['app']['phraseanet.user']->set_password($password);
        self::$DI['client']->request('POST', '/account/reset-email/', array(
            'form_password'      => $password,
            'form_email'         => 'email1@email.com',
            'form_email_confirm' => 'email2@email.com',
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/?notice=mail-match', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Account::resetEmail
     */
    public function testPostResetMailEmail()
    {
        $password = \random::generatePassword();
        self::$DI['app']['phraseanet.user']->set_password($password);
        self::$DI['client']->request('POST', '/account/reset-email/', array(
            'form_password'      => $password,
            'form_email'         => 'email1@email.com',
            'form_email_confirm' => 'email1@email.com',
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/reset-email/?update=mail-send', $response->headers->get('location'));
    }

    /**
     * @dataProvider noticeProvider
     */
    public function testGetResetMailNotice($notice)
    {
        $crawler = self::$DI['client']->request('GET', '/account/reset-email/', array(
            'notice' => $notice
            ));

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

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
        $crawler = self::$DI['client']->request('GET', '/account/reset-email/', array(
            'update' => $updateMessage
            ));

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

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
     * @dataProvider passwordMsgProvider
     */
    public function testGetResetPasswordPassError($msg)
    {
        $crawler = self::$DI['client']->request('GET', '/account/reset-password/', array(
            'pass-error' => $msg
            ));

        $response = self::$DI['client']->getResponse();

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
        $register = new \appbox_register(self::$DI['app']['phraseanet.appbox']);
        $bases = $notifs = array();

        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $bases[] = $collection->get_base_id();
            }
        }

        if (0 === count($bases)) {
            $this->markTestSkipped('No collections');
        }

        foreach (self::$DI['app']['events-manager']->list_notifications_available(self::$DI['app']['phraseanet.user']->get_id()) as $notifications) {
            foreach ($notifications as $notification) {
                $notifs[] = $notification['id'];
            }
        }

        array_shift($notifs);

        self::$DI['client']->request('POST', '/account/', array(
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

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('minet', self::$DI['app']['phraseanet.user']->get_lastname());

        $sql = 'SELECT base_id FROM demand WHERE usr_id = :usr_id AND en_cours="1" ';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $user->get_id()));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        self::$DI['client']->request('GET', '/account/security/application/0/grant/', array(), array(), array('HTTP_ACCEPT'           => 'application/json', 'HTTP_X-Requested-With' => 'XMLHttpRequest'));
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
        if (null === self::$authorizedApp) {
            $this->markTestSkipped('Application could not be created');
        }

        self::$DI['client']->request('GET', '/account/security/application/' . self::$authorizedApp->get_id() . '/grant/', array(
            'revoke' => $revoke
            ), array(), array(
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
        $json = json_decode($response->getContent());
        $this->assertInstanceOf('StdClass', $json);
        $this->assertObjectHasAttribute('success', $json);
        $this->assertTrue($json->success);

        $account = \API_OAuth2_Account::load_with_user(
                self::$DI['app']
                , self::$authorizedApp
                , self::$DI['user']
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
        self::$DI['app']['phraseanet.user']->set_password($oldPassword);

        self::$DI['client']->request('POST', '/account/reset-password/', array(
            'form_password'         => $password,
            'form_password_confirm' => $passwordConfirm,
            'form_old_password'     => $oldPassword
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals($redirect, $response->headers->get('location'));
    }

    public function testPostRenewPasswordBadOldPassword()
    {
        self::$DI['client']->request('POST', '/account/reset-password/', array(
            'form_password'         => 'password',
            'form_password_confirm' => 'password',
            'form_old_password'     => 'oulala'
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/?notice=pass-ko', $response->headers->get('location'));
    }

    public function testPostRenewPassword()
    {
        $password = \random::generatePassword();

        self::$DI['app']['phraseanet.user']->set_password($password);

        self::$DI['client']->request('POST', '/account/reset-password/', array(
            'form_password'         => 'password',
            'form_password_confirm' => 'password',
            'form_old_password'     => $password
        ));

        $response = self::$DI['client']->getResponse();

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
