<?php

namespace Alchemy\Tests\Phrasea\Controller\Root;

use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class LoginTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    public static $demands;
    public static $login;
    public static $email;

    public function setUp()
    {
        parent::setUp();

        if (null === self::$demands) {
            self::$demands = array(self::$DI['collection']->get_coll_id());
        }
        if (null === self::$login) {
            self::$login = self::$DI['user']->get_login();
        }
        if (null === self::$email) {
            self::$email = self::$DI['user']->get_email();
        }
    }

    public function testLoginAlreadyAthenticated()
    {
        self::$DI['client']->request('GET', '/login/');
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/prod/', $response->headers->get('location'));
    }

    public function testLoginRedirectPostLog()
    {
        self::$DI['app']['authentication']->closeAccount();

        self::$DI['client']->request('GET', '/login/', array('postlog'  => '1', 'redirect' => 'prod'));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/logout/?redirect=prod', $response->headers->get('location'));
    }

    /**
     * @dataProvider provideFlashMessages
     */
    public function testLoginError($type, $message)
    {
        self::$DI['app']->addFlash($type, $message);
        self::$DI['app']['authentication']->closeAccount();

        $crawler = self::$DI['client']->request('GET', '/login/');

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertAngularFlashMessage($crawler, $type, 1, $message);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailNoCode()
    {
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/login/register-confirm/');
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'error', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailWrongCode()
    {
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/login/register-confirm/', array(
            'code'    => '34dT0k3n'
        ));
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'error', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailUserNotFound()
    {
        self::$DI['app']['authentication']->closeAccount();
        $email = $this->generateEmail();
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_EMAIL, 0, null, $email);
        self::$DI['client']->request('GET', '/login/register-confirm/', array(
            'code'    => $token
        ));
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'error', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailUnlocked()
    {
        self::$DI['app']['authentication']->closeAccount();
        $email = $this->generateEmail();
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_EMAIL, self::$DI['user']->get_id(), null, $email);

        self::$DI['user']->set_mail_locked(false);

        self::$DI['client']->request('GET', '/login/register-confirm/', array('code'    => $token));
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'info', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMail()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationRegistered');

        self::$DI['app']['authentication']->closeAccount();
        $email = $this->generateEmail();
        $appboxRegister = new \appbox_register(self::$DI['app']['phraseanet.appbox']);
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_EMAIL, self::$DI['user']->get_id(), null, $email);

        self::$DI['user']->set_mail_locked(true);
        $this->deleteRequest();
        $appboxRegister->add_request(self::$DI['user'], self::$DI['collection']);
        self::$DI['client']->request('GET', '/login/register-confirm/', array('code'    => $token));
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'success', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
        $this->assertFalse(self::$DI['user']->get_mail_locked());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailNoCollAwait()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationUnregistered');

        $user = \User_Adapter::create(self::$DI['app'], 'test'.mt_rand(), \random::generatePassword(), 'email-random'.mt_rand().'@phraseanet.com', false);

        self::$DI['app']['authentication']->closeAccount();
        $email = $this->generateEmail();
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_EMAIL, $user->get_id(), null, $email);

        $user->set_mail_locked(true);

        $this->deleteRequest();

        self::$DI['client']->request('GET', '/login/register-confirm/', array('code'    => $token));
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'info', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
        $this->assertFalse(self::$DI['user']->get_mail_locked());
        $user->delete();
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordInvalidEmail()
    {
        self::$DI['app']['authentication']->closeAccount();
        $crawler = self::$DI['client']->request('POST', '/login/forgot-password/', array(
            'email'    => 'invalid.email.com',
            '_token'   => 'token',
        ));
        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isRedirect());

        $this->assertFormError($crawler, 1);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordUnknowEmail()
    {
        self::$DI['app']['authentication']->closeAccount();
        $crawler = self::$DI['client']->request('POST', '/login/forgot-password/', array(
            'email'   => 'invalid_email@test.com',
            '_token'  => 'token',
        ));
        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isRedirect());
        $this->assertAngularFlashMessage($crawler, 'error', 1);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordMail()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate');

        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('POST', '/login/forgot-password/', array(
            'email'    => self::$DI['user']->get_email(),
            '_token'   => 'token',
        ));
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/forgot-password/', $response->headers->get('location'));
        $this->assertFlashMessagePopulated(self::$DI['app'], 'info', 1);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordBadArguments()
    {
        self::$DI['app']['authentication']->closeAccount();
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_PASSWORD, self::$DI['user']->get_id());
        $crawler = self::$DI['client']->request('POST', '/login/renew-password/', array(
            'token'           => $token,
            '_token'          => 'token',
            'password'        => 'password',
            'passwordConfirm' => 'not identical'
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isRedirect());
        $this->assertAngularFlashMessage($crawler, 'error', 1);
    }

    public function testRenewPasswordBadToken()
    {
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('POST', '/login/renew-password/', array(
            'token'           => 'badToken',
            '_token'          => 'token',
            'password'        => 'password',
            'passwordConfirm' => 'password'
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testRenewPasswordBadTokenWheneverItsAuthenticated()
    {
        self::$DI['client']->request('POST', '/login/renew-password/', array(
            'token'           => 'badToken',
            '_token'          => 'token',
            'password'        => 'password',
            'passwordConfirm' => 'password'
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/prod/', $response->headers->get('location'));
    }

    public function testRenewPasswordNoToken()
    {
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('POST', '/login/renew-password/', array(
            '_token'          => 'token',
            'password'        => 'password',
            'passwordConfirm' => 'password'
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testRenewPasswordNoTokenWheneverItsAuthenticated()
    {
        self::$DI['client']->request('POST', '/login/renew-password/', array(
            '_token'          => 'token',
            'password'        => 'password',
            'passwordConfirm' => 'password'
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/prod/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPassword()
    {
        self::$DI['app']['authentication']->closeAccount();
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_PASSWORD, self::$DI['user']->get_id());

        self::$DI['client']->request('POST', '/login/renew-password/', array(
            'token'                 => $token,
            '_token'                 => 'token',
            'password'         => 'password',
            'passwordConfirm' => 'password'
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/', $response->headers->get('location'));

        $this->assertFlashMessagePopulated(self::$DI['app'], 'success', 1);
    }

    /**
     * @dataProvider provideFlashMessages
     */
    public function testRenewPasswordPageShowsFlashMessages($type, $message)
    {
        self::$DI['app']->addFlash($type, $message);

        self::$DI['app']['authentication']->closeAccount();
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_PASSWORD, self::$DI['user']->get_id());

        $crawler = self::$DI['client']->request('GET', '/login/renew-password/', array(
            'token' => $token
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        $this->assertAngularFlashMessage($crawler, $type, 1, $message);
    }

    public function testForgotPasswordGet()
    {
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/login/forgot-password/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testForgotPasswordWhenAuthenticatedMustReturnToProd()
    {
        self::$DI['client']->request('GET', '/login/forgot-password/');

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/prod/', $response->headers->get('location'));
    }

    public function testForgotPasswordInvalidEmail()
    {
        self::$DI['app']['authentication']->closeAccount();
        $crawler = self::$DI['client']->request('POST', '/login/forgot-password/', array(
            '_token' => 'token',
            'email'  => 'invalid.email',
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertFalse($response->isRedirect());

        $this->assertFormError($crawler, 1);
    }

    public function testForgotPasswordWrongEmail()
    {
        self::$DI['app']['authentication']->closeAccount();
        $crawler = self::$DI['client']->request('POST', '/login/forgot-password/', array(
            '_token' => 'token',
            'email'  => 'invalid@email.com',
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertFalse($response->isRedirect());

        $this->assertAngularFlashMessage($crawler, 'error', 1);
    }

    public function testForgotPasswordSubmission()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate');

        self::$DI['app']['authentication']->closeAccount();
        $crawler = self::$DI['client']->request('POST', '/login/forgot-password/', array(
            '_token' => 'token',
            'email'  => self::$DI['user']->get_email(),
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());

        $this->assertFlashMessagePopulated(self::$DI['app'], 'info', 1);
    }

    /**
     * @dataProvider provideFlashMessages
     */
    public function testGetRegister($type, $message)
    {
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['app']->addFlash($type, $message);
        $crawler = self::$DI['client']->request('GET', '/login/register-classic');

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
        $this->assertAngularFlashMessage($crawler, $type, 1, $message);
    }

    /**
     * @dataProvider provideInvalidRegistrationData
     */
    public function testPostRegisterbadArguments($parameters, $extraParameters, $errors)
    {
        self::$DI['app']['registration.fields'] = $extraParameters;

        self::$DI['app']['authentication']->closeAccount();

        $parameters = array_merge(array('_token' => 'token'), $parameters);
        foreach ($parameters as $key => $parameter) {
            if ('collections' === $key && null === $parameter) {
                $parameters[$key] = self::$demands;
            }
            if ('login' === $key && null === $parameter) {
                $parameters[$key] = self::$login;
            }
            if ('email' === $key && null === $parameter) {
                $parameters[$key] = self::$email;
            }
        }
        $crawler = self::$DI['client']->request('POST', '/login/register-classic', $parameters);

        $this->assertFalse(self::$DI['client']->getResponse()->isRedirect());
        $this->assertFormOrAngularError($crawler, $errors);
    }

    public function testPostRegisterWithoutParams()
    {
        self::$DI['app']['authentication']->closeAccount();
        $crawler = self::$DI['client']->request('POST', '/login/register-classic');

        $this->assertFalse(self::$DI['client']->getResponse()->isRedirect());
        $this->assertFormOrAngularError($crawler, 7);
    }

    public function provideInvalidRegistrationData()
    {
        return array(
            array(array(//required field missing
                    "password"        => 'password',
                    "passwordConfirm" => 'password',
                    "accept-tou"      => '1',
                    "collections"     => null,
                ), array(), 1),
            array(array(//required extra-field missing
                    "password"        => 'password',
                    "passwordConfirm" => 'password',
                    "email"           => $this->generateEmail(),
                    "accept-tou"      => '1',
                    "collections"     => null
                ), array(
                    array(
                        'name'     => 'login',
                        'required' => true,
                    )
                ), 1),
            array(array(//password mismatch
                    "password"        => 'password',
                    "passwordConfirm" => 'passwordmismatch',
                    "email"           => $this->generateEmail(),
                    "accept-tou"      => '1',
                    "collections"     => null
                ), array(), 1),
            array(array(//password tooshort
                    "password"        => 'min',
                    "passwordConfirm" => 'min',
                    "email"           => $this->generateEmail(),
                    "accept-tou"      => '1',
                    "collections"     => null
                ), array(), 2),
            array(array(//email invalid
                    "password"        => 'password',
                    "passwordConfirm" => 'password',
                    "email"           => 'invalid.email',
                    "accept-tou"      => '1',
                    "collections"     => null
                ), array(), 1),
            array(array(//login exists
                    "login"           => null,
                    "password"        => 'password',
                    "passwordConfirm" => 'password',
                    "email"           => $this->generateEmail(),
                    "accept-tou"      => '1',
                    "collections"     => null
                ), array(
                    array(
                        'name'     => 'login',
                        'required' => true,
                    )
                ), 1),
            array(array(//mails exists
                    "password"        => 'password',
                    "passwordConfirm" => 'password',
                    "email"           => null,
                    "accept-tou"      => '1',
                    "collections"     => null
                ), array(), 1),
            array(array(//tou declined
                    "password"        => 'password',
                    "passwordConfirm" => 'password',
                    "email"           => $this->generateEmail(),
                    "collections"     => null
                ), array(), 1),
            array(array(//no demands
                    "password"        => 'password',
                    "passwordConfirm" => 'password',
                    "email"           => $this->generateEmail(),
                    "accept-tou"      => '1',
                    "collections"     => array()
                ), array(), 1)
        );
    }

    public function provideRegistrationData()
    {
        return array(
            array(array(//required field missing
                    "password"        => 'password',
                    "passwordConfirm" => 'password',
                    "email"           => $this->generateEmail(),
                    "accept-tou"      => '1',
                    "collections"     => null,
                ), array()),
            array(array(//extra-field is not required
                    "password"        => 'password',
                    "passwordConfirm" => 'password',
                    "email"           => $this->generateEmail(),
                    "accept-tou"      => '1',
                    "collections"     => null
                ), array(
                    array(
                        'name'     => 'login',
                        'required' => false,
                    )
                )),
            array(array(//extra-fields are not required
                    "password"        => 'password',
                    "passwordConfirm" => 'password',
                    "email"           => $this->generateEmail(),
                    "accept-tou"      => '1',
                    "collections"     => null
                ), array(
                    array(
                        'name' => 'login',
                        'required' => false,
                    ),
                    array(
                        'name' => 'gender',
                        'required' => false,
                    ),
                    array(
                        'name' => 'firstname',
                        'required' => false,
                    ),
                    array(
                        'name' => 'lastname',
                        'required' => false,
                    ),
                    array(
                        'name' => 'address',
                        'required' => false,
                    ),
                    array(
                        'name' => 'zipcode',
                        'required' => false,
                    ),
                    array(
                        'name' => 'geonameid',
                        'required' => false,
                    ),
                    array(
                        'name' => 'position',
                        'required' => false,
                    ),
                    array(
                        'name' => 'company',
                        'required' => false,
                    ),
                    array(
                        'name' => 'job',
                        'required' => false,
                    ),
                    array(
                        'name' => 'tel',
                        'required' => false,
                    ),
                    array(
                        'name' => 'fax',
                        'required' => false,
                    )
                )),
            array(array(//extra-fields are required
                    "password"        => 'password',
                    "passwordConfirm" => 'password',
                    "email"           => $this->generateEmail(),
                    "accept-tou"      => '1',
                    "collections"     => null,
                    "login" => 'login-'.\random::generatePassword(),
                    "gender" => '1',
                    "firstname" => 'romain',
                    "lastname" => 'neutron',
                    "address" => '30 place saint georges',
                    "zipcode" => 'zip',
                    "geonameid" => 123456,
                    "position" => 'position',
                    "company" => 'company',
                    "job" => 'job',
                    "tel" => 'tel',
                    "fax" => 'fax',
                ), array(
                    array(
                        'name' => 'login',
                        'required' => true,
                    ),
                    array(
                        'name' => 'gender',
                        'required' => true,
                    ),
                    array(
                        'name' => 'firstname',
                        'required' => true,
                    ),
                    array(
                        'name' => 'lastname',
                        'required' => true,
                    ),
                    array(
                        'name' => 'address',
                        'required' => true,
                    ),
                    array(
                        'name' => 'zipcode',
                        'required' => true,
                    ),
                    array(
                        'name' => 'geonameid',
                        'required' => true,
                    ),
                    array(
                        'name' => 'position',
                        'required' => true,
                    ),
                    array(
                        'name' => 'company',
                        'required' => true,
                    ),
                    array(
                        'name' => 'job',
                        'required' => true,
                    ),
                    array(
                        'name' => 'tel',
                        'required' => true,
                    ),
                    array(
                        'name' => 'fax',
                        'required' => true,
                    )
                )),
        );
    }

    /**
     * @dataProvider provideRegistrationData
     */
    public function testPostRegister($parameters, $extraParameters)
    {
        self::$DI['app']['registration.fields'] = $extraParameters;

        self::$DI['app']['authentication']->closeAccount();

        $emails = array(
            'Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation'=>0,
            'Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered'=>0,
            'Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered'=>0,
        );

        $this->mockNotificationsDeliverer($emails);

        $parameters = array_merge(array('_token' => 'token'), $parameters);
        foreach ($parameters as $key => $parameter) {
            if ('collections' === $key && null === $parameter) {
                $parameters[$key] = self::$demands;
            }
            if ('login' === $key && null === $parameter) {
                $parameters[$key] = self::$login;
            }
            if ('email' === $key && null === $parameter) {
                $parameters[$key] = self::$email;
            }
        }

        self::$DI['client']->request('POST', '/login/register-classic', $parameters);

        if (false === $userId = \User_Adapter::get_usr_id_from_email(self::$DI['app'], $parameters['email'])) {
            $this->fail('User not created');
        }

        $user = new \User_Adapter((int) $userId, self::$DI['app']);

        $user->delete();

        $this->assertGreaterThan(0, $emails['Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered']);
        $this->assertEquals(1, $emails['Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation']);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'info', 1);
        $this->assertEquals('/login/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::logout
     */
    public function testGetLogout()
    {
        $this->assertTrue(self::$DI['app']['authentication']->isAuthenticated());
        self::$DI['client']->request('GET', '/login/logout/', array('app' => 'prod'));
        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::sendConfirmMail
     */
    public function testSendConfirmMailBadRequest()
    {
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/login/send-mail-confirm/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::sendConfirmMail
     */
    public function testSendConfirmMail()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation');

        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/login/send-mail-confirm/', array('usr_id' => self::$DI['user']->get_id()));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'success', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::sendConfirmMail
     */
    public function testSendConfirmMailWrongUser()
    {
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/login/send-mail-confirm/', array('usr_id' => 0));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'error', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testAuthenticate()
    {
        $password = \random::generatePassword();

        $login = self::$DI['app']['authentication']->getUser()->get_login();
        self::$DI['app']['authentication']->getUser()->set_password($password);
        self::$DI['app']['authentication']->getUser()->set_mail_locked(false);

        self::$DI['app']['authentication']->closeAccount();

        self::$DI['client'] = new Client(self::$DI['app'], array());
        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);
        self::$DI['client']->request('POST', '/login/authenticate/', array(
            'login' => $login,
            'password'   => $password,
            '_token' => 'token',
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegExp('/^\/prod\/$/', self::$DI['client']->getResponse()->headers->get('Location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testAuthenticateCheckRedirect()
    {
        $password = \random::generatePassword();

        $login = self::$DI['app']['authentication']->getUser()->get_login();
        self::$DI['app']['authentication']->getUser()->set_password($password);

        self::$DI['app']['authentication']->closeAccount();

        self::$DI['client'] = new Client(self::$DI['app'], array());
        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);
        self::$DI['client']->request('POST', '/login/authenticate/', array(
            'login'    => $login,
            'password' => $password,
            '_token'   => 'token',
            'redirect' => '/admin'
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegExp('/admin/', self::$DI['client']->getResponse()->headers->get('Location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testGuestAuthenticate()
    {
        $usr_id = \User_Adapter::get_usr_id_from_login(self::$DI['app'], 'invite');

        $user = \User_Adapter::getInstance($usr_id, self::$DI['app']);

        $user->ACL()->give_access_to_base(array(self::$DI['collection']->get_base_id()));

        self::$DI['app']['authentication']->closeAccount();

        self::$DI['client'] = new Client(self::$DI['app'], array());
        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);
        self::$DI['client']->request('POST', '/login/authenticate/guest/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegExp('/^\/prod\/$/', self::$DI['client']->getResponse()->headers->get('Location'));

        $cookies = self::$DI['client']->getResponse()->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);

        $this->assertArrayHasKey('invite-usr-id', $cookies['']['/']);
        $this->assertInternalType('integer', $cookies['']['/']['invite-usr-id']->getValue());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testGuestAuthenticateWithGetMethod()
    {
        self::$DI['app']['authentication']->closeAccount();

        self::$DI['client'] = new Client(self::$DI['app'], array());
        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);
        self::$DI['client']->request('GET', '/login/authenticate/guest/');

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegExp('/^\/prod\/$/', self::$DI['client']->getResponse()->headers->get('Location'));

        $cookies = self::$DI['client']->getResponse()->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);

        $this->assertArrayHasKey('invite-usr-id', $cookies['']['/']);
        $this->assertInternalType('integer', $cookies['']['/']['invite-usr-id']->getValue());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testBadAuthenticate()
    {
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('POST', '/login/authenticate/', array(
            'login' => self::$DI['user']->get_login(),
            'password'   => 'test',
            '_token' => 'token',
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertTrue(self::$DI['app']['session']->getFlashBag()->has('error'));
        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testBadAuthenticateCheckRedirect()
    {
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('POST', '/login/authenticate/', array(
            'login'     => self::$DI['user']->get_login(),
            'password'       => 'test',
            '_token' => 'token',
            'redirect'  => '/prod'
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegExp('/redirect=prod/', self::$DI['client']->getResponse()->headers->get('Location'));
        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testMailLockedAuthenticate()
    {
        self::$DI['app']['authentication']->closeAccount();
        $password = \random::generatePassword();
        self::$DI['user']->set_mail_locked(true);
        self::$DI['client']->request('POST', '/login/authenticate/', array(
            'login' => self::$DI['user']->get_login(),
            'password'   => $password,
            '_token' => 'token'
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegexp('/error=account-locked/', self::$DI['client']->getResponse()->headers->get('location'));
        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
        self::$DI['user']->set_mail_locked(false);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testAuthenticateUnavailable()
    {
        self::$DI['app']['authentication']->closeAccount();
        $password = \random::generatePassword();
        self::$DI['app']['phraseanet.registry']->set('GV_maintenance', true , \registry::TYPE_BOOLEAN);

        self::$DI['client'] = new Client(self::$DI['app'], array());

        self::$DI['client']->request('POST', '/login/authenticate/', array(
            'login' => self::$DI['user']->get_login(),
            'password'   => $password,
            '_token' => 'token'
        ));
        self::$DI['app']['phraseanet.registry']->set('GV_maintenance', false, \registry::TYPE_BOOLEAN);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'warning', 1);
        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());

    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testMaintenanceOnLoginDoesNotRedirect()
    {
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['app']['phraseanet.registry']->set('GV_maintenance', true , \registry::TYPE_BOOLEAN);

        self::$DI['client'] = new Client(self::$DI['app'], array());

        self::$DI['client']->request('GET', '/login/');
        self::$DI['app']['phraseanet.registry']->set('GV_maintenance', false, \registry::TYPE_BOOLEAN);
        $this->assertFalse(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * Delete inscription demand made by the current authenticathed user
     * @return void
     */
    private function deleteRequest()
    {
        $sql = "DELETE FROM demand WHERE usr_id = :usr_id";
        $stmt = self::$DI['app']['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => self::$DI['user']->get_id()));
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
