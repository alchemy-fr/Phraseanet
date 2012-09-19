<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Symfony\Component\HttpKernel\Client;

class LoginTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::login
     * @covers \Alchemy\Phrasea\Controller\Root\Login::connect
     */
    public function testLoginAlreadyAthenticated()
    {
        $this->client->request('GET', '/login/');
        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/prod/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::login
     */
    public function testLoginRedirectPostLog()
    {
        self::$application['phraseanet.appbox']->get_session()->logout();

        $this->client->request('GET', '/login/', array('postlog'  => '1', 'redirect' => 'prod'));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/logout/?redirect=prod', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::login
     * @dataProvider errorAndNoticeMsgProvider
     */
    public function testLoginError($warning, $notice)
    {
        self::$application['phraseanet.appbox']->get_session()->logout();

        $this->client->request('GET', '/login/', array(
            'error'  => $warning,
            'notice' => $notice
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
    }

    public function errorAndNoticeMsgProvider()
    {
        return array(
            array('auth', 'ok'),
            array('maintenance', 'already'),
            array('no-connection', 'mail-sent'),
            array('captcha', 'register-ok'),
            array('mail-not-confirmed', 'register-ok-wait'),
            array('no-base', 'password-update-ok'),
            array('session', 'no-register-available')
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailNoCode()
    {
        $this->client->request('GET', '/login/register-confirm/');
        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?redirect=/prod&error=code-not-found', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailWrongCode()
    {
        $this->client->request('GET', '/login/register-confirm/', array('code'    => '34dT0k3n'));
        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?redirect=/prod&error=token-not-found', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailUserNotFound()
    {
        $email = $this->generateEmail();
        $token = \random::getUrlToken(self::$application, \random::TYPE_EMAIL, 0, null, $email);
        $this->client->request('GET', '/login/register-confirm/', array('code'    => $token));
        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?redirect=/prod&error=user-not-found', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailUnlocked()
    {
        $email = $this->generateEmail();
        $token = \random::getUrlToken(self::$application, \random::TYPE_EMAIL, self::$user->get_id(), null, $email);

        self::$user->set_mail_locked(false);

        $this->client->request('GET', '/login/register-confirm/', array('code'    => $token));
        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?redirect=prod&notice=already', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMail()
    {
        $email = $this->generateEmail();
        $appboxRegister = new \appbox_register(self::$application['phraseanet.appbox']);
        $token = \random::getUrlToken(self::$application, \random::TYPE_EMAIL, self::$user->get_id(), null, $email);

        self::$user->set_mail_locked(true);
        $this->deleteRequest();
        $appboxRegister->add_request(self::$user, self::$collection);
        $this->client->request('GET', '/login/register-confirm/', array('code'    => $token));
        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?redirect=prod&notice=confirm-ok-wait', $response->headers->get('location'));
        $this->assertFalse(self::$user->get_mail_locked());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailNoCollAwait()
    {
        $email = $this->generateEmail();
        $token = \random::getUrlToken(self::$application, \random::TYPE_EMAIL, self::$user->get_id(), null, $email);

        self::$user->set_mail_locked(true);

        $this->deleteRequest();

        $this->client->request('GET', '/login/register-confirm/', array('code'    => $token));
        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());

        $this->assertEquals('/login/?redirect=prod&notice=confirm-ok', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordInvalidEmail()
    {
        $this->client->request('POST', '/login/forgot-password/', array('mail'    => 'invalid.email.com'));
        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/forgot-password/?error=invalidmail', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordUnknowEmail()
    {
        $this->client->request('POST', '/login/forgot-password/', array('mail'    => 'invalid_email@test.com'));
        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/forgot-password/?error=noaccount', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordMail()
    {
        $this->client->request('POST', '/login/forgot-password/', array('mail'    => self::$user->get_email()));
        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/forgot-password/?sent=ok', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     * @dataProvider passwordProvider
     */
    public function testRenewPasswordBadArguments($password, $passwordConfirm, $redirect)
    {
        $this->client->request('POST', '/login/forgot-password/', array(
            'token'                 => '1Cx6Z7',
            'form_password'         => $password,
            'form_password_confirm' => $passwordConfirm
            )
        );
        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals($redirect, $response->headers->get('location'));
    }

    public function testRenewPasswordBadToken()
    {
        $this->client->request('POST', '/login/forgot-password/', array(
            'token'                 => 'badToken',
            'form_password'         => 'password',
            'form_password_confirm' => 'password'
            )
        );
        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/forgot-password/?error=token', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     * @dataProvider passwordProvider
     */
    public function testRenewPassword()
    {
        $token = \random::getUrlToken(self::$application, \random::TYPE_PASSWORD, self::$user->get_id());

        $this->client->request('POST', '/login/forgot-password/', array(
            'token'                 => $token,
            'form_password'         => 'password',
            'form_password_confirm' => 'password'
            )
        );
        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?notice=password-update-ok', $response->headers->get('location'));
    }

    public function passwordProvider()
    {
        return array(
            array('password', 'password_not_identical', '/login/forgot-password/?pass-error=pass-match'),
            array('min', 'min', '/login/forgot-password/?pass-error=pass-short'),
            array('in valid password', 'in valid password', '/login/forgot-password/?pass-error=pass-invalid'),
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::displayForgotPasswordForm
     */
    public function testGetForgotPasswordSendMsg()
    {
        $this->client->request('GET', '/login/forgot-password/', array(
            'sent' => 'ok',
        ));

        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::displayForgotPasswordForm
     */
    public function testGetForgotBadToken()
    {
        $crawler = $this->client->request('GET', '/login/forgot-password/', array(
            'token' => 'one-token'
            ));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertEquals(1, $crawler->filter('.alert-error')->count());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::displayForgotPasswordForm
     * @dataProvider errorMessageProvider
     */
    public function testGetForgotPasswordErrorMsg($errorMsg)
    {
        $crawler = $this->client->request('GET', '/login/forgot-password/', array(
            'error' => $errorMsg
            ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals(1, $crawler->filter('.alert-error')->count());
    }

    public function errorMessageProvider()
    {
        return array(
            array('invalidmail'),
            array('mailserver'),
            array('noaccount'),
            array('mail'),
            array('token'),
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::displayForgotPasswordForm
     * @dataProvider badPasswordMsgProvider
     */
    public function testGetForgotPasswordBadPassword($msg)
    {
        $this->client->request('GET', '/login/forgot-password/', array(
            'pass-error' => $msg,
        ));

        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function badPasswordMsgProvider()
    {
        return array(
            array('pass-match'),
            array('pass-short'),
            array('pass-invalid'),
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::displayRegisterForm
     * @covers \Alchemy\Phrasea\Controller\Root\Login::getRegisterFieldConfiguration
     * @dataProvider fieldErrorProvider
     */
    public function testGetRegister($error)
    {
        $this->client->request('GET', '/login/register/', array(
            'needed' => array(
                'field_name' => $error,
            )
        ));

        /**
         * @todo change this
         */
        if ( ! \login::register_enabled(self::$application)) {
            $this->assertTrue($this->client->getResponse()->isRedirect());
        } else {
            $this->assertTrue($this->client->getResponse()->isOk());
        }
    }

    public function fieldErrorProvider()
    {
        return array(
            array('required-field'),
            array('pass-match'),
            array('pass-short'),
            array('pass-invalid'),
            array('email-invalid'),
            array('login-short'),
            array('login-mail-exists'),
            array('user-mail-exists'),
            array('no-collections'),
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::register
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testPostRegisterBadRequest()
    {
        $this->client->request('POST', '/login/register/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::register
     * @dataProvider parametersProvider
     */
    public function testPostRegisterbadArguments($parameters)
    {
        $this->client->request('POST', '/login/register/', $parameters);

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    public function parametersProvider()
    {
        return array(
            array(array(//required field
                    "form_login"            => '',
                    "form_password"         => 'password',
                    "form_password_confirm" => 'password',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@email.com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            )),
            array(array(//password mismatch
                    "form_login"            => 'login',
                    "form_password"         => 'password',
                    "form_password_confirm" => 'passwordmismatch',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@email.com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            )),
            array(array(//password tooshort
                    "form_login"            => 'login',
                    "form_password"         => 'min',
                    "form_password_confirm" => 'min',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@email.com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            )),
            array(array(//password invalid
                    "form_login"            => 'login',
                    "form_password"         => 'invalid pass word',
                    "form_password_confirm" => 'invalid pass word',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@email.com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            )),
            array(array(//email invalid
                    "form_login"            => 'login',
                    "form_password"         => 'password',
                    "form_password_confirm" => 'password',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            )),
            array(array(//login exists
                    "form_login"            => 'test_phpunit',
                    "form_password"         => 'invalid pass word',
                    "form_password_confirm" => 'invalid pass word',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@email.com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            )),
            array(array(//mails exists
                    "form_login"            => 'login',
                    "form_password"         => 'invalid pass word',
                    "form_password_confirm" => 'noone@example.com',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@email.com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            )),
            array(array(//no demands
                    "form_login"            => 'login',
                    "form_password"         => 'invalid pass word',
                    "form_password_confirm" => 'email@email.com',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@email.com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            ))
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::register
     */
    public function testPostRegister()
    {
        $bases = array();

        foreach (self::$application['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $bases[] = $collection->get_base_id();
            }
        }

        $login = \random::generatePassword();
        $email = $login . '@google.com';

        $this->client->request('POST', '/login/register/', array(
            "form_login"            => $login,
            "form_password"         => 'password',
            "form_password_confirm" => 'password',
            "form_gender"           => 'M',
            "form_lastname"         => 'lastname',
            "form_firstname"        => 'firstname',
            "form_email"            => $email,
            "form_job"              => 'job',
            "form_company"          => 'company',
            "form_activity"         => 'activity',
            "form_phone"            => 'phone',
            "form_fax"              => 'fax',
            "form_address"          => 'adress',
            "form_zip"              => 'zip',
            "form_geonameid"        => 'geoname_id',
            "demand"                => $bases
        ));

        if ( ! $userId = \User_Adapter::get_usr_id_from_login(self::$application, $login)) {
            $this->fail('User not created');
        }

        $user = new User_Adapter((int) $userId, self::$application);

        $user->delete();

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertEquals('/login/?notice=mail-sent', $this->client->getResponse()->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::logout
     */
    public function testGetLogout()
    {
        $this->assertTrue(self::$application->isAuthenticated());
        $this->client->request('GET', '/login/logout/', array('app' => 'prod'));
        $this->assertFalse(self::$application->isAuthenticated());

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::sendConfirmMail
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testSendConfirmMailBadRequest()
    {
        $this->client->request('GET', '/login/send-mail-confirm/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::sendConfirmMail
     */
    public function testSendConfirmMail()
    {
        $this->client->request('GET', '/login/send-mail-confirm/', array('usr_id' => self::$user->get_id()));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?notice=mail-sent', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::sendConfirmMail
     */
    public function testSendConfirmMailWrongUser()
    {
        $this->client->request('GET', '/login/send-mail-confirm/', array('usr_id' => 0));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?error=user-not-found', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testAuthenticate()
    {
        self::$application['phraseanet.appbox']->get_session()->logout();
        $password = \random::generatePassword();
        self::$application['phraseanet.user']->set_password($password);
        $this->client->request('POST', '/login/authenticate/', array(
            'login' => self::$user->get_login(),
            'pwd'   => $password
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertTrue(self::$application->isAuthenticated());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testBadAuthenticate()
    {
        self::$application['phraseanet.appbox']->get_session()->logout();
        $this->client->request('POST', '/login/authenticate/', array(
            'login' => self::$user->get_login(),
            'pwd'   => 'test'
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertRegexp('/error=auth/', $this->client->getResponse()->headers->get('location'));
        $this->assertFalse(self::$application->isAuthenticated());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testMailLockedAuthenticate()
    {
        self::$application['phraseanet.appbox']->get_session()->logout();
        $password = \random::generatePassword();
        self::$user->set_mail_locked(true);
        $this->client->request('POST', '/login/authenticate/', array(
            'login' => self::$user->get_login(),
            'pwd'   => $password
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertRegexp('/error=mail-not-confirmed/', $this->client->getResponse()->headers->get('location'));
        $this->assertFalse(self::$application->isAuthenticated());
        self::$user->set_mail_locked(false);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testAuthenticateUnavailable()
    {
        self::$application['phraseanet.appbox']->get_session()->logout();
        $password = \random::generatePassword();
        self::$application['phraseanet.registry']->set('GV_maintenance', true , \registry::TYPE_BOOLEAN);

        $this->client = new Client(self::$application, array());

        $this->client->request('POST', '/login/authenticate/', array(
            'login' => self::$user->get_login(),
            'pwd'   => $password
        ));
        self::$application['phraseanet.registry']->set('GV_maintenance', false, \registry::TYPE_BOOLEAN);
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertRegexp('/error=maintenance/', $this->client->getResponse()->headers->get('location'));
        $this->assertFalse(self::$application->isAuthenticated());

    }

    /**
     * Delete inscription demand made by the current authenticathed user
     * @return void
     */
    private function deleteRequest()
    {
        $sql = "DELETE FROM demand WHERE usr_id = :usr_id";
        $stmt = self::$application['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => self::$user->get_id()));
        $stmt->closeCursor();
    }

    /**
     * Generate a new valid email adress
     * @return string
     */
    private function generateEmail()
    {
        return \random::generatePassword() . '_email@email.com';
    }
}
