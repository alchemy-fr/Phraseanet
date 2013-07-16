<?php

namespace Alchemy\Tests\Phrasea\Controller\Root;

use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Authentication\Provider\Token\Token;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;
use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Authentication\ProvidersCollection;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;

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
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['app']->addFlash($type, $message);

        $crawler = self::$DI['client']->request('GET', '/login/');

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertFlashMessage($crawler, $type, 1, $message);
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
        $this->assertFlashMessage($crawler, 'error', 1);
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
            'password'        => array('password' => 'password', 'confirm' => 'not_identical')
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isRedirect());
        $this->assertFormOrFlashError($crawler, 1);
    }

    public function testRenewPasswordBadToken()
    {
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('POST', '/login/renew-password/', array(
            'token'           => 'badToken',
            '_token'          => 'token',
            'password'        => array('password' => 'password', 'confirm' => 'password')
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testRenewPasswordBadTokenWheneverItsAuthenticated()
    {
        self::$DI['client']->request('POST', '/login/renew-password/', array(
            'token'           => 'badToken',
            '_token'          => 'token',
            'password'        => array('password' => 'password', 'confirm' => 'password')
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
            'password'        => array('password' => 'password', 'confirm' => 'password')
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testRenewPasswordNoTokenWheneverItsAuthenticated()
    {
        self::$DI['client']->request('POST', '/login/renew-password/', array(
            '_token'          => 'token',
            'password'        => array('password' => 'password', 'confirm' => 'password')
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
            'password'        => array('password' => 'password', 'confirm' => 'password')
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
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['app']->addFlash($type, $message);

        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_PASSWORD, self::$DI['user']->get_id());

        $crawler = self::$DI['client']->request('GET', '/login/renew-password/', array(
            'token' => $token
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        $this->assertFlashMessage($crawler, $type, 1, $message);
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

        $this->assertFlashMessage($crawler, 'error', 1);
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
        $crawler = self::$DI['client']->request('GET', '/login/register-classic/');

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
        $this->assertFlashMessage($crawler, $type, 1, $message);
    }

    public function testGetRegisterWithRegisterIdBindDataToForm()
    {
        self::$DI['app']['authentication']->closeAccount();

        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');

        self::$DI['app']['authentication.providers'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ProvidersCollection')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['authentication.providers']->expects($this->once())
            ->method('get')
            ->with($this->equalTo('provider-test'))
            ->will($this->returnValue($provider));

        $identity = $this->getMockBuilder('Alchemy\Phrasea\Authentication\Provider\Token\Identity')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($identity));

        $identity->expects($this->once())
            ->method('getEmail')
            ->will($this->returnValue('supermail@superprovider.com'));

        $crawler = self::$DI['client']->request('GET', '/login/register-classic/?providerId=provider-test');

        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals(1, $crawler->filterXPath("//input[@value='supermail@superprovider.com' and @name='email']")->count());
    }

    public function provideRegistrationRouteAndMethods()
    {
        return array(
            array('GET', '/login/register/'),
            array('GET', '/login/register-classic/'),
            array('POST', '/login/register-classic/'),
        );
    }

    /**
     * @dataProvider provideRegistrationRouteAndMethods
     */
    public function testGetPostRegisterWhenRegistrationDisabled($method, $route)
    {
        self::$DI['app']['registration.enabled'] = false;
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request($method, $route);
        $this->assertEquals(404, self::$DI['client']->getResponse()->getStatusCode());
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
        $crawler = self::$DI['client']->request('POST', '/login/register-classic/', $parameters);

        $this->assertFalse(self::$DI['client']->getResponse()->isRedirect());
        $this->assertFormOrFlashError($crawler, $errors);
    }

    public function testPostRegisterWithoutParams()
    {
        self::$DI['app']['authentication']->closeAccount();
        $crawler = self::$DI['client']->request('POST', '/login/register-classic/');

        $this->assertFalse(self::$DI['client']->getResponse()->isRedirect());
        $this->assertFormOrFlashError($crawler, 8);
    }

    public function provideInvalidRegistrationData()
    {
        return array(
            array(array(//required field missing
                    "password" => array(
                        'password' => 'password',
                        'confirm'  => 'password'
                    ),
                    "accept-tou"      => '1',
                    "collections"     => null,
                ), array(), 1),
            array(array(//required extra-field missing
                    "password" => array(
                        'password' => 'password',
                        'confirm'  => 'password'
                    ),
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
                    "password" => array(
                        'password' => 'password',
                        'confirm'  => 'passwordMismatch'
                    ),
                    "email"           => $this->generateEmail(),
                    "accept-tou"      => '1',
                    "collections"     => null
                ), array(), 1),
            array(array(//password tooshort
                    "password" => array(
                        'password' => 'min',
                        'confirm'  => 'min'
                    ),
                    "email"           => $this->generateEmail(),
                    "accept-tou"      => '1',
                    "collections"     => null
                ), array(), 1),
            array(array(//email invalid
                    "password" => array(
                        'password' => 'password',
                        'confirm'  => 'password'
                    ),
                    "email"           => 'invalid.email',
                    "accept-tou"      => '1',
                    "collections"     => null
                ), array(), 1),
            array(array(//login exists
                    "login"           => null,
                    "password" => array(
                        'password' => 'password',
                        'confirm'  => 'password'
                    ),
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
                    "password" => array(
                        'password' => 'password',
                        'confirm'  => 'password'
                    ),
                    "email"           => null,
                    "accept-tou"      => '1',
                    "collections"     => null
                ), array(), 1),
            array(array(//tou declined
                    "password" => array(
                        'password' => 'password',
                        'confirm'  => 'password'
                    ),
                    "email"           => $this->generateEmail(),
                    "collections"     => null
                ), array(), 1),
            array(array(//no demands
                    "password" => array(
                        'password' => 'password',
                        'confirm'  => 'password'
                    ),
                    "email"           => $this->generateEmail(),
                    "accept-tou"      => '1',
                    "collections"     => array()
                ), array(), 1)
        );
    }

    public function provideRegistrationData()
    {
        return array(
            array(array(
                    "password" => array(
                        'password' => 'password',
                        'confirm'  => 'password'
                    ),
                    "email"           => $this->generateEmail(),
                    "accept-tou"      => '1',
                    "collections"     => null,
                ), array()),
            array(array(
                    "password" => array(
                        'password' => 'password',
                        'confirm'  => 'password'
                    ),
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
                    "password" => array(
                        'password' => 'password',
                        'confirm'  => 'password'
                    ),
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
                    "password" => array(
                        'password' => 'password',
                        'confirm'  => 'password'
                    ),
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

    public function testPostRegisterWithProviderIdAndAlreadyBoundProvider()
    {
        self::$DI['app']['registration.fields'] = array();
        self::$DI['app']['authentication']->closeAccount();

        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $entity = $this->getMock('Entities\UsrAuthProvider');
        $entity->expects($this->any())
            ->method('getUser')
            ->with(self::$DI['app'])
            ->will($this->returnValue(self::$DI['user']));

        $token = new Token($provider, 42);
        $this->addUsrAuthDoctrineEntitySupport(42, $entity, true);

        $provider->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $parameters = array_merge(array('_token' => 'token'), array(
            "password" => array(
                'password' => 'password',
                'confirm'  => 'password'
            ),
            "email"           => $this->generateEmail(),
            "accept-tou"      => '1',
            "collections"     => null,
            "provider-id"     => 'provider-test',
        ));

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

        self::$DI['client']->request('POST', '/login/register-classic/', $parameters);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertEquals('/prod/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function testPostRegisterWithUnknownProvider()
    {
        self::$DI['app']['registration.fields'] = array();
        self::$DI['app']['authentication']->closeAccount();

        $parameters = array_merge(array('_token' => 'token'), array(
            "password" => array(
                'password' => 'password',
                'confirm'  => 'password'
            ),
            "email"           => $this->generateEmail(),
            "accept-tou"      => '1',
            "collections"     => null,
            "provider-id"     => 'provider-test',
        ));

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

        self::$DI['client']->request('POST', '/login/register-classic/', $parameters);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'error', 1);
        $this->assertEquals('/login/register/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function testPostRegisterWithProviderNotAuthenticated()
    {
        self::$DI['app']['registration.fields'] = array();
        self::$DI['app']['authentication']->closeAccount();

        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $provider->expects($this->any())
            ->method('getToken')
            ->will($this->throwException(new NotAuthenticatedException('Not authenticated')));

        $parameters = array_merge(array('_token' => 'token'), array(
           "password" => array(
                'password' => 'password',
                'confirm'  => 'password'
            ),
            "email"           => $this->generateEmail(),
            "accept-tou"      => '1',
            "collections"     => null,
            "provider-id"     => 'provider-test',
        ));

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

        self::$DI['client']->request('POST', '/login/register-classic/', $parameters);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'error', 1);
        $this->assertEquals('/login/register/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function testPostRegisterWithProviderId()
    {
        self::$DI['app']['registration.fields'] = array();
        self::$DI['app']['authentication']->closeAccount();

        $emails = array(
            'Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation'=>0,
            'Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered'=>0,
            'Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered'=>0,
        );

        $this->mockNotificationsDeliverer($emails);

        $parameters = array_merge(array('_token' => 'token'), array(
            "password" => array(
                'password' => 'password',
                'confirm'  => 'password'
            ),
            "email"           => $this->generateEmail(),
            "accept-tou"      => '1',
            "collections"     => null,
            "provider-id"     => 'provider-test',
        ));

        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $token = new Token($provider, 42);

        $provider->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

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

        self::$DI['client']->request('POST', '/login/register-classic/', $parameters);

        if (false === $userId = \User_Adapter::get_usr_id_from_email(self::$DI['app'], $parameters['email'])) {
            $this->fail('User not created');
        }

        $user = new \User_Adapter((int) $userId, self::$DI['app']);

        $ret = self::$DI['app']['EM']->getRepository('\Entities\UsrAuthProvider')
            ->findBy(array('usr_id' => $userId, 'provider' => 'provider-test'));
        $this->assertCount(1, $ret);

        $user->delete();

        $this->assertGreaterThan(0, $emails['Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered']);
        $this->assertEquals(1, $emails['Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation']);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'info', 1);
        $this->assertEquals('/login/', self::$DI['client']->getResponse()->headers->get('location'));
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

        self::$DI['client']->request('POST', '/login/register-classic/', $parameters);

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
     * @dataProvider provideEventNames
     */
    public function testAuthenticateTriggersEvents($eventName, $className, $context)
    {
        $password = \random::generatePassword();

        $login = self::$DI['app']['authentication']->getUser()->get_login();
        self::$DI['app']['authentication']->getUser()->set_password($password);
        self::$DI['app']['authentication']->getUser()->set_mail_locked(false);

        self::$DI['app']['authentication']->closeAccount();

        $preEvent = 0;
        $phpunit = $this;
        self::$DI['app']['dispatcher']->addListener($eventName, function ($event) use ($phpunit, &$preEvent, $className, $context) {
            $preEvent++;
            $phpunit->assertInstanceOf($className, $event);
            $phpunit->assertEquals($context, $event->getContext()->getContext());
        });

        self::$DI['client'] = new Client(self::$DI['app'], array());
        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);
        self::$DI['client']->request('POST', '/login/authenticate/', array(
            'login' => $login,
            'password'   => $password,
            '_token' => 'token',
        ));

        $this->assertEquals(1, $preEvent);
    }

    public function provideEventNames()
    {
        return array(
            array(PhraseaEvents::PRE_AUTHENTICATE, 'Alchemy\Phrasea\Core\Event\PreAuthenticate', Context::CONTEXT_NATIVE),
            array(PhraseaEvents::POST_AUTHENTICATE, 'Alchemy\Phrasea\Core\Event\PostAuthenticate', Context::CONTEXT_NATIVE),
        );
    }

    public function provideGuestEventNames()
    {
        return array(
            array(PhraseaEvents::PRE_AUTHENTICATE, 'Alchemy\Phrasea\Core\Event\PreAuthenticate', Context::CONTEXT_GUEST),
            array(PhraseaEvents::POST_AUTHENTICATE, 'Alchemy\Phrasea\Core\Event\PostAuthenticate', Context::CONTEXT_GUEST),
        );
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
     * @dataProvider provideGuestEventNames
     */
    public function testGuestAuthenticateTriggersEvents($eventName, $className, $context)
    {
        $preEvent = 0;
        $phpunit = $this;
        self::$DI['app']['dispatcher']->addListener($eventName, function ($event) use ($phpunit, &$preEvent, $className, $context) {
            $preEvent++;
            $phpunit->assertInstanceOf($className, $event);
            $phpunit->assertEquals($context, $event->getContext()->getContext());
        });

        $usr_id = \User_Adapter::get_usr_id_from_login(self::$DI['app'], 'invite');
        $user = \User_Adapter::getInstance($usr_id, self::$DI['app']);
        $user->ACL()->give_access_to_base(array(self::$DI['collection']->get_base_id()));

        self::$DI['app']['authentication']->closeAccount();

        self::$DI['client'] = new Client(self::$DI['app'], array());
        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);
        self::$DI['client']->request('POST', '/login/authenticate/guest/');

        $this->assertEquals(1, $preEvent);
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
        $this->assertEquals('/login/', self::$DI['client']->getResponse()->headers->get('location'));
        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
        self::$DI['user']->set_mail_locked(false);
    }

    public function testAuthenticateWithProvider()
    {
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');

        self::$DI['app']['authentication.providers'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ProvidersCollection')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['authentication.providers']->expects($this->once())
            ->method('get')
            ->with($this->equalTo('provider-test'))
            ->will($this->returnValue($provider));

        $parameters = array('key1' => 'value1', 'key2' => 'value2');

        $response = new Response();

        $provider->expects($this->once())
            ->method('authenticate')
            ->with($parameters)
            ->will($this->returnValue($response));

        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/login/provider/provider-test/authenticate/', $parameters);

        $this->assertSame($response, self::$DI['client']->getResponse());
    }

    /**
     * @dataProvider provideAuthProvidersRoutesAndMethods
     */
    public function testAuthenticateProviderWhileConnected($method, $route)
    {
        self::$DI['client']->request($method, $route);

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertSame('/prod/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function provideAuthProvidersRoutesAndMethods()
    {
        return array(
            array('GET', '/login/provider/provider-test/authenticate/'),
            array('GET', '/login/provider/provider-test/callback/'),
            array('GET', '/login/register-classic/?providerId=provider-test'),
        );
    }

    /**
     * @dataProvider provideAuthProvidersRoutesAndMethods
     */
    public function testAuthenticateWithInvalidProvider($method, $route)
    {
        self::$DI['app']['authentication.providers'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ProvidersCollection')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['authentication.providers']->expects($this->once())
            ->method('get')
            ->with($this->equalTo('provider-test'))
            ->will($this->throwException(new InvalidArgumentException('Provider not found')));

        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request($method, $route);

        $this->assertEquals(404, self::$DI['client']->getResponse()->getStatusCode());
    }

    private function addProvider($name, $provider)
    {
        self::$DI['app']['authentication.providers'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ProvidersCollection')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['authentication.providers']->expects($this->once())
            ->method('get')
            ->with($this->equalTo($name))
            ->will($this->returnValue($provider));

        $provider->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($name));
    }

    public function testAuthenticateProviderCallbackWithNotAuthenticatedException()
    {
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $provider->expects($this->once())
            ->method('onCallback')
            ->will($this->throwException(new NotAuthenticatedException('Not authenticated.')));

        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertSame('/login/', self::$DI['client']->getResponse()->headers->get('location'));

        $this->assertFlashMessagePopulated(self::$DI['app'], 'error', 1);
    }

    public function testAuthenticateProviderCallbackAlreadyBound()
    {
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $provider->expects($this->once())
            ->method('onCallback');

        $entity = $this->getMock('Entities\UsrAuthProvider');
        $entity->expects($this->any())
            ->method('getUser')
            ->with(self::$DI['app'])
            ->will($this->returnValue(self::$DI['user']));

        $token = new Token($provider, 42);
        $this->addUsrAuthDoctrineEntitySupport(42, $entity, true);

        $provider->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertSame('/prod/', self::$DI['client']->getResponse()->headers->get('location'));

        $this->assertTrue(self::$DI['app']['authentication']->isAuthenticated());
    }

    public function testAuthenticateProviderCallbackWithSuggestionBindProviderToUser()
    {
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $provider->expects($this->once())
            ->method('onCallback');

        $token = new Token($provider, 42);

        $provider->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        self::$DI['app']['authentication.suggestion-finder'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\SuggestionFinder')
            ->disableOriginalConstructor()
            ->getMock();

        $user = self::$DI['user'];

        self::$DI['app']['authentication.suggestion-finder']->expects($this->once())
            ->method('find')
            ->with($token)
            ->will($this->returnValue($user));

        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());

        $ret = self::$DI['app']['EM']->getRepository('\Entities\UsrAuthProvider')
            ->findBy(array('usr_id' => self::$DI['user']->get_id(), 'provider' => 'provider-test'));

        $this->assertCount(1, $ret);

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertSame('/prod/', self::$DI['client']->getResponse()->headers->get('location'));

        $this->assertTrue(self::$DI['app']['authentication']->isAuthenticated());
    }

    public function testAuthenticateProviderCallbackWithAccountCreatorEnabled()
    {
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $provider->expects($this->once())
            ->method('onCallback');

        $token = new Token($provider, 42);

        $provider->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        self::$DI['app']['authentication.suggestion-finder'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\SuggestionFinder')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['authentication.suggestion-finder']->expects($this->once())
            ->method('find')
            ->with($token)
            ->will($this->returnValue(null));

        $identity = $this->getMockBuilder('Alchemy\Phrasea\Authentication\Provider\Token\Identity')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue($identity));

        $provider->expects($this->once())
            ->method('getTemplates')
            ->will($this->returnValue(array()));

        $identity->expects($this->once())
            ->method('getEmail')
            ->will($this->returnValue('supermail@superprovider.com'));

        $createdUserId = \User_Adapter::get_usr_id_from_email(self::$DI['app'], 'supermail@superprovider.com');

        if (false === $createdUserId) {
            $random = self::$DI['app']['tokens']->generatePassword();
            $createdUser = \User_Adapter::create(self::$DI['app'], 'temporary-'.$random, $random, 'supermail@superprovider.com', false);
        } else {
            $createdUser = \User_Adapter::getInstance($createdUserId, self::$DI['app']);
        }

        self::$DI['app']['authentication.providers.account-creator'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\AccountCreator')
            ->disableOriginalConstructor()
            ->getMock();
        self::$DI['app']['authentication.providers.account-creator']->expects($this->once())
            ->method('create')
            ->with(self::$DI['app'], 42, 'supermail@superprovider.com', array())
            ->will($this->returnValue($createdUser));
        self::$DI['app']['authentication.providers.account-creator']->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));

        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());

        $ret = self::$DI['app']['EM']->getRepository('\Entities\UsrAuthProvider')
            ->findBy(array('usr_id' => $createdUser->get_id(), 'provider' => 'provider-test'));

        $this->assertCount(1, $ret);

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertSame('/prod/', self::$DI['client']->getResponse()->headers->get('location'));

        $this->assertTrue(self::$DI['app']['authentication']->isAuthenticated());

        $createdUser->delete();
    }

    public function testAuthenticateProviderCallbackWithRegistrationEnabled()
    {
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $provider->expects($this->once())
            ->method('onCallback');

        $token = new Token($provider, 42);

        $provider->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        self::$DI['app']['authentication.suggestion-finder'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\SuggestionFinder')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['authentication.suggestion-finder']->expects($this->once())
            ->method('find')
            ->with($token)
            ->will($this->returnValue(null));

        self::$DI['app']['authentication.providers.account-creator'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\AccountCreator')
            ->disableOriginalConstructor()
            ->getMock();
        self::$DI['app']['authentication.providers.account-creator']->expects($this->never())
            ->method('create');
        self::$DI['app']['authentication.providers.account-creator']->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(false));

        self::$DI['app']['registration.enabled'] = true;

        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertSame('/login/register-classic/?providerId=provider-test', self::$DI['client']->getResponse()->headers->get('location'));

        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
    }

    public function testAuthenticateProviderCallbackWithoutRegistrationEnabled()
    {
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $provider->expects($this->once())
            ->method('onCallback');

        $token = new Token($provider, 42);

        $provider->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        self::$DI['app']['authentication.suggestion-finder'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\SuggestionFinder')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['authentication.suggestion-finder']->expects($this->once())
            ->method('find')
            ->with($token)
            ->will($this->returnValue(null));

        self::$DI['app']['authentication.providers.account-creator'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\AccountCreator')
            ->disableOriginalConstructor()
            ->getMock();
        self::$DI['app']['authentication.providers.account-creator']->expects($this->never())
            ->method('create');
        self::$DI['app']['authentication.providers.account-creator']->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(false));

        self::$DI['app']['registration.enabled'] = false;

        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertSame('/login/', self::$DI['client']->getResponse()->headers->get('location'));

        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'error', 1);
    }

    public function testGetRegistrationFields()
    {
        $fields = array(
            'field' => array(
                'required' => false
            )
        );
        self::$DI['app']['registration.fields'] = $fields;

        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/login/registration-fields/');

        $this->assertSame(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertSame('application/json', self::$DI['client']->getResponse()->headers->get('content-type'));

        $this->assertEquals($fields, json_decode(self::$DI['client']->getResponse()->getContent(), true));
    }

    public function testRegisterRedirectsNoAuthProvidersAvailable()
    {
        self::$DI['app']['authentication']->closeAccount();

        self::$DI['app']['authentication.providers'] = new ProvidersCollection();

        self::$DI['client']->request('GET', '/login/register/');

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertSame('/login/register-classic/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function testRegisterDisplaysIfAuthProvidersAvailable()
    {
        self::$DI['app']['authentication']->closeAccount();

        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $provider->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('test-provider'));

        self::$DI['app']['authentication.providers'] = new ProvidersCollection();
        self::$DI['app']['authentication.providers']->register($provider);

        self::$DI['client']->request('GET', '/login/register/');

        $this->assertSame(200, self::$DI['client']->getResponse()->getStatusCode());
    }

    private function addUsrAuthDoctrineEntitySupport($id, $out, $participants = false)
    {
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository\UsrAuthProviderRepository')
            ->setMethods(array('findWithProviderAndId'))
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->any())
            ->method('findWithProviderAndId')
            ->with('provider-test', $id)
            ->will($this->returnValue($out));

        self::$DI['app']['EM'] = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['EM']->expects($this->at(0))
            ->method('getRepository')
            ->with('Entities\UsrAuthProvider')
            ->will($this->returnValue($repo));

        if ($participants) {
            $repo = $this->getMockBuilder('Repositories\ValidationParticipantRepository')
                ->disableOriginalConstructor()
                ->getMock();

            $repo->expects($this->once())
                ->method('findNotConfirmedAndNotRemindedParticipantsByExpireDate')
                ->will($this->returnValue(array()));

            self::$DI['app']['EM']->expects($this->at(1))
                ->method('getRepository')
                ->with('Entities\ValidationParticipant')
                ->will($this->returnValue($repo));
        }
    }

    private function mockSuggestionFinder()
    {
        self::$DI['app']['authentication.suggestion-finder'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\SuggestionFinder')
            ->disableOriginalConstructor()
            ->getMock();
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
