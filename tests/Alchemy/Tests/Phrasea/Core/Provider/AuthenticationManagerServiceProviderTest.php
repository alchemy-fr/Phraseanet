<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Authentication\AccountCreator;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Authentication\Manager as AuthenticationManager;
use Alchemy\Phrasea\Authentication\PersistentCookie\Manager as PersistentCookieManager;
use Alchemy\Phrasea\Authentication\Phrasea\FailureHandledNativeAuthentication;
use Alchemy\Phrasea\Authentication\Phrasea\FailureManager;
use Alchemy\Phrasea\Authentication\Phrasea\NativeAuthentication;
use Alchemy\Phrasea\Authentication\Phrasea\OldPasswordEncoder;
use Alchemy\Phrasea\Authentication\Phrasea\PasswordAuthenticationInterface;
use Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder;
use Alchemy\Phrasea\Authentication\Provider\Factory;
use Alchemy\Phrasea\Authentication\ProvidersCollection;
use Alchemy\Phrasea\Authentication\SuggestionFinder;
use Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider;
use Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider;
use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\AuthFailureRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider
 */
class AuthenticationManagerServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                AuthenticationManagerServiceProvider::class,
                'authentication',
                Authenticator::class,
            ],
            [
                AuthenticationManagerServiceProvider::class,
                'authentication.persistent-manager',
                PersistentCookieManager::class
            ],
            [
                AuthenticationManagerServiceProvider::class,
                'authentication.suggestion-finder',
                SuggestionFinder::class
            ],
            [
                AuthenticationManagerServiceProvider::class,
                'authentication.providers.factory',
                Factory::class
            ],
            [
                AuthenticationManagerServiceProvider::class,
                'authentication.providers',
                ProvidersCollection::class
            ],
            [
                AuthenticationManagerServiceProvider::class,
                'authentication.manager',
                AuthenticationManager::class
            ],
            [
                AuthenticationManagerServiceProvider::class,
                'auth.password-encoder',
                PasswordEncoder::class
            ],
            [
                AuthenticationManagerServiceProvider::class,
                'auth.old-password-encoder',
                OldPasswordEncoder::class
            ],
            [
                AuthenticationManagerServiceProvider::class,
                'auth.native.failure-manager',
                FailureManager::class
            ],
            [
                AuthenticationManagerServiceProvider::class,
                'auth.native',
                PasswordAuthenticationInterface::class
            ],
            [
                AuthenticationManagerServiceProvider::class,
                'authentication.providers.account-creator',
                AccountCreator::class
            ],
        ];
    }

    public function testFailureManagerAttemptsConfiguration()
    {
        $app = $this->loadApp();

        $bkp = $app['conf']->get('authentication');

        $app['conf']->set(['registry', 'webservices', 'trials-before-display'], 42);

        //$app['orm.em'] = $this->createEntityManagerMock();
        $app['recaptcha'] = $this->createReCaptchaMock();

        $manager = $app['auth.native.failure-manager'];
        $this->assertEquals(42, $manager->getTrials());

        $app['conf']->set('authentication', $bkp);
    }

    public function testFailureAccountCreator()
    {
        $app = $this->getApplication();

        $bkp = $app['conf']->get('authentication');

        $app->register(new ConfigurationServiceProvider());
        $app['conf']->set(['authentication', 'auto-create'], ['templates' => []]);
        $app['authentication.providers.account-creator'];

        $app['conf']->set('authentication', $bkp);
    }

    public function testAuthNativeWithCaptchaEnabled()
    {
        $app = $this->loadApp();
        $app['root.path'] = __DIR__ . '/../../../../../../';
        $app->register(new AuthenticationManagerServiceProvider());
        $app->register(new ConfigurationServiceProvider());
        $app->register(new RepositoriesServiceProvider());
        $app['phraseanet.appbox'] = self::$DI['app']['phraseanet.appbox'];

        $app['conf']->set(['registry', 'webservices'], ['captchas-enabled' => true]);
        $bkp = $app['conf']->get('authentication');

        $app['orm.em'] = $this->createEntityManagerMock();
        $app['repo.users'] = $this->createUserRepositoryMock();
        $app['repo.auth-failures'] = $this->getMockBuilder(AuthFailureRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $app['recaptcha'] = $this->createReCaptchaMock();

        $this->assertInstanceOf(FailureHandledNativeAuthentication::class, $app['auth.native']);

        $app['conf']->set('authentication', $bkp);
    }

    public function testAuthNativeWithCaptchaDisabled()
    {
        $app = $this->loadApp();
        $app['root.path'] = __DIR__ . '/../../../../../../';
        $app->register(new AuthenticationManagerServiceProvider());
        $app->register(new ConfigurationServiceProvider());
        $app['phraseanet.appbox'] = self::$DI['app']['phraseanet.appbox'];

        $app['conf']->set(['registry', 'webservices'], ['captchas-enabled' => false]);
        $bkp = $app['conf']->get('authentication');

        $app['orm.em'] = $this->createEntityManagerMock();
        $app['repo.users'] = $this->createUserRepositoryMock();
        $app['recaptcha'] = $this->createReCaptchaMock();

        $this->assertInstanceOf(NativeAuthentication::class, $app['auth.native']);

        $app['conf']->set('authentication', $bkp);
    }

    public function testAccountCreator()
    {
        $app = $this->getApplication();
        $template1 = $user = $app['manipulator.user']->createTemplate('template1', self::$DI['user']);
        $template2 = $user = $app['manipulator.user']->createTemplate('template2', self::$DI['user']);

        $bkp = $app['conf']->get('authentication');

        $app['conf']->set(['authentication', 'auto-create'], ['templates' => [$template1->getId(), $template2->getId()]]);

        $this->assertEquals([$template1->getLogin(), $template2->getLogin()], array_map(function (User $user) {
            return $user->getLogin();
        }, $app['authentication.providers.account-creator']->getTemplates()));

        $this->removeUser($app, $template1);
        $this->removeUser($app, $template2);

        $app['conf']->set('authentication', $bkp);
    }

    private function createUserRepositoryMock()
    {
        return $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ReCaptcha|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createReCaptchaMock()
    {
        return $this->getMockBuilder(\ReCaptcha\ReCaptcha::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
