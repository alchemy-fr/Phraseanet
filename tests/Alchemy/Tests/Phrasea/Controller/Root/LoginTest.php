<?php

namespace Alchemy\Tests\Phrasea\Controller\Root;

use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Authentication\Provider\Token\Token;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;
use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Authentication\ProvidersCollection;
use Alchemy\Phrasea\Model\Entities\Registration;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;

class LoginTest extends \PhraseanetAuthenticatedWebTestCase
{
    public static $registrationCollections;
    public static $collections;
    public static $login;
    public static $email;
    public static $termsOfUse;

    public function setUp()
    {
        parent::setUp();

        if (null === self::$registrationCollections) {
            self::$registrationCollections = [self::$DI['collection']->get_coll_id()];
        }
        if (null === self::$collections) {
            self::$collections = [self::$DI['collection']->get_base_id()];

            $sxml = simplexml_load_string(self::$DI['collection']->get_prefs());
            $sxml->caninscript = 1;
            $dom = new \DOMDocument();
            $dom->loadXML($sxml->asXML());
            self::$DI['collection']->set_prefs($dom);
        }
        if (null === self::$login) {
            self::$login = self::$DI['user']->getLogin();
        }
        if (null === self::$email) {
            self::$email = self::$DI['user']->getEmail();
        }

        $this->enableRegistration();
    }

    public function tearDown()
    {
        $this->resetTOU();
        parent::tearDown();
    }

    public static function tearDownAfterClass()
    {
        self::$registrationCollections = self::$collections = self::$login = self::$email = self::$termsOfUse = null;
        parent::tearDownAfterClass();
    }

    public function testRegisterWithNoTou()
    {
        $this->logout(self::$DI['app']);
        $this->disableTOU();
        self::$DI['client']->followRedirects();
        $crawler = self::$DI['client']->request('GET', '/login/register-classic');
        $this->assertEquals(0, $crawler->filter('a[href="'.self::$DI['app']->path('login_cgus').'"]')->count());
    }

    public function testRegisterWithTou()
    {
        $this->logout(self::$DI['app']);
        $this->enableTOU();
        self::$DI['client']->followRedirects();
        $crawler = self::$DI['client']->request('GET', '/login/register-classic');
        $this->assertEquals(2, $crawler->filter('a[href="'.self::$DI['app']->path('login_cgus').'"]')->count());
    }

    public function testRegisterWithAutoSelect()
    {
        $this->logout(self::$DI['app']);
        $gvAutoSelectDb = !! self::$DI['app']['conf']->get(['registry', 'registration', 'auto-select-collections']);
        self::$DI['app']['conf']->set(['registry', 'registration', 'auto-select-collections'], false);
        $crawler = self::$DI['client']->request('GET', '/login/register-classic/');
        $this->assertEquals(1, $crawler->filter('select[name="collections[]"]')->count());
        self::$DI['app']['conf']->set(['registry', 'registration', 'auto-select-collections'], $gvAutoSelectDb);
    }

    public function testRegisterWithNoAutoSelect()
    {
        $this->logout(self::$DI['app']);
        $gvAutoSelectDb = !! self::$DI['app']['conf']->get(['registry', 'registration', 'auto-select-collections']);
        self::$DI['app']['conf']->set(['registry', 'registration', 'auto-select-collections'], true);
        $crawler = self::$DI['client']->request('GET', '/login/register-classic/');
        $this->assertEquals(0, $crawler->filter('select[name="collections[]"]')->count());
        self::$DI['app']['conf']->set(['registry', 'registration', 'auto-select-collections'], $gvAutoSelectDb);
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
        $this->logout(self::$DI['app']);

        self::$DI['client']->request('GET', '/login/', ['postlog'  => '1', 'redirect' => 'prod']);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/logout/?redirect=prod', $response->headers->get('location'));
    }

    /**
     * @dataProvider provideFlashMessages
     */
    public function testLoginError($type, $message)
    {
        $this->logout(self::$DI['app']);
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
        $this->logout(self::$DI['app']);
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
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('GET', '/login/register-confirm/', [
            'code'    => '34dT0k3n'
        ]);
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
        $this->logout(self::$DI['app']);
        $email = $this->generateEmail();
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_EMAIL, 0, null, $email);
        self::$DI['client']->request('GET', '/login/register-confirm/', [
            'code'    => $token
        ]);
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
        $this->logout(self::$DI['app']);
        $email = $this->generateEmail();
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_EMAIL, self::$DI['user']->getId(), null, $email);

        self::$DI['user']->setMailLocked(false);

        self::$DI['client']->request('GET', '/login/register-confirm/', ['code'    => $token]);
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'info', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    public function testRegisterConfirmMail()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationRegistered');
        $this->mockUserNotificationSettings('eventsmanager_notify_register');

        $this->logout(self::$DI['app']);
        $email = $this->generateEmail();
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_EMAIL, self::$DI['user']->getId(), null, $email);

        self::$DI['user']->setMailLocked(true);
        $this->deleteRequest();
        $registration = new Registration();
        $registration->setUser(self::$DI['user']);
        $registration->setBaseId(self::$DI['collection']->get_base_id());

        self::$DI['app']['EM']->persist($registration);
        self::$DI['app']['EM']->flush();

        self::$DI['client']->request('GET', '/login/register-confirm/', ['code'    => $token]);
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'success', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
        $this->assertFalse(self::$DI['user']->isMailLocked());
    }

    public function testRegisterConfirmMailNoCollAwait()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationUnregistered');
        $this->mockUserNotificationSettings('eventsmanager_notify_register');
        ;
        $this->logout(self::$DI['app']);
        $email = $this->generateEmail();
        $user = self::$DI['app']['manipulator.user']->createUser('test', 'test', $email);
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_EMAIL, $user->getId(), null, $email);

        $user->setMailLocked(true);

        $this->deleteRequest();

        self::$DI['client']->request('GET', '/login/register-confirm/', ['code'    => $token]);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'info', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
        $this->assertFalse(self::$DI['user']->isMailLocked());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordInvalidEmail()
    {
        $this->logout(self::$DI['app']);
        $crawler = self::$DI['client']->request('POST', '/login/forgot-password/', [
            'email'    => 'invalid.email.com',
            '_token'   => 'token',
        ]);
        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isRedirect());

        $this->assertFormError($crawler, 1);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordUnknowEmail()
    {
        $this->logout(self::$DI['app']);
        $crawler = self::$DI['client']->request('POST', '/login/forgot-password/', [
            'email'   => 'invalid_email@test.com',
            '_token'  => 'token',
        ]);
        $response = self::$DI['client']->getResponse();
        $this->assertFalse($response->isRedirect());
        $this->assertFlashMessage($crawler, 'error', 1);
    }

    public function testRenewPasswordMail()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate');
        $this->mockUserNotificationSettings('eventsmanager_notify_register');

        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/login/forgot-password/', [
            'email'    => self::$DI['user']->getEmail(),
            '_token'   => 'token',
        ]);
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
        $this->logout(self::$DI['app']);
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_PASSWORD, self::$DI['user']->getId());
        $crawler = self::$DI['client']->request('POST', '/login/renew-password/', [
            'token'           => $token,
            '_token'          => 'token',
            'password'        => ['password' => 'password', 'confirm' => 'not_identical']
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isRedirect());
        $this->assertFormOrFlashError($crawler, 1);
    }

    public function testRenewPasswordBadToken()
    {
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/login/renew-password/', [
            'token'           => 'badToken',
            '_token'          => 'token',
            'password'        => ['password' => 'password', 'confirm' => 'password']
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testRenewPasswordBadTokenWheneverItsAuthenticated()
    {
        self::$DI['client']->request('POST', '/login/renew-password/', [
            'token'           => 'badToken',
            '_token'          => 'token',
            'password'        => ['password' => 'password', 'confirm' => 'password']
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/prod/', $response->headers->get('location'));
    }

    public function testRenewPasswordNoToken()
    {
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/login/renew-password/', [
            '_token'          => 'token',
            'password'        => ['password' => 'password', 'confirm' => 'password']
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testRenewPasswordNoTokenWheneverItsAuthenticated()
    {
        self::$DI['client']->request('POST', '/login/renew-password/', [
            '_token'          => 'token',
            'password'        => ['password' => 'password', 'confirm' => 'password']
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/prod/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPassword()
    {
        $this->logout(self::$DI['app']);
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_PASSWORD, self::$DI['user']->getId());

        self::$DI['client']->request('POST', '/login/renew-password/', [
            'token'                 => $token,
            '_token'                 => 'token',
            'password'        => ['password' => 'password', 'confirm' => 'password']
        ]);

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
        $this->logout(self::$DI['app']);
        self::$DI['app']->addFlash($type, $message);

        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_PASSWORD, self::$DI['user']->getId());

        $crawler = self::$DI['client']->request('GET', '/login/renew-password/', [
            'token' => $token
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        $this->assertFlashMessage($crawler, $type, 1, $message);
    }

    public function testForgotPasswordGet()
    {
        $this->logout(self::$DI['app']);
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
        $this->logout(self::$DI['app']);
        $crawler = self::$DI['client']->request('POST', '/login/forgot-password/', [
            '_token' => 'token',
            'email'  => 'invalid.email',
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertFalse($response->isRedirect());

        $this->assertFormError($crawler, 1);
    }

    public function testForgotPasswordWrongEmail()
    {
        $this->logout(self::$DI['app']);
        $crawler = self::$DI['client']->request('POST', '/login/forgot-password/', [
            '_token' => 'token',
            'email'  => 'invalid@email.com',
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertFalse($response->isRedirect());

        $this->assertFlashMessage($crawler, 'error', 1);
    }

    public function testForgotPasswordSubmission()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate');
        $this->mockUserNotificationSettings('eventsmanager_notify_register');

        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/login/forgot-password/', [
            '_token' => 'token',
            'email'  => self::$DI['user']->getEmail(),
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());

        $this->assertFlashMessagePopulated(self::$DI['app'], 'info', 1);
    }

    /**
     * @dataProvider provideFlashMessages
     */
    public function testGetRegister($type, $message)
    {
        $this->logout(self::$DI['app']);
        self::$DI['app']->addFlash($type, $message);
        $crawler = self::$DI['client']->request('GET', '/login/register-classic/');

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
        $this->assertFlashMessage($crawler, $type, 1, $message);
    }

    public function testGetRegisterWithRegisterIdBindDataToForm()
    {
        $this->logout(self::$DI['app']);

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
        return [
            ['GET', '/login/register/'],
            ['GET', '/login/register-classic/'],
            ['POST', '/login/register-classic/'],
        ];
    }

    /**
     * @dataProvider provideRegistrationRouteAndMethods
     */
    public function testGetPostRegisterWhenRegistrationDisabled($method, $route)
    {
        $this->disableRegistration();
        $this->logout(self::$DI['app']);
        self::$DI['client']->request($method, $route);
        $this->assertEquals(404, self::$DI['client']->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider provideInvalidRegistrationData
     */
    public function testPostRegisterBadArguments($parameters, $extraParameters, $errors)
    {
        $this->enableTOU();
        self::$DI['app']['registration.enabled'] = true;
        self::$DI['app']['registration.fields'] = $extraParameters;

        $this->logout(self::$DI['app']);

        $parameters = array_merge(['_token' => 'token'], $parameters);
        foreach ($parameters as $key => $parameter) {
            if ('collections' === $key && null === $parameter) {
                $parameters[$key] = self::$registrationCollections;
            }
            if ('login' === $key && null === $parameter) {
                $parameters[$key] = self::$login;
            }
            if ('email' === $key && null === $parameter) {
                $parameters[$key] = self::$email;
            }
        }

        if (self::$DI['app']['conf']->get(['registry', 'registration', 'auto-select-collections'])) {
            unset($parameters['collections']);
        }

        $crawler = self::$DI['client']->request('POST', '/login/register-classic/', $parameters);

        $this->assertFalse(self::$DI['client']->getResponse()->isRedirect());
        $this->assertFormOrFlashError($crawler, $errors);
    }

    public function testPostRegisterWithoutParams()
    {
        $this->logout(self::$DI['app']);
        $this->disableTOU();
        $crawler = self::$DI['client']->request('POST', '/login/register-classic/');

        $this->assertFalse(self::$DI['client']->getResponse()->isRedirect());
        $this->assertFormOrFlashError($crawler, self::$DI['app']['conf']->get(['registry', 'registration', 'auto-select-collections']) ? 6 : 7);
    }

    public function provideInvalidRegistrationData()
    {
        return [
            [[//required field missing
                "password" => [
                    'password' => 'password',
                    'confirm'  => 'password'
                ],
                "collections"     => null,
                "accept-tou"      => '1'
            ], [], 1],
            [[//required extra-field missing
                "password" => [
                    'password' => 'password',
                    'confirm'  => 'password'
                ],
                "email"           => $this->generateEmail(),
                "collections"     => null,
                "accept-tou"      => '1'
            ], [
                [
                    'name'     => 'login',
                    'required' => true,
                ]
            ], 1],
            [[//password mismatch
                "password" => [
                    'password' => 'password',
                    'confirm'  => 'passwordMismatch'
                ],
                "email"           => $this->generateEmail(),
                "collections"     => null,
                "accept-tou"      => '1'
            ], [], 1],
            [[//password tooshort
                "password" => [
                    'password' => 'min',
                    'confirm'  => 'min'
                ],
                "email"           => $this->generateEmail(),
                "collections"     => null,
                "accept-tou"      => '1'
            ], [], 1],
            [[//email invalid
                "password" => [
                    'password' => 'password',
                    'confirm'  => 'password'
                ],
                "email"           => 'invalid.email',
                "collections"     => null,
                "accept-tou"      => '1'
            ], [], 1],
            [[//login exists
                "login"           => null,
                "password" => [
                    'password' => 'password',
                    'confirm'  => 'password'
                ],
                "email"           => $this->generateEmail(),
                "collections"     => null,
                "accept-tou"      => '1'
            ], [
                [
                    'name'     => 'login',
                    'required' => true,
                ]
            ], 1],
            [[//mails exists
                "password" => [
                    'password' => 'password',
                    'confirm'  => 'password'
                ],
                "email"           => null,
                "collections"     => null,
                "accept-tou"      => '1'
            ], [], 1],
            [[//tou declined
                "password" => [
                    'password' => 'password',
                    'confirm'  => 'password'
                ],
                "email"           => $this->generateEmail(),
                "collections"     => null
            ], [], 1]
        ];
    }

    public function provideRegistrationData()
    {
        return [
            [[
                "password" => [
                    'password' => 'password',
                    'confirm'  => 'password'
                ],
                "email"           => $this->generateEmail(),
                "collections"     => null,
            ], []],
            [[
                "password" => [
                    'password' => 'password',
                    'confirm'  => 'password'
                ],
                "email"           => $this->generateEmail(),
                "collections"     => null
            ], [
                [
                    'name'     => 'login',
                    'required' => false,
                ]
            ]],
            [[//extra-fields are not required
                "password" => [
                    'password' => 'password',
                    'confirm'  => 'password'
                ],
                "email"           => $this->generateEmail(),
                "collections"     => null
            ], [
                [
                    'name' => 'login',
                    'required' => false,
                ],
                [
                    'name' => 'gender',
                    'required' => false,
                ],
                [
                    'name' => 'firstname',
                    'required' => false,
                ],
                [
                    'name' => 'lastname',
                    'required' => false,
                ],
                [
                    'name' => 'address',
                    'required' => false,
                ],
                [
                    'name' => 'zipcode',
                    'required' => false,
                ],
                [
                    'name' => 'geonameid',
                    'required' => false,
                ],
                [
                    'name' => 'position',
                    'required' => false,
                ],
                [
                    'name' => 'company',
                    'required' => false,
                ],
                [
                    'name' => 'job',
                    'required' => false,
                ],
                [
                    'name' => 'tel',
                    'required' => false,
                ],
                [
                    'name' => 'fax',
                    'required' => false,
                ]
            ]],
            [[//extra-fields are required
                "password" => [
                    'password' => 'password',
                    'confirm'  => 'password'
                ],
                "email"           => $this->generateEmail(),
                "collections"     => null,
                "login" => 'login-'.\random::generatePassword(),
                "gender" => User::GENDER_MR,
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
            ], [
                [
                    'name' => 'login',
                    'required' => true,
                ],
                [
                    'name' => 'gender',
                    'required' => true,
                ],
                [
                    'name' => 'firstname',
                    'required' => true,
                ],
                [
                    'name' => 'lastname',
                    'required' => true,
                ],
                [
                    'name' => 'address',
                    'required' => true,
                ],
                [
                    'name' => 'zipcode',
                    'required' => true,
                ],
                [
                    'name' => 'geonameid',
                    'required' => true,
                ],
                [
                    'name' => 'position',
                    'required' => true,
                ],
                [
                    'name' => 'company',
                    'required' => true,
                ],
                [
                    'name' => 'job',
                    'required' => true,
                ],
                [
                    'name' => 'tel',
                    'required' => true,
                ],
                [
                    'name' => 'fax',
                    'required' => true,
                ]
            ]],
        ];
    }

    public function testPostRegisterWithProviderIdAndAlreadyBoundProvider()
    {
        self::$DI['app']['registration.fields'] = [];
        $this->logout(self::$DI['app']);

        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $entity = $this->getMock('Alchemy\Phrasea\Model\Entities\UsrAuthProvider');
        $entity->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(self::$DI['user']));

        $token = new Token($provider, 42);
        $this->addUsrAuthDoctrineEntitySupport(42, $entity, true);

        $provider->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $parameters = array_merge(['_token' => 'token'], [
            "password" => [
                'password' => 'password',
                'confirm'  => 'password'
            ],
            "email"           => $this->generateEmail(),
            "collections"     => null,
            "provider-id"     => 'provider-test',
        ]);

        foreach ($parameters as $key => $parameter) {
            if ('collections' === $key && null === $parameter) {
                $parameters[$key] = self::$registrationCollections;
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
        self::$DI['app']['registration.fields'] = [];
        $this->logout(self::$DI['app']);

        $parameters = array_merge(['_token' => 'token'], [
            "password" => [
                'password' => 'password',
                'confirm'  => 'password'
            ],
            "email"           => $this->generateEmail(),
            "accept-tou"      => '1',
            "collections"     => null,
            "provider-id"     => 'provider-test',
        ]);

        foreach ($parameters as $key => $parameter) {
            if ('collections' === $key && null === $parameter) {
                $parameters[$key] = self::$registrationCollections;
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
        self::$DI['app']['registration.fields'] = [];
        $this->logout(self::$DI['app']);

        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $provider->expects($this->any())
            ->method('getToken')
            ->will($this->throwException(new NotAuthenticatedException('Not authenticated')));

        $parameters = array_merge(['_token' => 'token'], [
            "password" => [
                'password' => 'password',
                'confirm'  => 'password'
            ],
            "email"           => $this->generateEmail(),
            "accept-tou"      => '1',
            "collections"     => null,
            "provider-id"     => 'provider-test',
        ]);

        foreach ($parameters as $key => $parameter) {
            if ('collections' === $key && null === $parameter) {
                $parameters[$key] = self::$registrationCollections;
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

    public function testPostRegisterWithProviderIdAndAccountNotCreatedYet()
    {
        self::$DI['app']['registration.fields'] = [];
        $this->logout(self::$DI['app']);
        $this->disableTOU();

        $emails = [
            'Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation'=>0,
            'Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered'=>0,
            'Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered'=>0,
        ];

        self::$DI['app']['phraseanet.appbox-register'] = $this->getMockBuilder('\appbox_register')
            ->disableOriginalConstructor()
            ->getMock();

        $nativeQueryMock = $this->getMockBuilder('Alchemy\Phrasea\Model\NativeQueryProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $nativeQueryMock->expects($this->once())->method('getAdminsOfBases')->will($this->returnValue([[
            self::$DI['user'],
            'base_id' => 1
        ]]));

        self::$DI['app']['EM.native-query'] = $nativeQueryMock;

        $this->mockNotificationsDeliverer($emails);
        $this->mockUserNotificationSettings('eventsmanager_notify_register');

        $parameters = array_merge(['_token' => 'token'], [
            "password" => [
                'password' => 'password',
                'confirm'  => 'password'
            ],
            "email"           => $this->generateEmail(),
            "collections"     => null,
            "provider-id"     => 'provider-test',
        ]);

        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $token = new Token($provider, 42);

        $provider->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $repoUsrAuthProvider = $this->getMockBuilder('Doctrine\ORM\EntityRepository\UsrAuthProviderRepository')
            ->setMethods(['findWithProviderAndId'])
            ->getMock();

        $repoUsrAuthProvider->expects($this->any())
            ->method('findWithProviderAndId')
            ->with('provider-test', $token->getId())
            ->will($this->returnValue(null));

        foreach ($parameters as $key => $parameter) {
            if ('collections' === $key && null === $parameter) {
                $parameters[$key] = self::$registrationCollections;
            }
            if ('login' === $key && null === $parameter) {
                $parameters[$key] = self::$login;
            }
            if ('email' === $key && null === $parameter) {
                $parameters[$key] = self::$email;
            }
        }

        if ( self::$DI['app']['conf']->get(['registry', 'registration', 'auto-select-collections'])) {
            unset($parameters['collections']);
        }

        self::$DI['client']->request('POST', '/login/register-classic/', $parameters);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());

        if (null === $user = self::$DI['app']['manipulator.user']->getRepository()->findByEmail($parameters['email'])) {
            $this->fail('User not created');
        }

        self::$DI['app']['manipulator.user']->delete($user);

        $this->assertGreaterThan(0, $emails['Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered']);
        $this->assertEquals(1, $emails['Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation']);
        $this->assertFlashMessagePopulated(self::$DI['app'], 'info', 1);
        $this->assertEquals('/login/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    /**
     * @dataProvider provideRegistrationData
     */
    public function testPostRegister($parameters, $extraParameters)
    {
        self::$DI['app']['phraseanet.appbox-register'] = $this->getMockBuilder('\appbox_register')
            ->disableOriginalConstructor()
            ->getMock();

        $nativeQueryMock = $this->getMockBuilder('Alchemy\Phrasea\Model\NativeQueryProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $nativeQueryMock->expects($this->once())->method('getAdminsOfBases')->will($this->returnValue([[
            self::$DI['user'],
            'base_id' => 1
        ]]));

        self::$DI['app']['EM.native-query'] = $nativeQueryMock;

        self::$DI['app']['registration.fields'] = $extraParameters;

        $this->logout(self::$DI['app']);
        $this->disableTOU();

        $emails = [
            'Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation'=>0,
            'Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered'=>0,
            'Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered'=>0,
        ];

        $this->mockNotificationsDeliverer($emails);
        $this->mockUserNotificationSettings('eventsmanager_notify_register');

        $parameters = array_merge(['_token' => 'token'], $parameters);
        foreach ($parameters as $key => $parameter) {
            if ('collections' === $key && null === $parameter) {
                $parameters[$key] = self::$registrationCollections;
            }
            if ('login' === $key && null === $parameter) {
                $parameters[$key] = self::$login;
            }
            if ('email' === $key && null === $parameter) {
                $parameters[$key] = self::$email;
            }
        }

        if (self::$DI['app']['conf']->get(['registry', 'registration', 'auto-select-collections'])) {
            unset($parameters['collections']);
        }

        self::$DI['client']->request('POST', '/login/register-classic/', $parameters);

        if (null === $user = self::$DI['app']['manipulator.user']->getRepository()->findByEmail($parameters['email'])) {
            $this->fail('User not created');
        }
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertGreaterThan(0, $emails['Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered']);
        $this->assertEquals(1, $emails['Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation']);
        $this->assertFlashMessagePopulated(self::$DI['app'], 'info', 1);
        $this->assertEquals('/login/', self::$DI['client']->getResponse()->headers->get('location'));
        self::$DI['app']['manipulator.user']->delete($user);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::logout
     */
    public function testGetLogout()
    {
        $this->assertTrue(self::$DI['app']['authentication']->isAuthenticated());
        self::$DI['client']->request('GET', '/login/logout/', ['app' => 'prod']);
        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::sendConfirmMail
     */
    public function testSendConfirmMailBadRequest()
    {
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('GET', '/login/send-mail-confirm/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    public function testSendConfirmMail()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation');
        $this->mockUserNotificationSettings('eventsmanager_notify_register');

        $this->logout(self::$DI['app']);
        self::$DI['client']->request('GET', '/login/send-mail-confirm/', ['usr_id' => self::$DI['user']->getId()]);

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
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('GET', '/login/send-mail-confirm/', ['usr_id' => 0]);

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
        $login = self::$DI['app']['authentication']->getUser()->getLogin();
        self::$DI['app']['manipulator.user']->setPassword(self::$DI['app']['authentication']->getUser(), $password);
        self::$DI['app']['authentication']->getUser()->setMailLocked(false);
        self::$DI['app']['EM']->persist(self::$DI['app']['authentication']->getUser());
        self::$DI['app']['EM']->flush();

        $this->logout(self::$DI['app']);

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);
        self::$DI['client']->request('POST', '/login/authenticate/', [
            'login' => $login,
            'password'   => $password,
            '_token' => 'token',
        ]);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegExp('/^\/prod\/$/', self::$DI['client']->getResponse()->headers->get('Location'));
    }

    /**
     * @dataProvider provideEventNames
     */
    public function testAuthenticateTriggersEvents($eventName, $className, $context)
    {
        $password = \random::generatePassword();

        $login = self::$DI['app']['authentication']->getUser()->getLogin();
        self::$DI['app']['manipulator.user']->setPassword(self::$DI['app']['authentication']->getUser(), $password);
        self::$DI['app']['authentication']->getUser()->setMailLocked(false);

        $this->logout(self::$DI['app']);

        $preEvent = 0;
        self::$DI['app']['dispatcher']->addListener($eventName, function ($event) use (&$preEvent, $className, $context) {
            $preEvent++;
            $this->assertInstanceOf($className, $event);
            $this->assertEquals($context, $event->getContext()->getContext());
        });

        self::$DI['client'] = new Client(self::$DI['app'], []);
        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);
        self::$DI['client']->request('POST', '/login/authenticate/', [
            'login' => $login,
            'password'   => $password,
            '_token' => 'token',
        ]);

        $this->assertEquals(1, $preEvent);
    }

    public function provideEventNames()
    {
        return [
            [PhraseaEvents::PRE_AUTHENTICATE, 'Alchemy\Phrasea\Core\Event\PreAuthenticate', Context::CONTEXT_NATIVE],
            [PhraseaEvents::POST_AUTHENTICATE, 'Alchemy\Phrasea\Core\Event\PostAuthenticate', Context::CONTEXT_NATIVE],
        ];
    }

    public function provideGuestEventNames()
    {
        return [
            [PhraseaEvents::PRE_AUTHENTICATE, 'Alchemy\Phrasea\Core\Event\PreAuthenticate', Context::CONTEXT_GUEST],
            [PhraseaEvents::POST_AUTHENTICATE, 'Alchemy\Phrasea\Core\Event\PostAuthenticate', Context::CONTEXT_GUEST],
        ];
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testAuthenticateCheckRedirect()
    {
        $password = \random::generatePassword();

        $login = self::$DI['app']['authentication']->getUser()->getLogin();
        self::$DI['app']['manipulator.user']->setPassword(self::$DI['app']['authentication']->getUser(), $password);

        $this->logout(self::$DI['app']);

        self::$DI['client'] = new Client(self::$DI['app'], []);
        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);
        self::$DI['client']->request('POST', '/login/authenticate/', [
            'login'    => $login,
            'password' => $password,
            '_token'   => 'token',
            'redirect' => '/admin'
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegExp('/admin/', self::$DI['client']->getResponse()->headers->get('Location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testGuestAuthenticate()
    {
        self::$DI['app']['acl']->get(self::$DI['user_guest'])->give_access_to_base([self::$DI['collection']->get_base_id()]);

        $this->logout(self::$DI['app']);

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
        self::$DI['app']['dispatcher']->addListener($eventName, function ($event) use (&$preEvent, $className, $context) {
            $preEvent++;
            $this->assertInstanceOf($className, $event);
            $this->assertEquals($context, $event->getContext()->getContext());
        });

        self::$DI['app']['acl']->get(self::$DI['user_guest'])->give_access_to_base([self::$DI['collection']->get_base_id()]);

        $this->logout(self::$DI['app']);

        self::$DI['client'] = new Client(self::$DI['app'], []);
        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);
        self::$DI['client']->request('POST', '/login/authenticate/guest/');

        $this->assertEquals(1, $preEvent);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testGuestAuthenticateWithGetMethod()
    {
        self::$DI['app']['acl']->get(self::$DI['user_guest'])->give_access_to_base([self::$DI['collection']->get_base_id()]);
        $this->logout(self::$DI['app']);

        self::$DI['client'] = new Client(self::$DI['app'], []);
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
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/login/authenticate/', [
            'login' => self::$DI['user']->getLogin(),
            'password'   => 'test',
            '_token' => 'token',
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertTrue(self::$DI['app']['session']->getFlashBag()->has('error'));
        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testBadAuthenticateCheckRedirect()
    {
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/login/authenticate/', [
            'login'     => self::$DI['user']->getLogin(),
            'password'       => 'test',
            '_token' => 'token',
            'redirect'  => '/prod'
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegExp('/redirect=prod/', self::$DI['client']->getResponse()->headers->get('Location'));
        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testMailLockedAuthenticate()
    {
        $this->logout(self::$DI['app']);
        $password = \random::generatePassword();
        self::$DI['user']->setMailLocked(true);
        self::$DI['client']->request('POST', '/login/authenticate/', [
            'login' => self::$DI['user']->getLogin(),
            'password'   => $password,
            '_token' => 'token'
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertEquals('/login/', self::$DI['client']->getResponse()->headers->get('location'));
        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
        self::$DI['user']->setMailLocked(false);
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

        $parameters = ['key1' => 'value1', 'key2' => 'value2'];

        $response = new Response();

        $provider->expects($this->once())
            ->method('authenticate')
            ->with($parameters)
            ->will($this->returnValue($response));

        $this->logout(self::$DI['app']);
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
        return [
            ['GET', '/login/provider/provider-test/authenticate/'],
            ['GET', '/login/provider/provider-test/callback/'],
            ['GET', '/login/register-classic/?providerId=provider-test'],
        ];
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

        $this->logout(self::$DI['app']);
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

        $this->logout(self::$DI['app']);
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

        $entity = $this->getMock('Alchemy\Phrasea\Model\Entities\UsrAuthProvider');
        $entity->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(self::$DI['user']));

        $token = new Token($provider, 42);
        $this->addUsrAuthDoctrineEntitySupport(42, $entity, true);

        $provider->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->logout(self::$DI['app']);
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

        $this->logout(self::$DI['app']);
        self::$DI['client']->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());

        $ret = self::$DI['app']['EM']->getRepository('Phraseanet:UsrAuthProvider')
            ->findBy(['user' => self::$DI['user']->getId(), 'provider' => 'provider-test']);

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
            ->will($this->returnValue([]));

        $identity->expects($this->once())
            ->method('getEmail')
            ->will($this->returnValue('supermail@superprovider.com'));

        if (null === $user = self::$DI['app']['manipulator.user']->getRepository()->findByEmail('supermail@superprovider.com')) {
            $random = self::$DI['app']['tokens']->generatePassword();
            $user = self::$DI['app']['manipulator.user']->createUser('temporary-'.$random, $random, 'supermail@superprovider.com');
        }

        self::$DI['app']['authentication.providers.account-creator'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\AccountCreator')
            ->disableOriginalConstructor()
            ->getMock();
        self::$DI['app']['authentication.providers.account-creator']->expects($this->once())
            ->method('create')
            ->with(self::$DI['app'], 42, 'supermail@superprovider.com', [])
            ->will($this->returnValue($user));
        self::$DI['app']['authentication.providers.account-creator']->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));

        $this->logout(self::$DI['app']);
        self::$DI['client']->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());

        $ret = self::$DI['app']['EM']->getRepository('Phraseanet:UsrAuthProvider')
            ->findBy(['user' => $user->getId(), 'provider' => 'provider-test']);

        $this->assertCount(1, $ret);

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertSame('/prod/', self::$DI['client']->getResponse()->headers->get('location'));

        $this->assertTrue(self::$DI['app']['authentication']->isAuthenticated());
        self::$DI['app']['manipulator.user']->delete($user);
    }

    public function testAuthenticateProviderCallbackWithRegistrationEnabled()
    {
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $provider->expects($this->once())->method('onCallback');

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

        $this->logout(self::$DI['app']);
        self::$DI['client']->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertSame('/login/register-classic/?providerId=provider-test', self::$DI['client']->getResponse()->headers->get('location'));

        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
    }

    public function testAuthenticateProviderCallbackWithoutRegistrationEnabled()
    {
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);
        $provider->expects($this->once())->method('onCallback');
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

        $this->disableRegistration();
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());

        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
        $this->assertFlashMessagePopulated(self::$DI['app'], 'error', 1);
        $this->assertSame('/login/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function testGetRegistrationFields()
    {
        $fields = [
            'field' => [
                'required' => false
            ]
        ];
        self::$DI['app']['registration.fields'] = $fields;

        $this->logout(self::$DI['app']);
        self::$DI['client']->request('GET', '/login/registration-fields/');

        $this->assertSame(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertSame('application/json', self::$DI['client']->getResponse()->headers->get('content-type'));

        $this->assertEquals($fields, json_decode(self::$DI['client']->getResponse()->getContent(), true));
    }

    public function testRegisterRedirectsNoAuthProvidersAvailable()
    {
        $this->logout(self::$DI['app']);

        self::$DI['app']['authentication.providers'] = new ProvidersCollection();

        self::$DI['client']->request('GET', '/login/register/');

        $this->assertSame(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertSame('/login/register-classic/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function testRegisterDisplaysIfAuthProvidersAvailable()
    {
        $this->logout(self::$DI['app']);

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
            ->setMethods(['findWithProviderAndId'])
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
            ->with('Phraseanet:UsrAuthProvider')
            ->will($this->returnValue($repo));

        if ($participants) {
            $repo = $this->getMockBuilder('Alchemy\Phrasea\Model\Repositories\ValidationParticipantRepository')
                ->disableOriginalConstructor()
                ->getMock();

            $repo->expects($this->once())
                ->method('findNotConfirmedAndNotRemindedParticipantsByExpireDate')
                ->will($this->returnValue([]));

            self::$DI['app']['EM']->expects($this->at(1))
                ->method('getRepository')
                ->with('Phraseanet:ValidationParticipant')
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
     * Delete inscription request made by the current authenticathed user
     * @return void
     */
    private function deleteRequest()
    {
        $query = self::$DI['app']['EM']->createQuery('DELETE FROM Phraseanet:Registration d WHERE d.user=?1');
        $query->setParameter(1, self::$DI['user']->getId());
        $query->execute();
    }

    /**
     * Generate a new valid email adress
     * @return string
     */
    private function generateEmail()
    {
        return \random::generatePassword() . '_email@email.com';
    }

    private function disableTOU()
    {
        if (null === self::$termsOfUse) {
            self::$termsOfUse = [];
            foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
                self::$termsOfUse[$databox->get_sbas_id()] = $databox->get_cgus();

                foreach ( self::$termsOfUse[$databox->get_sbas_id()]as $lng => $tou) {
                    $databox->update_cgus($lng, '', false);
                }
            }
        }
    }

    private function enableTOU()
    {
        if (null !== self::$termsOfUse) {
            return;
        }
        self::$termsOfUse = [];
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            self::$termsOfUse[$databox->get_sbas_id()] = $databox->get_cgus();

            foreach ( self::$termsOfUse[$databox->get_sbas_id()]as $lng => $tou) {
                $databox->update_cgus($lng, 'something', false);
            }
        }
    }

    private function resetTOU()
    {
        if (null === self::$termsOfUse) {
            return;
        }
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            if (!isset(self::$termsOfUse[$databox->get_sbas_id()])) {
                continue;
            }
            $tous = self::$termsOfUse[$databox->get_sbas_id()];
            foreach ($tous as $lng => $tou) {
                $databox->update_cgus($lng, $tou['value'], false);
            }
        }

        self::$termsOfUse = null;
    }

    private function getRegistrationSummary()
    {
        return [
            [
                'registrations' => [
                    'by-type' => [
                        'inactive'  => [new Registration()],
                        'accepted'  => [new Registration()],
                        'in-time'   => [new Registration()],
                        'out-dated' => [new Registration()],
                        'pending'   => [new Registration()],
                        'rejected'  => [new Registration()],
                    ],
                    'by-collection' => []
                ],
                'config' => [
                    'db-name'       => 'a_db_name',
                    'cgu'           => null,
                    'cgu-release'   => null,
                    'can-register'  => false,
                    'collections'   => [
                        [
                            'coll-name'     => 'a_coll_name',
                            'can-register'  => false,
                            'cgu'           => 'Some terms of use.',
                            'registration'  => null
                        ],
                        [
                            'coll-name'     => 'an_other_coll_name',
                            'can-register'  => false,
                            'cgu'           => null,
                            'registration'  => null
                        ]
                    ],
                ]
            ]
        ];
    }

    private function enableRegistration()
    {
        $managerMock = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\RegistrationManager')
            ->setConstructorArgs([self::$DI['app']['phraseanet.appbox'], self::$DI['app']['manipulator.registration']->getRepository(), self::$DI['app']['locale']])
            ->setMethods(['isRegistrationEnabled'])
            ->getMock();

        self::$DI['app']['registration.manager'] = $managerMock;
        self::$DI['app']['registration.manager']->expects($this->any())->method('isRegistrationEnabled')->will($this->returnValue(true));
    }

    private function disableRegistration()
    {
        $managerMock = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\RegistrationManager')
            ->setConstructorArgs([self::$DI['app']['phraseanet.appbox'], self::$DI['app']['manipulator.registration']->getRepository(), self::$DI['app']['locale']])
            ->setMethods(['isRegistrationEnabled'])
            ->getMock();

        self::$DI['app']['registration.manager'] = $managerMock;
        self::$DI['app']['registration.manager']->expects($this->any())->method('isRegistrationEnabled')->will($this->returnValue(false));
    }
}
