<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\Provider\TokensServiceProvider;
use Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider;
use Silex\Application;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

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
                'Alchemy\\Phrasea\\Authentication\\Authenticator'
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
                'Alchemy\Phrasea\Authentication\Phrasea\NativeAuthentication'
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

        $app['phraseanet.configuration'] = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration')
            ->disableOriginalConstructor()
            ->getMock();

        $app['phraseanet.configuration']->expects($this->once())
            ->method('get')
            ->with('authentication')
            ->will($this->returnValue(array('trials-before-failure' => 42)));

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

        $conf = $app['phraseanet.configuration'];

        $app['phraseanet.configuration'] = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration')
            ->disableOriginalConstructor()
            ->getMock();

        $app['phraseanet.configuration']->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($key) use ($conf) {
                if ($key === 'authentication') {
                    return array(
                        'auto-create' => array(
                            'enabled'   => true,
                            'templates' => array()
                        )
                    );
                } else {
                    return $conf->get($key);
                }
            }));
        $app['phraseanet.configuration']->expects($this->any())
            ->method('getPhraseanet')
            ->will($this->returnValue(new ParameterBag($conf->get('phraseanet'))));

        $conn = $conf->getSpecifications()->getConnexions();

        $app['phraseanet.configuration']->expects($this->any())
            ->method('getConnexion')
            ->will($this->returnValue(new ParameterBag($conn['main_connexion'])));

        $app['authentication.providers.account-creator'];
    }

    public function testAccountCreator()
    {
        $app = new PhraseaApplication();

        $random = $app['tokens'];
        $template1 = \User_Adapter::create(self::$DI['app'], 'template' . $random->generatePassword(), $random->generatePassword(), null, false);
        $template1->set_template(self::$DI['user']);
        $template2 = \User_Adapter::create(self::$DI['app'], 'template' . $random->generatePassword(), $random->generatePassword(), null, false);
        $template2->set_template(self::$DI['user']);

        $conf = $app['phraseanet.configuration'];

        $app['phraseanet.configuration'] = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration')
            ->disableOriginalConstructor()
            ->getMock();

        $app['phraseanet.configuration']->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($key) use ($template1, $template2, $conf) {
                if ($key === 'authentication') {
                    return array(
                        'auto-create' => array(
                            'enabled'   => true,
                            'templates' => array(
                                $template1->get_id(),
                                $template2->get_login()
                            )
                        )
                    );
                } else {
                    return $conf->get($key);
                }
            }));

        $app['phraseanet.configuration']->expects($this->any())
            ->method('getPhraseanet')
            ->will($this->returnValue(new ParameterBag($conf->get('phraseanet'))));

        $conn = $conf->getSpecifications()->getConnexions();

        $app['phraseanet.configuration']->expects($this->any())
            ->method('getConnexion')
            ->will($this->returnValue(new ParameterBag($conn['main_connexion'])));

        $this->assertEquals(array($template1, $template2), $app['authentication.providers.account-creator']->getTemplates());

        $template1->delete();
        $template2->delete();
    }
}
