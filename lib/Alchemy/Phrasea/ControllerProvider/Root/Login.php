<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Root;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Controller\Root\LoginController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Login implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;
    
    public function register(Application $app)
    {
        $app['login.controller'] = $app->share(function (PhraseaApplication $app) {
            return (new LoginController($app))
                ->setDelivererLocator(new LazyLocator($app, 'notification.deliverer'))
                ->setDispatcher($app['dispatcher'])
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'))
            ;
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(Application $app)
    {
        $controllers = $this->createCollection($app);
        $firewall = $this->getFirewall($app);
        
        $requireUnauthenticated = function () use ($firewall) {
            if (null !== $response = $firewall->requireNotAuthenticated()) {
                return $response;
            }
            
            return null;
        }; 

        // Displays the homepage
        $controllers->get('/', 'login.controller:login')
            ->before($requireUnauthenticated)
            ->bind('homepage');

        // Authentication end point
        $controllers->post('/authenticate/', 'login.controller:authenticate')
            ->before($requireUnauthenticated)
            ->bind('login_authenticate');

        // Guest access end point
        $controllers->match('/authenticate/guest/', 'login.controller:authenticateAsGuest')
            ->before($requireUnauthenticated)
            ->bind('login_authenticate_as_guest')
            ->method('GET|POST');

        // Authenticate with an AuthProvider
        $controllers->get('/provider/{providerId}/authenticate/', 'login.controller:authenticateWithProvider')
            ->before($requireUnauthenticated)
            ->bind('login_authentication_provider_authenticate');

        // AuthProviders callbacks
        $controllers->get('/provider/{providerId}/callback/', 'login.controller:authenticationCallback')
            ->before($requireUnauthenticated)->bind('login_authentication_provider_callback');

        // Logout end point
        $logoutController = $controllers->get('/logout/', 'login.controller:logout')
            ->bind('logout');

        $firewall->addMandatoryAuthentication($logoutController);

        // Registration end point ; redirects to classic registration or AuthProvider registration
        $controllers->get('/register/', 'login.controller:displayRegisterForm')
            ->before($requireUnauthenticated)
            ->bind('login_register');

        // Classic registration end point
        $controllers->match('/register-classic/', 'login.controller:doRegistration')
            ->before($requireUnauthenticated)
            ->bind('login_register_classic');

        // Provide a JSON serialization of registration fields configuration
        $controllers->get('/registration-fields/', 'login.controller:getRegistrationFieldsAction')
            ->bind('login_registration_fields');

        // Unlocks an email address that is currently locked
        $controllers->get('/register-confirm/', 'login.controller:registerConfirm')
            ->before($requireUnauthenticated)->bind('login_register_confirm');

        // Displays a form to send an account unlock email again
        $controllers->get('/send-mail-confirm/', 'login.controller:sendConfirmMail')
            ->before($requireUnauthenticated)->bind('login_send_mail');

        // Forgot password end point
        $controllers->match('/forgot-password/', 'login.controller:forgotPassword')
            ->before($requireUnauthenticated)->bind('login_forgot_password');

        // Renew password end point
        $controllers->match('/renew-password/', 'login.controller:renewPassword')
            ->before($requireUnauthenticated)->bind('login_renew_password');

        // Displays Terms of use
        $controllers->get('/cgus', 'login.controller:getCgusAction')
            ->bind('login_cgus');

        $controllers->get('/language.json', 'login.controller:getLanguage')
            ->bind('login_language');

        return $controllers;
    }
}
