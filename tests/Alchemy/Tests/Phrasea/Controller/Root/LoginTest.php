<?php

namespace Alchemy\Tests\Phrasea\Controller\Root;

use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\Provider\Token\Token;
use Alchemy\Phrasea\Authentication\ProvidersCollection;
use Alchemy\Phrasea\Core\Event\AuthenticationEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\Registration;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use RandomLib\Factory;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
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

        $collection = $this->getCollection();

        if (null === self::$registrationCollections) {
            self::$registrationCollections = [$collection->get_coll_id()];
        }
        if (null === self::$collections) {
            self::$collections = [$collection->get_base_id()];

            $sxml = simplexml_load_string($collection->get_prefs());
            $sxml->caninscript = 1;
            $dom = new \DOMDocument();
            $dom->loadXML($sxml->asXML());
            $collection->set_prefs($dom);
        }

        $user = $this->getUser();

        if (null === self::$login) {
            self::$login = $user->getLogin();
        }
        if (null === self::$email) {
            self::$email = $user->getEmail();
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
        $app = $this->getApplication();
        $client = $this->getClient();

        $this->logout($app);
        $this->disableTOU();
        $client->followRedirects();
        $crawler = $client->request('GET', '/login/register-classic');
        $this->assertEquals(0, $crawler->filter('a[href="' . $app->path('login_cgus') . '"]')->count());
    }

    public function testRegisterWithTou()
    {
        $app = $this->getApplication();
        $client = $this->getClient();

        $this->logout($app);
        $this->enableTOU();
        $client->followRedirects();
        $crawler = $client->request('GET', '/login/register-classic');
        $this->assertEquals(2, $crawler->filter('a[href="' . $app->path('login_cgus') . '"]')->count());
    }

    public function testRegisterWithAutoSelect()
    {
        $app = $this->getApplication();
        $configuration = $app['conf'];

        $this->logout($app);

        $gvAutoSelectDb = !!$configuration->get(['registry', 'registration', 'auto-select-collections']);
        $configuration->set(['registry', 'registration', 'auto-select-collections'], false);
        $crawler = $this->getClient()->request('GET', '/login/register-classic/');
        $this->assertEquals(1, $crawler->filter('select[name="collections[]"]')->count());
        $configuration->set(['registry', 'registration', 'auto-select-collections'], $gvAutoSelectDb);
    }

    public function testRegisterWithNoAutoSelect()
    {
        $app = $this->getApplication();
        $configuration = $app['conf'];
        $this->logout($app);

        $gvAutoSelectDb = !!$configuration->get(['registry', 'registration', 'auto-select-collections']);
        $configuration->set(['registry', 'registration', 'auto-select-collections'], true);
        $crawler = $this->getClient()->request('GET', '/login/register-classic/');
        $this->assertEquals(0, $crawler->filter('select[name="collections[]"]')->count());
        $configuration->set(['registry', 'registration', 'auto-select-collections'], $gvAutoSelectDb);
    }

    public function testLoginAlreadyAthenticated()
    {
        $client = $this->getClient();

        $client->request('GET', '/login/');
        $response = $client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/prod/', $response->headers->get('location'));
    }

    /**
     * @dataProvider provideFlashMessages
     * @param string $type
     * @param string $message
     */
    public function testLoginError($type, $message)
    {
        $app = $this->getApplication();
        $client = $this->getClient();

        $this->logout($app);
        $app->addFlash($type, $message);

        $crawler = $client->request('GET', '/login/');

        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertFlashMessage($crawler, $type, 1, $message);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailNoCode()
    {
        $app = $this->getApplication();
        $client = $this->getClient();

        $this->logout($app);
        $client->request('GET', '/login/register-confirm/');
        $response = $client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated($app, 'error', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailWrongCode()
    {
        $app = $this->getApplication();
        $client = $this->getClient();

        $this->logout($app);
        $client->request('GET', '/login/register-confirm/', [
            'code' => '34dT0k3n',
        ]);
        $response = $client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated($app, 'error', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailUserNotFound()
    {
        $app = $this->getApplication();

        $this->logout($app);
        $email = $this->generateEmail();
        $token = $app['manipulator.token']->createResetEmailToken($this->getUser(), $email);
        $tokenValue = $token->getValue();
        $app['orm.em']->remove($token);
        $app['orm.em']->flush();
        $this->getClient()->request('GET', '/login/register-confirm/', [
            'code' => $tokenValue,
        ]);
        $response = $this->getClient()->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated($app, 'error', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailUnlocked()
    {
        $app = $this->getApplication();
        $client = $this->getClient();

        $this->logout($app);
        $email = $this->generateEmail();
        $token = $app['manipulator.token']->createResetEmailToken($this->getUser(), $email);

        $this->getUser()->setMailLocked(false);

        $client->request('GET', '/login/register-confirm/', ['code' => $token->getValue()]);
        $response = $client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated($app, 'info', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    public function testRegisterConfirmMail()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationRegistered');
        $this->mockUserNotificationSettings('eventsmanager_notify_register');

        $app = $this->getApplication();
        $this->logout($app);
        $email = $this->generateEmail();
        $token = $app['manipulator.token']->createResetEmailToken($this->getUser(), $email);

        $this->getUser()->setMailLocked(true);
        $this->deleteRequest();
        $registration = new Registration();
        $registration->setUser($this->getUser());
        $registration->setBaseId($this->getCollection()->get_base_id());

        $app['orm.em']->persist($registration);
        $app['orm.em']->flush();

        $client = $this->getClient();
        $client->request('GET', '/login/register-confirm/', ['code' => $token->getValue()]);
        $response = $client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated($app, 'success', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
        $this->assertFalse($this->getUser()->isMailLocked());
    }

    public function testRegisterConfirmMailNoCollAwait()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationUnregistered');
        $this->mockUserNotificationSettings('eventsmanager_notify_register');;
        $app = $this->getApplication();

        $this->logout($app);
        $email = $this->generateEmail();
        $user = $app['manipulator.user']->createUser(uniqid('test_'), uniqid('test_'), $email);
        $token = $app['manipulator.token']->createResetEmailToken($user, $email);
        $user->setMailLocked(true);
        $revokeBases = [];
        foreach ($app->getDataboxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $revokeBases[] = $collection->get_base_id();
            }
        }
        $app->getAclForUser($user)->revoke_access_from_bases($revokeBases);
        $this->deleteRequest();

        $client = $this->getClient();
        $client->request('GET', '/login/register-confirm/', ['code' => $token->getValue()]);
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect(), $response->getContent());
        $this->assertFlashMessagePopulated($app, 'info', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
        $this->assertFalse($this->getUser()->isMailLocked());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordInvalidEmail()
    {
        $this->logout($this->getApplication());
        $client = $this->getClient();

        $crawler = $client->request('POST', '/login/forgot-password/', [
            'email' => 'invalid.email.com',
            '_token' => 'token',
        ]);
        $response = $client->getResponse();

        $this->assertFalse($response->isRedirect());

        $this->assertFormError($crawler, 1);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordUnknowEmail()
    {
        $this->logout($this->getApplication());
        $client = $this->getClient();

        $crawler = $client->request('POST', '/login/forgot-password/', [
            'email' => 'invalid_email@test.com',
            '_token' => 'token',
        ]);
        $response = $client->getResponse();
        $this->assertFalse($response->isRedirect());
        $this->assertFlashMessage($crawler, 'error', 1);
    }

    public function testRenewPasswordMail()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate');
        $this->mockUserNotificationSettings('eventsmanager_notify_register');

        $this->logout($this->getApplication());
        $client = $this->getClient();

        $client->request('POST', '/login/forgot-password/', [
            'email' => $this->getUser()->getEmail(),
            '_token' => 'token',
        ]);
        $response = $client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/forgot-password/', $response->headers->get('location'));
        $this->assertFlashMessagePopulated($this->getApplication(), 'info', 1);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordBadArguments()
    {
        $app = $this->getApplication();
        $client = $this->getClient();

        $this->logout($app);
        $token = $app['manipulator.token']->createResetPasswordToken($this->getUser());
        $crawler = $client->request('POST', '/login/renew-password/', [
            'token' => $token->getValue(),
            '_token' => 'token',
            'password' => ['password' => 'password', 'confirm' => 'not_identical'],
        ]);

        $response = $client->getResponse();

        $this->assertFalse($response->isRedirect());
        $this->assertFormOrFlashError($crawler, 1);
    }

    public function testRenewPasswordBadToken()
    {
        $this->logout($this->getApplication());
        $client = $this->getClient();

        $client->request('POST', '/login/renew-password/', [
            'token' => 'badToken',
            '_token' => 'token',
            'password' => ['password' => 'password', 'confirm' => 'password'],
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRenewPasswordBadTokenWheneverItsAuthenticated()
    {
        $client = $this->getClient();

        $client->request('POST', '/login/renew-password/', [
            'token' => 'badToken',
            '_token' => 'token',
            'password' => ['password' => 'password', 'confirm' => 'password'],
        ]);

        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/prod/', $response->headers->get('location'));
    }

    public function testRenewPasswordNoToken()
    {
        $this->logout($this->getApplication());
        $client = $this->getClient();

        $client->request('POST', '/login/renew-password/', [
            '_token' => 'token',
            'password' => ['password' => 'password', 'confirm' => 'password'],
        ]);

        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRenewPasswordNoTokenWheneverItsAuthenticated()
    {
        $client = $this->getClient();

        $client->request('POST', '/login/renew-password/', [
            '_token' => 'token',
            'password' => ['password' => 'password', 'confirm' => 'password'],
        ]);

        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/prod/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPassword()
    {
        $app = $this->getApplication();
        $this->logout($app);

        $token = $app['manipulator.token']->createResetPasswordToken($this->getUser());

        $client = $this->getClient();
        $client->request('POST', '/login/renew-password/', [
            'token' => $token->getValue(),
            '_token' => 'token',
            'password' => ['password' => 'password', 'confirm' => 'password'],
        ]);

        $response = $client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/', $response->headers->get('location'));

        $this->assertFlashMessagePopulated($app, 'success', 1);
    }

    /**
     * @dataProvider provideFlashMessages
     * @param string $type
     * @param string $message
     */
    public function testRenewPasswordPageShowsFlashMessages($type, $message)
    {
        $application = $this->getApplication();
        $client = $this->getClient();

        $this->logout($application);
        $application->addFlash($type, $message);

        $token = $application['manipulator.token']->createResetPasswordToken($this->getUser());

        $crawler = $client->request('GET', '/login/renew-password/', [
            'token' => $token->getValue(),
        ]);

        $this->assertTrue($client->getResponse()->isOk());

        $this->assertFlashMessage($crawler, $type, 1, $message);
    }

    public function testForgotPasswordGet()
    {
        $this->logout($this->getApplication());
        $client = $this->getClient();
        $client->request('GET', '/login/forgot-password/');

        $this->assertTrue($client->getResponse()->isOk());
    }

    public function testForgotPasswordWhenAuthenticatedMustReturnToProd()
    {
        $client = $this->getClient();
        $client->request('GET', '/login/forgot-password/');

        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/prod/', $response->headers->get('location'));
    }

    public function testForgotPasswordInvalidEmail()
    {
        $this->logout($this->getApplication());
        $client = $this->getClient();
        $crawler = $client->request('POST', '/login/forgot-password/', [
            '_token' => 'token',
            'email' => 'invalid.email',
        ]);

        $response = $client->getResponse();
        $this->assertFalse($response->isRedirect());

        $this->assertFormError($crawler, 1);
    }

    public function testForgotPasswordWrongEmail()
    {
        $this->logout($this->getApplication());
        $client = $this->getClient();
        $crawler = $client->request('POST', '/login/forgot-password/', [
            '_token' => 'token',
            'email' => 'invalid@email.com',
        ]);

        $response = $client->getResponse();
        $this->assertFalse($response->isRedirect());

        $this->assertFlashMessage($crawler, 'error', 1);
    }

    public function testForgotPasswordSubmission()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate');
        $this->mockUserNotificationSettings('eventsmanager_notify_register');

        $app = $this->getApplication();
        $client = $this->getClient();

        $this->logout($app);
        $client->request('POST', '/login/forgot-password/', [
            '_token' => 'token',
            'email' => $this->getUser()->getEmail(),
        ]);

        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect());

        $this->assertFlashMessagePopulated($app, 'info', 1);
    }

    /**
     * @dataProvider provideFlashMessages
     * @param string $type
     * @param string $message
     */
    public function testGetRegister($type, $message)
    {
        $application = $this->getApplication();
        $client = $this->getClient();

        $this->logout($application);
        $application->addFlash($type, $message);
        $crawler = $client->request('GET', '/login/register-classic/');

        $response = $this->getClient()->getResponse();

        $this->assertTrue($response->isOk());
        $this->assertFlashMessage($crawler, $type, 1, $message);
    }

    public function testGetRegisterWithRegisterIdBindDataToForm()
    {
        $app = $this->getApplication();
        $this->logout($app);

        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');

        $providersCollectionMock = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ProvidersCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $providersCollectionMock->expects($this->any())
            ->method('get')
            ->with($this->equalTo('provider-test'))
            ->will($this->returnValue($provider));
        $app['authentication.providers'] = $providersCollectionMock;

        $identity = $this->getMockBuilder('Alchemy\Phrasea\Authentication\Provider\Token\Identity')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($identity));

        $identity->expects($this->once())
            ->method('getEmail')
            ->will($this->returnValue('supermail@superprovider.com'));

        $client = $this->getClient();
        $crawler = $client->request('GET', '/login/register-classic/?providerId=provider-test');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
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
     * @param string $method
     * @param string $route
     */
    public function testGetPostRegisterWhenRegistrationDisabled($method, $route)
    {
        $this->disableRegistration();
        $this->logout($this->getApplication());
        $this->getClient()->request($method, $route);
        $this->assertEquals(404, $this->getClient()->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider provideInvalidRegistrationData
     * @param array $parameters
     * @param array $extraParameters
     * @param array $errors
     */
    public function testPostRegisterBadArguments($parameters, $extraParameters, $errors)
    {
        $this->enableTOU();
        $app = $this->getApplication();
        $app['registration.enabled'] = true;
        $app['registration.fields'] = $extraParameters;

        $this->logout($app);

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

        if ($app['conf']->get(['registry', 'registration', 'auto-select-collections'])) {
            unset($parameters['collections']);
        }

        $client = $this->getClient();
        $crawler = $client->request('POST', '/login/register-classic/', $parameters);

        $this->assertFalse($client->getResponse()->isRedirect());
        $this->assertFormOrFlashError($crawler, $errors);
    }

    public function testPostRegisterWithoutParams()
    {
        $app = $this->getApplication();
        $client = $this->getClient();

        $this->logout($app);
        $this->disableTOU();
        $crawler = $client->request('POST', '/login/register-classic/');

        $this->assertFalse($client->getResponse()->isRedirect());
        $this->assertFormOrFlashError($crawler, $app['conf']->get([
            'registry',
            'registration',
            'auto-select-collections',
        ]) ? 7 : 8);
    }

    public function provideInvalidRegistrationData()
    {
        return [
            [
                [//required field missing
                 "password" => [
                     'password' => 'password',
                     'confirm' => 'password',
                 ],
                 "collections" => null,
                 "accept-tou" => '1',
                ],
                [],
                1,
            ],
            [
                [//required extra-field missing
                 "password" => [
                     'password' => 'password',
                     'confirm' => 'password',
                 ],
                 "email" => $this->generateEmail(),
                 "collections" => null,
                 "accept-tou" => '1',
                ],
                [
                    [
                        'name' => 'login',
                        'required' => true,
                    ],
                ],
                1,
            ],
            [
                [//password mismatch
                 "password" => [
                     'password' => 'password',
                     'confirm' => 'passwordMismatch',
                 ],
                 "email" => $this->generateEmail(),
                 "collections" => null,
                 "accept-tou" => '1',
                ],
                [],
                1,
            ],
            [
                [//password tooshort
                 "password" => [
                     'password' => 'min',
                     'confirm' => 'min',
                 ],
                 "email" => $this->generateEmail(),
                 "collections" => null,
                 "accept-tou" => '1',
                ],
                [],
                1,
            ],
            [
                [//email invalid
                 "password" => [
                     'password' => 'password',
                     'confirm' => 'password',
                 ],
                 "email" => 'invalid.email',
                 "collections" => null,
                 "accept-tou" => '1',
                ],
                [],
                1,
            ],
            [
                [//login exists
                 "login" => null,
                 "password" => [
                     'password' => 'password',
                     'confirm' => 'password',
                 ],
                 "email" => $this->generateEmail(),
                 "collections" => null,
                 "accept-tou" => '1',
                ],
                [
                    [
                        'name' => 'login',
                        'required' => true,
                    ],
                ],
                1,
            ],
            [
                [//mails exists
                 "password" => [
                     'password' => 'password',
                     'confirm' => 'password',
                 ],
                 "email" => null,
                 "collections" => null,
                 "accept-tou" => '1',
                ],
                [],
                1,
            ],
            [
                [//tou declined
                 "password" => [
                     'password' => 'password',
                     'confirm' => 'password',
                 ],
                 "email" => $this->generateEmail(),
                 "collections" => null,
                ],
                [],
                1,
            ],
        ];
    }

    public function provideRegistrationData()
    {
        $factory = new Factory();
        $generator = $factory->getLowStrengthGenerator();

        return [
            [
                [
                    "password" => [
                        'password' => 'password',
                        'confirm' => 'password',
                    ],
                    "email" => $this->generateEmail(),
                    "collections" => null,
                ],
                [],
            ],
            [
                [
                    "password" => [
                        'password' => 'password',
                        'confirm' => 'password',
                    ],
                    "email" => $this->generateEmail(),
                    "collections" => null,
                ],
                [
                    [
                        'name' => 'login',
                        'required' => false,
                    ],
                ],
            ],
            [
                [//extra-fields are not required
                 "password" => [
                     'password' => 'password',
                     'confirm' => 'password',
                 ],
                 "email" => $this->generateEmail(),
                 "collections" => null,
                ],
                [
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
                    ],
                ],
            ],
            [
                [//extra-fields are required
                 "password" => [
                     'password' => 'password',
                     'confirm' => 'password',
                 ],
                 "email" => $this->generateEmail(),
                 "collections" => null,
                 "login" => 'login-' . $generator->generateString(8),
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
                ],
                [
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
                    ],
                ],
            ],
        ];
    }

    public function testPostRegisterWithProviderIdAndAlreadyBoundProvider()
    {
        $app = $this->getApplication();

        $app['registration.fields'] = [];
        $this->logout($app);

        /** @var ProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(ProviderInterface::class);
        $this->addProvider('provider-test', $provider);

        $entity = $this->getMock('Alchemy\Phrasea\Model\Entities\UsrAuthProvider');
        $entity->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($this->getUser()));

        $token = new Token($provider, 42);
        $this->addUsrAuthDoctrineEntitySupport(42, $entity, true);

        $provider->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $parameters = array_merge(['_token' => 'token'], [
            "password" => [
                'password' => 'password',
                'confirm' => 'password',
            ],
            "email" => $this->generateEmail(),
            "collections" => null,
            "provider-id" => 'provider-test',
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

        $client = $this->getClient();
        $client->request('POST', '/login/register-classic/', $parameters);
        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertEquals('/prod/', $client->getResponse()->headers->get('location'));
    }

    public function testPostRegisterWithUnknownProvider()
    {
        $app = $this->getApplication();
        $app['registration.fields'] = [];
        $this->logout($app);

        $parameters = array_merge(['_token' => 'token'], [
            "password" => [
                'password' => 'password',
                'confirm' => 'password',
            ],
            "email" => $this->generateEmail(),
            "accept-tou" => '1',
            "collections" => null,
            "provider-id" => 'provider-test',
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

        $client = $this->getClient();
        $client->request('POST', '/login/register-classic/', $parameters);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertFlashMessagePopulated($app, 'error', 1);
        $this->assertEquals('/login/register/', $client->getResponse()->headers->get('location'));
    }

    public function testPostRegisterWithProviderNotAuthenticated()
    {
        $app = $this->getApplication();
        $app['registration.fields'] = [];
        $this->logout($app);

        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $provider->expects($this->any())
            ->method('getToken')
            ->will($this->throwException(new NotAuthenticatedException('Not authenticated')));

        $parameters = array_merge(['_token' => 'token'], [
            "password" => [
                'password' => 'password',
                'confirm' => 'password',
            ],
            "email" => $this->generateEmail(),
            "accept-tou" => '1',
            "collections" => null,
            "provider-id" => 'provider-test',
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

        $client = $this->getClient();
        $client->request('POST', '/login/register-classic/', $parameters);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertFlashMessagePopulated($app, 'error', 1);
        $this->assertEquals('/login/register/', $client->getResponse()->headers->get('location'));
    }

    public function testPostRegisterWithProviderIdAndAccountNotCreatedYet()
    {
        $app = $this->getApplication();
        $app['registration.fields'] = [];
        $this->logout($app);
        $this->disableTOU();

        $emails = [
            'Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation' => 0,
            'Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered' => 0,
            'Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered' => 0,
        ];

        $nativeQueryMock = $this->getMockBuilder('Alchemy\Phrasea\Model\NativeQueryProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $nativeQueryMock
            ->expects($this->once())
            ->method('getAdminsOfBases')
            ->will($this->returnValue(
                [
                    [
                        $this->getUser(),
                        'base_id' => 1,
                    ],
                ]
            ));

        $app['orm.em.native-query'] = $nativeQueryMock;

        $this->mockNotificationsDeliverer($emails);
        $this->mockUserNotificationSettings('eventsmanager_notify_register');

        $acl = $this->getMockBuilder('ACL')
            ->disableOriginalConstructor()
            ->getMock();
        $acl->expects($this->once())
            ->method('get_granted_base')
            ->will($this->returnValue([]));

        $aclProvider = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ACLProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $aclProvider->expects($this->any())
            ->method('get')
            ->will($this->returnValue($acl));

        $app['acl'] = $aclProvider;

        $parameters = array_merge(['_token' => 'token'], [
            "password" => [
                'password' => 'password',
                'confirm' => 'password',
            ],
            "email" => $this->generateEmail(),
            "collections" => null,
            "provider-id" => 'provider-test',
        ]);

        /** @var ProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(ProviderInterface::class);
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

        if ($app['conf']->get(['registry', 'registration', 'auto-select-collections'])) {
            unset($parameters['collections']);
        }

        $client = $this->getClient();
        $client->request('POST', '/login/register-classic/', $parameters);
        $this->assertTrue($client->getResponse()->isRedirect(), $client->getResponse()->getContent());

        if (null === $user = $app['repo.users']->findByEmail($parameters['email'])) {
            $this->fail('User not created');
        }

        $app['manipulator.user']->delete($user);

        $this->assertGreaterThan(0, $emails['Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered']);
        $this->assertEquals(1, $emails['Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation']);
        $this->assertFlashMessagePopulated($app, 'info', 1);
        $this->assertEquals('/login/', $client->getResponse()->headers->get('location'));
    }

    /**
     * @dataProvider provideRegistrationData
     * @param array $parameters
     * @param array $extraParameters
     */
    public function testPostRegister($parameters, $extraParameters)
    {
        $nativeQueryMock = $this->getMockBuilder('Alchemy\Phrasea\Model\NativeQueryProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $nativeQueryMock->expects($this->once())->method('getAdminsOfBases')->will($this->returnValue([
            [
                $this->getUser(),
                'base_id' => 1,
            ],
        ]));

        $app = $this->getApplication();
        $app['orm.em.native-query'] = $nativeQueryMock;

        $acl = $this->getMockBuilder('ACL')
            ->disableOriginalConstructor()
            ->getMock();
        $acl->expects($this->once())
            ->method('get_granted_base')
            ->will($this->returnValue([]));

        $aclProvider = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ACLProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $aclProvider->expects($this->any())
            ->method('get')
            ->will($this->returnValue($acl));

        $app['acl'] = $aclProvider;

        $app['registration.fields'] = $extraParameters;

        $this->logout($app);
        $this->disableTOU();

        $emails = [
            'Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation' => 0,
            'Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered' => 0,
            'Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered' => 0,
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

        if ($app['conf']->get(['registry', 'registration', 'auto-select-collections'])) {
            unset($parameters['collections']);
        }

        $client = $this->getClient();
        $client->request('POST', '/login/register-classic/', $parameters);

        if (null === $user = $app['repo.users']->findByEmail($parameters['email'])) {
            $this->fail('User not created');
        }
        $this->assertTrue($client->getResponse()->isRedirect(), $client->getResponse()->getContent());
        $this->assertGreaterThan(0, $emails['Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered']);
        $this->assertEquals(1, $emails['Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation']);
        $this->assertFlashMessagePopulated($app, 'info', 1);
        $this->assertEquals('/login/', $client->getResponse()->headers->get('location'));
        $app['manipulator.user']->delete($user);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::logout
     */
    public function testGetLogout()
    {
        $app = $this->getApplication();
        $app['phraseanet.SE'] = $this->createSearchEngineMock();

        $this->assertTrue($app['authentication']->isAuthenticated());
        $client = $this->getClient();
        $client->request('GET', '/login/logout/', ['app' => 'prod']);
        $this->assertFalse($app['authentication']->isAuthenticated());

        $this->assertTrue($client->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::sendConfirmMail
     */
    public function testSendConfirmMailBadRequest()
    {
        $this->logout($this->getApplication());
        $client = $this->getClient();
        $client->request('GET', '/login/send-mail-confirm/');

        $this->assertBadResponse($client->getResponse());
    }

    public function testSendConfirmMail()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation');
        $this->mockUserNotificationSettings('eventsmanager_notify_register');

        $app = $this->getApplication();
        $client = $this->getClient();
        $this->logout($app);
        $client->request('GET', '/login/send-mail-confirm/', ['usr_id' => $this->getUser()->getId()]);

        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated($app, 'success', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::sendConfirmMail
     */
    public function testSendConfirmMailWrongUser()
    {
        $app = $this->getApplication();
        $client = $this->getClient();
        $this->logout($app);
        $client->request('GET', '/login/send-mail-confirm/', ['usr_id' => 0]);

        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertFlashMessagePopulated($app, 'error', 1);
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testAuthenticate()
    {
        $app = $this->getApplication();
        $password = $app['random.low']->generateString(8);
        $login = $app->getAuthenticatedUser()->getLogin();
        $app['manipulator.user']->setPassword($app->getAuthenticatedUser(), $password);
        $app->getAuthenticatedUser()->setMailLocked(false);
        $app['orm.em']->persist($app->getAuthenticatedUser());
        $app['orm.em']->flush();

        $this->logout($app);

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, $app);
        $client = $this->getClient();
        $client->request('POST', '/login/authenticate/', [
            'login' => $login,
            'password' => $password,
            '_token' => 'token',
        ]);
        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertRegExp('/^\/prod\/$/', $client->getResponse()->headers->get('Location'));
    }

    /**
     * @dataProvider provideEventNames
     * @param string $eventName
     * @param string $className
     * @param string $context
     */
    public function testAuthenticateTriggersEvents($eventName, $className, $context)
    {
        $app = $this->getApplication();
        $password = $app['random.low']->generateString(8);

        $login = $app->getAuthenticatedUser()->getLogin();
        $app['manipulator.user']->setPassword($app->getAuthenticatedUser(), $password);
        $app->getAuthenticatedUser()->setMailLocked(false);

        $this->logout($app);

        $preEvent = 0;
        $app['dispatcher']->addListener($eventName, function (AuthenticationEvent $event) use (&$preEvent, $className, $context) {
            $preEvent++;
            $this->assertInstanceOf($className, $event);
            $this->assertEquals($context, $event->getContext()->getContext());
        });

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, $app);
        $this->getClient()->request('POST', '/login/authenticate/', [
            'login' => $login,
            'password' => $password,
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
        $app = $this->getApplication();
        $password = $app['random.low']->generateString(8);

        $login = $app->getAuthenticatedUser()->getLogin();
        $app['manipulator.user']->setPassword($app->getAuthenticatedUser(), $password);

        $this->logout($app);

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, $app);
        $client = $this->getClient();
        $client->request('POST', '/login/authenticate/', [
            'login' => $login,
            'password' => $password,
            '_token' => 'token',
            'redirect' => '/admin',
        ]);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertRegExp('/admin/', $client->getResponse()->headers->get('Location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testGuestAuthenticate()
    {
        $app = $this->getApplication();
        $app->getAclForUser(self::$DI['user_guest'])->give_access_to_base([$this->getCollection()->get_base_id()]);

        $this->logout($app);

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, $app);
        $client = $this->getClient();
        $client->request('POST', '/login/authenticate/guest/');
        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertRegExp('/^\/prod\/$/', $client->getResponse()->headers->get('Location'));

        $cookies = $client->getResponse()->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);

        $this->assertArrayHasKey('invite-usr-id', $cookies['']['/']);
        /** @var Cookie $inviteUsrIdCookie */
        $inviteUsrIdCookie = $cookies['']['/']['invite-usr-id'];
        $this->assertInstanceOf(Cookie::class, $inviteUsrIdCookie);
        $this->assertInternalType('integer', $inviteUsrIdCookie->getValue());
    }

    /**
     * @dataProvider provideGuestEventNames
     * @param string $eventName
     * @param string $className
     * @param string $context
     */
    public function testGuestAuthenticateTriggersEvents($eventName, $className, $context)
    {
        $preEvent = 0;
        $app = $this->getApplication();
        $app['dispatcher']->addListener($eventName, function (AuthenticationEvent $event) use (&$preEvent, $className, $context) {
            $preEvent++;
            $this->assertInstanceOf($className, $event);
            $this->assertEquals($context, $event->getContext()->getContext());
        });

        $app->getAclForUser(self::$DI['user_guest'])->give_access_to_base([$this->getCollection()->get_base_id()]);

        $this->logout($app);

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, $app);
        $this->getClient()->request('POST', '/login/authenticate/guest/');

        $this->assertEquals(1, $preEvent);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testGuestAuthenticateWithGetMethod()
    {
        $app = $this->getApplication();
        $app->getAclForUser(self::$DI['user_guest'])->give_access_to_base([$this->getCollection()->get_base_id()]);
        $this->logout($app);

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, $app);
        $client = $this->getClient();
        $client->request('GET', '/login/authenticate/guest/');
        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertRegExp('/^\/prod\/$/', $client->getResponse()->headers->get('Location'));

        $cookies = $client->getResponse()->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);

        $this->assertArrayHasKey('invite-usr-id', $cookies['']['/']);
        /** @var Cookie $inviteUsrIdCookie */
        $inviteUsrIdCookie = $cookies['']['/']['invite-usr-id'];
        $this->assertInstanceOf(Cookie::class, $inviteUsrIdCookie);
        $this->assertInternalType('integer', $inviteUsrIdCookie->getValue());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testBadAuthenticate()
    {
        $app = $this->getApplication();
        $client = $this->getClient();
        $this->logout($app);
        $client->request('POST', '/login/authenticate/', [
            'login' => $this->getUser()->getLogin(),
            'password' => 'test',
            '_token' => 'token',
        ]);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertTrue($app['session']->getFlashBag()->has('error'));
        $this->assertFalse($app['authentication']->isAuthenticated());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testBadAuthenticateCheckRedirect()
    {
        $app = $this->getApplication();
        $client = $this->getClient();

        $this->logout($app);
        $client->request('POST', '/login/authenticate/', [
            'login' => $this->getUser()->getLogin(),
            'password' => 'test',
            '_token' => 'token',
            'redirect' => '/prod',
        ]);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertRegExp('/redirect=prod/', $client->getResponse()->headers->get('Location'));
        $this->assertFalse($app['authentication']->isAuthenticated());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testMailLockedAuthenticate()
    {
        $app = $this->getApplication();
        $client = $this->getClient();

        $this->logout($app);
        $password = $app['random.low']->generateString(8);
        $this->getUser()->setMailLocked(true);
        $client->request('POST', '/login/authenticate/', [
            'login' => $this->getUser()->getLogin(),
            'password' => $password,
            '_token' => 'token',
        ]);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertEquals('/login/', $client->getResponse()->headers->get('location'));
        $this->assertFalse($app['authentication']->isAuthenticated());
        $this->getUser()->setMailLocked(false);
    }

    public function testAuthenticateWithProvider()
    {
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');

        $app = $this->getApplication();
        $providersCollectionMock = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ProvidersCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $providersCollectionMock->expects($this->any())
            ->method('get')
            ->with($this->equalTo('provider-test'))
            ->will($this->returnValue($provider));
        $app['authentication.providers'] = $providersCollectionMock;

        $parameters = ['key1' => 'value1', 'key2' => 'value2'];

        $response = new Response();

        $provider->expects($this->once())
            ->method('authenticate')
            ->with($parameters)
            ->will($this->returnValue($response));

        $this->logout($app);
        $client = $this->getClient();
        $client->request('GET', '/login/provider/provider-test/authenticate/', $parameters);

        $this->assertSame($response, $client->getResponse());
    }

    /**
     * @dataProvider provideAuthProvidersRoutesAndMethods
     * @param string $method
     * @param string $route
     */
    public function testAuthenticateProviderWhileConnected($method, $route)
    {
        $client = $this->getClient();
        $client->request($method, $route);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/prod/', $client->getResponse()->headers->get('location'));
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
     * @param string $method
     * @param string $route
     */
    public function testAuthenticateWithInvalidProvider($method, $route)
    {
        $app = $this->getApplication();
        $providersCollectionMock = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ProvidersCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $providersCollectionMock->expects($this->any())
            ->method('get')
            ->with($this->equalTo('provider-test'))
            ->will($this->throwException(new InvalidArgumentException('Provider not found')));
        $app['authentication.providers'] = $providersCollectionMock;

        $this->logout($app);
        $this->getClient()->request($method, $route);

        $this->assertEquals(404, $this->getClient()->getResponse()->getStatusCode());
    }

    private function addProvider($name, \PHPUnit_Framework_MockObject_MockObject $provider)
    {
        $app = $this->getApplication();

        $providersCollectionMock = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ProvidersCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $providersCollectionMock->expects($this->any())
            ->method('get')
            ->with($this->equalTo($name))
            ->will($this->returnValue($provider));
        $app['authentication.providers'] = $providersCollectionMock;

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

        $this->logout($this->getApplication());
        $client = $this->getClient();
        $client->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/login/', $client->getResponse()->headers->get('location'));

        $this->assertFlashMessagePopulated($this->getApplication(), 'error', 1);
    }

    public function testAuthenticateProviderCallbackAlreadyBound()
    {
        /** @var ProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $provider->expects($this->once())
            ->method('onCallback');

        $entity = $this->getMock('Alchemy\Phrasea\Model\Entities\UsrAuthProvider');
        $entity->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($this->getUser()));

        $token = new Token($provider, 42);
        $this->addUsrAuthDoctrineEntitySupport(42, $entity, true);

        $provider->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $app = $this->getApplication();
        $client = $this->getClient();
        $this->logout($app);
        $client->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/prod/', $client->getResponse()->headers->get('location'));

        $this->assertTrue($app['authentication']->isAuthenticated());
    }

    public function testAuthenticateProviderCallbackWithSuggestionBindProviderToUser()
    {
        /** @var ProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $provider->expects($this->once())
            ->method('onCallback');

        $token = new Token($provider, 42);

        $provider->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $app = $this->getApplication();
        $user = $this->getUser();

        $suggestionFinder = $this->getMockBuilder('Alchemy\Phrasea\Authentication\SuggestionFinder')
            ->disableOriginalConstructor()
            ->getMock();

        $suggestionFinder->expects($this->once())
            ->method('find')
            ->with($token)
            ->will($this->returnValue($user));

        $app['authentication.suggestion-finder'] = $suggestionFinder;

        $this->logout($app);
        $client = $this->getClient();
        $client->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, $client->getResponse()->getStatusCode());

        $ret = $app['orm.em']->getRepository('Phraseanet:UsrAuthProvider')
            ->findBy(['user' => $this->getUser()->getId(), 'provider' => 'provider-test']);

        $this->assertCount(1, $ret);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/prod/', $client->getResponse()->headers->get('location'));

        $this->assertTrue($app['authentication']->isAuthenticated());
    }

    public function testAuthenticateProviderCallbackWithAccountCreatorEnabled()
    {
        /** @var ProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $provider->expects($this->once())
            ->method('onCallback');

        $token = new Token($provider, 42);

        $provider->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $app = $this->getApplication();
        $suggestionFinder = $this->getMockBuilder('Alchemy\Phrasea\Authentication\SuggestionFinder')
            ->disableOriginalConstructor()
            ->getMock();

        $suggestionFinder->expects($this->once())
            ->method('find')
            ->with($token)
            ->will($this->returnValue(null));
        $app['authentication.suggestion-finder'] = $suggestionFinder;

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

        if (null === $user = $app['repo.users']->findByEmail('supermail@superprovider.com')) {
            $random = $app['random.low']->generateString(8);
            $user = $app['manipulator.user']->createUser('temporary-' . $random, $random, 'supermail@superprovider.com');
        }

        $accountCreatorMock = $this->getMockBuilder('Alchemy\Phrasea\Authentication\AccountCreator')
            ->disableOriginalConstructor()
            ->getMock();
        $accountCreatorMock->expects($this->once())
            ->method('create')
            ->with($app, 42, 'supermail@superprovider.com', [])
            ->will($this->returnValue($user));
        $accountCreatorMock->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $app['authentication.providers.account-creator'] = $accountCreatorMock;

        $this->logout($app);
        $client = $this->getClient();
        $client->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, $client->getResponse()->getStatusCode());

        $ret = $app['orm.em']->getRepository('Phraseanet:UsrAuthProvider')
            ->findBy(['user' => $user->getId(), 'provider' => 'provider-test']);

        $this->assertCount(1, $ret);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/prod/', $client->getResponse()->headers->get('location'));

        $this->assertTrue($app['authentication']->isAuthenticated());
        $app['manipulator.user']->delete($user);
    }

    public function testAuthenticateProviderCallbackWithRegistrationEnabled()
    {
        /** @var ProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);

        $provider->expects($this->once())->method('onCallback');

        $token = new Token($provider, 42);

        $provider->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $app = $this->getApplication();
        $suggestionFinder = $this->getMockBuilder('Alchemy\Phrasea\Authentication\SuggestionFinder')
            ->disableOriginalConstructor()
            ->getMock();

        $suggestionFinder->expects($this->once())
            ->method('find')
            ->with($token)
            ->will($this->returnValue(null));
        $app['authentication.suggestion-finder'] = $suggestionFinder;

        $accountCreatorMock = $this->getMockBuilder('Alchemy\Phrasea\Authentication\AccountCreator')
            ->disableOriginalConstructor()
            ->getMock();
        $accountCreatorMock->expects($this->never())
            ->method('create');
        $accountCreatorMock->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(false));
        $app['authentication.providers.account-creator'] = $accountCreatorMock;

        $this->logout($app);
        $client = $this->getClient();
        $client->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/login/register-classic/?providerId=provider-test', $client->getResponse()->headers->get('location'));

        $this->assertFalse($app['authentication']->isAuthenticated());
    }

    public function testAuthenticateProviderCallbackWithoutRegistrationEnabled()
    {
        /** @var ProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $this->addProvider('provider-test', $provider);
        $provider->expects($this->once())->method('onCallback');
        $token = new Token($provider, 42);

        $provider->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $app = $this->getApplication();
        $suggestionFinder = $this->getMockBuilder('Alchemy\Phrasea\Authentication\SuggestionFinder')
            ->disableOriginalConstructor()
            ->getMock();

        $suggestionFinder->expects($this->once())
            ->method('find')
            ->with($token)
            ->will($this->returnValue(null));
        $app['authentication.suggestion-finder'] = $suggestionFinder;

        $accountCreatorMock = $this->getMockBuilder('Alchemy\Phrasea\Authentication\AccountCreator')
            ->disableOriginalConstructor()
            ->getMock();
        $accountCreatorMock->expects($this->never())
            ->method('create');
        $accountCreatorMock->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(false));
        $app['authentication.providers.account-creator'] = $accountCreatorMock;

        $this->disableRegistration();
        $this->logout($app);
        $client = $this->getClient();
        $client->request('GET', '/login/provider/provider-test/callback/');

        $this->assertSame(302, $client->getResponse()->getStatusCode());

        $this->assertFalse($app['authentication']->isAuthenticated());
        $this->assertFlashMessagePopulated($app, 'error', 1);
        $this->assertSame('/login/', $client->getResponse()->headers->get('location'));
    }

    public function testGetRegistrationFields()
    {
        $fields = [
            'field' => [
                'required' => false,
            ],
        ];
        $app = $this->getApplication();
        $app['registration.fields'] = $fields;

        $this->logout($app);
        $client = $this->getClient();
        $client->request('GET', '/login/registration-fields/');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('application/json', $client->getResponse()->headers->get('content-type'));

        $this->assertEquals($fields, json_decode($client->getResponse()->getContent(), true));
    }

    public function testRegisterRedirectsNoAuthProvidersAvailable()
    {
        $app = $this->getApplication();
        $this->logout($app);

        $app['authentication.providers'] = new ProvidersCollection();

        $client = $this->getClient();
        $client->request('GET', '/login/register/');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/login/register-classic/', $client->getResponse()->headers->get('location'));
    }

    public function testRegisterDisplaysIfAuthProvidersAvailable()
    {
        $app = $this->getApplication();
        $this->logout($app);

        /** @var ProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $provider->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('test-provider'));

        $app['authentication.providers'] = new ProvidersCollection();
        $app['authentication.providers']->register($provider);

        $this->getClient()->request('GET', '/login/register/');

        $this->assertSame(200, $this->getClient()->getResponse()->getStatusCode());
    }

    public function testLoginPageWithIdleSessionTime()
    {
        $app = $this->getApplication();
        $this->logout($app);

        $bkp = $app['phraseanet.configuration']['session'];

        $app['phraseanet.configuration']['session'] = [
            'idle' => 10,
            'lifetime' => 60475,
        ];

        $client = $this->getClient();
        $crawler = $client->request('GET', '/login/');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('hidden', $crawler->filter('input[name="remember-me"]')->attr('type'));

        $app['phraseanet.configuration']['session'] = $bkp;
    }

    public function testLoginPageWithNoIdleSessionTime()
    {
        $app = $this->getApplication();
        $this->logout($app);

        $bkp = $app['phraseanet.configuration']['session'];

        $app['phraseanet.configuration']['session'] = [
            'idle' => 0,
            'lifetime' => 60475,
        ];

        $client = $this->getClient();
        $crawler = $client->request('GET', '/login/');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('checkbox', $crawler->filter('input[name="remember-me"]')->attr('type'));

        $app['phraseanet.configuration']['session'] = $bkp;
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

        $app = $this->getApplication();
        $app['orm.em'] = $this->createEntityManagerMock();

        $app['repo.usr-auth-providers'] = $repo;

        $repo = $this->getMockBuilder('Alchemy\Phrasea\Model\Repositories\BasketParticipantRepository')
            ->disableOriginalConstructor()
            ->getMock();

        /*
        $repo->expects($participants ? $this->once() : $this->never())
            ->method('findNotConfirmedAndNotRemindedParticipantsByExpireDate')
            ->will($this->returnValue([]));
        */

        $app['repo.basket-participants'] = $repo;
    }

    private function mockSuggestionFinder()
    {
        $app = $this->getApplication();

        $app['authentication.suggestion-finder'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\SuggestionFinder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Delete inscription request made by the current authenticathed user
     * @return void
     */
    private function deleteRequest()
    {
        $app = $this->getApplication();
        $query = $app['orm.em']->createQuery('DELETE FROM Phraseanet:Registration d WHERE d.user=?1');
        $query->setParameter(1, $this->getUser()->getId());
        $query->execute();
    }

    /**
     * Generate a new valid email adress
     * @return string
     */
    private function generateEmail()
    {
        $factory = new Factory();
        $generator = $factory->getLowStrengthGenerator();

        return $generator->generateString(8, TokenManipulator::LETTERS_AND_NUMBERS) . '_email@email.com';
    }

    private function disableTOU()
    {
        if (null === self::$termsOfUse) {
            self::$termsOfUse = [];
            foreach ($this->getApplication()->getDataboxes() as $databox) {
                self::$termsOfUse[$databox->get_sbas_id()] = $databox->get_cgus();

                foreach (self::$termsOfUse[$databox->get_sbas_id()] as $lng => $tou) {
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
        foreach ($this->getApplication()->getDataboxes() as $databox) {
            self::$termsOfUse[$databox->get_sbas_id()] = $databox->get_cgus();

            foreach (self::$termsOfUse[$databox->get_sbas_id()] as $lng => $tou) {
                $databox->update_cgus($lng, 'something', false);
            }
        }
    }

    private function resetTOU()
    {
        if (null === self::$termsOfUse) {
            return;
        }
        foreach ($this->getApplication()->getDataboxes() as $databox) {
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
                        'inactive' => [new Registration()],
                        'accepted' => [new Registration()],
                        'in-time' => [new Registration()],
                        'out-dated' => [new Registration()],
                        'pending' => [new Registration()],
                        'rejected' => [new Registration()],
                    ],
                    'by-collection' => [],
                ],
                'config' => [
                    'db-name' => 'a_db_name',
                    'cgu' => null,
                    'cgu-release' => null,
                    'can-register' => false,
                    'collections' => [
                        [
                            'coll-name' => 'a_coll_name',
                            'can-register' => false,
                            'cgu' => 'Some terms of use.',
                            'registration' => null,
                        ],
                        [
                            'coll-name' => 'an_other_coll_name',
                            'can-register' => false,
                            'cgu' => null,
                            'registration' => null,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function enableRegistration()
    {
        $app = $this->getApplication();
        $managerMock = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\RegistrationManager')
            ->setConstructorArgs([
                $app['phraseanet.appbox'],
                $app['repo.registrations'],
                $app['locale'],
            ])
            ->setMethods(['isRegistrationEnabled'])
            ->getMock();

        $managerMock->expects($this->any())->method('isRegistrationEnabled')->will($this->returnValue(true));
        $app['registration.manager'] = $managerMock;
    }

    private function disableRegistration()
    {
        $app = $this->getApplication();
        $managerMock = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\RegistrationManager')
            ->setConstructorArgs([
                $app['phraseanet.appbox'],
                $app['repo.registrations'],
                $app['locale'],
            ])
            ->setMethods(['isRegistrationEnabled'])
            ->getMock();

        $managerMock->expects($this->any())->method('isRegistrationEnabled')->will($this->returnValue(false));
        $app['registration.manager'] = $managerMock;
    }

    /**
     * @return User
     */
    private function getUser()
    {
        return self::$DI['user'];
    }

    private function mockNotificationsDeliverer(array &$expectedMails)
    {
        $app = $this->getApplication();
        $app['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        $app['notification.deliverer']->expects($this->any())
            ->method('deliver')
            ->will($this->returnCallback(function ($email, $receipt) use (&$expectedMails) {
                $this->assertTrue(isset($expectedMails[get_class($email)]));
                $expectedMails[get_class($email)]++;
            }));
    }
}
