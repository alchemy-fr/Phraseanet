<?php

namespace Alchemy\Tests\Phrasea\Controller\Root;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_EMAIL, self::$DI['user']->get_id(), null, 'new_email@email.com');
        $crawler = self::$DI['client']->request('GET', '/account/reset-email/', array('token'   => $token));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/', $response->headers->get('location'));

        $this->assertEquals('new_email@email.com', self::$DI['user']->get_email());
        self::$DI['user']->set_email('noone@example.com');
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
        self::$DI['client']->request('GET', '/account/reset-email/', array('token'   => '134dT0k3n'));
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
        self::$DI['client']->request('POST', '/account/reset-email/', array(
            'form_password'      => 'changeme',
            'form_email'         => 'new@email.com',
            'form_email_confirm' => 'new@email.com',
        ));

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
        self::$DI['app']['authentication']->getUser()->set_password($password);
        self::$DI['client']->request('POST', '/account/reset-email/', array(
            'form_password'      => $password,
            'form_email'         => "invalid#!&&@@email.x",
            'form_email_confirm' => 'invalid#!&&@@email.x',
        ));

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
        self::$DI['app']['authentication']->getUser()->set_password($password);
        self::$DI['client']->request('POST', '/account/reset-email/', array(
            'form_password'      => $password,
            'form_email'         => 'email1@email.com',
            'form_email_confirm' => 'email2@email.com',
        ));

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
        self::$DI['app']['authentication']->getUser()->set_password($password);
        self::$DI['client']->request('POST', '/account/reset-email/', array(
            'form_password'      => $password,
            'form_email'         => 'email1@email.com',
            'form_email_confirm' => 'email1@email.com',
        ));

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
        $bases = $notifs = array();

        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $bases[] = $collection->get_base_id();
            }
        }

        if (0 === count($bases)) {
            $this->markTestSkipped('No collections');
        }

        foreach (self::$DI['app']['events-manager']->list_notifications_available(self::$DI['app']['authentication']->getUser()->get_id()) as $notifications) {
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
            'form_retryFTP'        => '',
            'notifications'        => $notifs,
            'form_defaultdataFTP'  => array('document', 'preview', 'caption'),
            'mail_notifications' => '1'
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('minet', self::$DI['app']['authentication']->getUser()->get_lastname());

        $sql = 'SELECT base_id FROM demand WHERE usr_id = :usr_id AND en_cours="1" ';
        $stmt = self::$DI['app']['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => self::$DI['app']['authentication']->getUser()->get_id()));
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
    public function testPostRenewPasswordBadArguments($oldPassword, $password, $passwordConfirm)
    {
        self::$DI['app']['authentication']->getUser()->set_password($oldPassword);

        $crawler = self::$DI['client']->request('POST', '/account/reset-password/', array(
            'password' => array(
                'password' => $password,
                'confirm'  => $passwordConfirm
            ),
            'oldPassword'     => $oldPassword,
            '_token'          => 'token',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isRedirect());
        $this->assertFormOrFlashError($crawler, 1);
    }

    public function testPostRenewPasswordBadOldPassword()
    {
        $crawler = self::$DI['client']->request('POST', '/account/reset-password/', array(
            'password' => array(
                'password' => 'password',
                'confirm'  => 'password'
            ),
            'oldPassword'     => 'oulala',
            '_token'          => 'token',
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertFalse($response->isRedirect());
        $this->assertFlashMessage($crawler, 'error', 1);
    }

    public function testPostRenewPasswordNoToken()
    {
        $password = \random::generatePassword();

        self::$DI['app']['authentication']->getUser()->set_password($password);

        $crawler = self::$DI['client']->request('POST', '/account/reset-password/', array(
            'password' => array(
                'password' => 'password',
                'confirm'  => 'password'
            ),
            'oldPassword'     => $password,
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isRedirect());
        $this->assertFormError($crawler, 1);
    }

    public function testPostRenewPassword()
    {
        $password = \random::generatePassword();

        self::$DI['app']['authentication']->getUser()->set_password($password);

        self::$DI['client']->request('POST', '/account/reset-password/', array(
            'password' => array(
                'password' => 'password',
                'confirm'  => 'password'
            ),
            'oldPassword'     => $password,
            '_token'          => 'token',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/account/', $response->headers->get('location'));

        $this->assertFlashMessagePopulated(self::$DI['app'], 'success', 1);
    }

    public function passwordProvider()
    {
        return array(
            array(\random::generatePassword(), 'password', 'not_identical_password'),
        );
    }
}
