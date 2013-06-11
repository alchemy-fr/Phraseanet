<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\Provider\TokensServiceProvider;
use Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider;
use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;
use Silex\Application;

/**
 * @covers Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider
 */
class AuthenticationManagerServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication',
                'Alchemy\\Phrasea\\Authentication\\Authenticator',
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication.token-validator',
                'Alchemy\Phrasea\Authentication\Token\TokenValidator'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication.persistent-manager',
                'Alchemy\Phrasea\Authentication\PersistentCookie\Manager'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication.suggestion-finder',
                'Alchemy\Phrasea\Authentication\SuggestionFinder'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication.providers.factory',
                'Alchemy\Phrasea\Authentication\Provider\Factory'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication.providers',
                'Alchemy\Phrasea\Authentication\ProvidersCollection'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication.manager',
                'Alchemy\Phrasea\Authentication\Manager'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'auth.password-encoder',
                'Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'auth.old-password-encoder',
                'Alchemy\Phrasea\Authentication\Phrasea\OldPasswordEncoder'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'auth.native.failure-manager',
                'Alchemy\Phrasea\Authentication\Phrasea\FailureManager'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'auth.native',
                'Alchemy\Phrasea\Authentication\Phrasea\PasswordAuthenticationInterface'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication.providers.account-creator',
                'Alchemy\Phrasea\Authentication\AccountCreator'
            ),
        );
    }

    public function testFailureManagerAttemptsConfiguration()
    {
        $app = new Application();
        $app->register(new TokensServiceProvider());
        $app->register(new AuthenticationManagerServiceProvider());
        $app->register(new ConfigurationServiceProvider());

        $app['phraseanet.configuration'] = $conf = $app['phraseanet.configuration']->getConfig();
        $conf['authentication']['captcha']['trials-before-failure'] = 42;
        $app['phraseanet.configuration'] = $conf;

        $app['EM'] = $this->getMockBuilder('Doctrine\Orm\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $app['recaptcha'] = $this->getMockBuilder('Neutron\ReCaptcha\ReCaptcha')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $app['auth.native.failure-manager'];
        $this->assertEquals(42, $manager->getTrials());
    }

    public function testFailureAccountCreator()
    {
        $app = new PhraseaApplication();
        $app->register(new ConfigurationServiceProvider());

        $app['phraseanet.configuration'] = $conf = $app['phraseanet.configuration']->getConfig();
        $conf['authentication']['auto-create'] = array(
            'enabled' => true,
            'templates' => array(),
        );
        $app['phraseanet.configuration'] = $conf;

        $app['authentication.providers.account-creator'];
    }

    public function testAuthNativeWithCaptchaEnabled()
    {
        $app = new Application();
        $app->register(new AuthenticationManagerServiceProvider());
        $app->register(new ConfigurationServiceProvider());
        $app['phraseanet.registry'] = $this->getMockBuilder('registry')
            ->disableOriginalConstructor()
            ->getMock();
        $phpunit = $this;
        $app['phraseanet.registry']->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($key) use ($phpunit) {
                switch ($key) {
                    case 'GV_sit':
                        return mt_rand();
                    default:
                        $phpunit->fail(sprintf('Unknown key %s', $key));
                }
            }));
        $app['phraseanet.appbox'] = self::$DI['app']['phraseanet.appbox'];

        $app['phraseanet.configuration'] = $conf = $app['phraseanet.configuration']->getConfig();
        $conf['authentication']['captcha'] = array(
            'enabled' => true,
        );
        $app['phraseanet.configuration'] = $conf;

        $app['EM'] = $this->getMockBuilder('Doctrine\Orm\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $app['recaptcha'] = $this->getMockBuilder('Neutron\ReCaptcha\ReCaptcha')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertInstanceOf('Alchemy\Phrasea\Authentication\Phrasea\FailureHandledNativeAuthentication', $app['auth.native']);
    }

    public function testAuthNativeWithCaptchaDisabled()
    {
        $app = new Application();
        $app->register(new AuthenticationManagerServiceProvider());
        $app->register(new ConfigurationServiceProvider());
        $app['phraseanet.registry'] = $this->getMockBuilder('registry')
            ->disableOriginalConstructor()
            ->getMock();
        $phpunit = $this;
        $app['phraseanet.registry']->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($key) use ($phpunit) {
                switch ($key) {
                    case 'GV_sit':
                        return mt_rand();
                    default:
                        $phpunit->fail(sprintf('Unknown key %s', $key));
                }
            }));
        $app['phraseanet.appbox'] = self::$DI['app']['phraseanet.appbox'];

        $app['phraseanet.configuration'] = $conf = $app['phraseanet.configuration']->getConfig();
        $conf['authentication']['captcha'] = array(
            'enabled' => false,
        );
        $app['phraseanet.configuration'] = $conf;

        $app['EM'] = $this->getMockBuilder('Doctrine\Orm\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $app['recaptcha'] = $this->getMockBuilder('Neutron\ReCaptcha\ReCaptcha')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertInstanceOf('Alchemy\Phrasea\Authentication\Phrasea\NativeAuthentication', $app['auth.native']);
    }

    public function testAccountCreator()
    {
        $app = new PhraseaApplication();

        $random = $app['tokens'];
        $template1 = \User_Adapter::create(self::$DI['app'], 'template' . $random->generatePassword(), $random->generatePassword(), null, false);
        $template1->set_template(self::$DI['user']);
        $template2 = \User_Adapter::create(self::$DI['app'], 'template' . $random->generatePassword(), $random->generatePassword(), null, false);
        $template2->set_template(self::$DI['user']);

        $app['phraseanet.configuration'] = $conf = $app['phraseanet.configuration']->getConfig();
        $conf['authentication']['auto-create'] = array(
            'enabled' => true,
            'templates' => array(
                $template1->get_id(),
                $template2->get_login()
            )
        );
        $app['phraseanet.configuration'] = $conf;

        $this->assertEquals(array($template1, $template2), $app['authentication.providers.account-creator']->getTemplates());

        $template1->delete();
        $template2->delete();
    }
}
