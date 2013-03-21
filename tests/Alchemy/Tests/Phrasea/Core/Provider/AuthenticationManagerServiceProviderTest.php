<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

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
        );
    }
}
