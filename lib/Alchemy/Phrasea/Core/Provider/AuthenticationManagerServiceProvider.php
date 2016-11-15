<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Authentication\AccountCreator;
use Alchemy\Phrasea\Authentication\Manager;
use Alchemy\Phrasea\Authentication\ProvidersCollection;
use Alchemy\Phrasea\Authentication\Phrasea\FailureManager;
use Alchemy\Phrasea\Authentication\Provider\Factory as ProviderFactory;
use Alchemy\Phrasea\Authentication\PersistentCookie\Manager as CookieManager;
use Alchemy\Phrasea\Authentication\Phrasea\FailureHandledNativeAuthentication;
use Alchemy\Phrasea\Authentication\Phrasea\NativeAuthentication;
use Alchemy\Phrasea\Authentication\Phrasea\OldPasswordEncoder;
use Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder;
use Alchemy\Phrasea\Authentication\RecoveryService;
use Alchemy\Phrasea\Authentication\RegistrationService;
use Alchemy\Phrasea\Authentication\SuggestionFinder;
use Silex\Application;
use ReCaptcha\ReCaptcha;
use Silex\ServiceProviderInterface;
use Alchemy\Phrasea\Core\Event\Subscriber\PersistentCookieSubscriber;

class AuthenticationManagerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['authentication'] = $app->share(function (Application $app) {
            return new Authenticator($app, $app['browser'], $app['session'], $app['orm.em']);
        });

        if($app['conf']->get(['registry', 'webservices', 'recaptcha-private-key']) !== ""){
            $app['recaptcha'] = $app->share(function (Application $app) {
                return new ReCaptcha($app['conf']->get(['registry', 'webservices', 'recaptcha-private-key']));
            });
        }
        $app['authentication.persistent-manager'] = $app->share(function (Application $app) {
            return new CookieManager($app['auth.password-encoder'], $app['repo.sessions'], $app['browser']);
        });

        $app['authentication.suggestion-finder'] = $app->share(function (Application $app) {
            return new SuggestionFinder($app['repo.users']);
        });

        $app['authentication.providers.factory'] = $app->share(function (Application $app) {
           return new ProviderFactory($app['url_generator'], $app['session']);
        });

        $app['authentication.providers.account-creator'] = $app->share(function (Application $app) {
            $authConf = $app['conf']->get('authentication');
            $templates = array_filter(array_map(function ($templateId) use ($app) {
                try {
                    if (is_int($templateId) || ctype_digit($templateId)) {
                        return $app['repo.users']->find($templateId);
                    }

                    if (false !== $templateId) {
                        return $app['repo.users']->find($templateId);
                    }
                } catch (\Exception $e) {

                }
            }, $authConf['auto-create']['templates']));

            $enabled = $app['conf']->get(['registry', 'registration', 'auto-register-enabled']) && $app['registration.manager']->isRegistrationEnabled();

            return new AccountCreator($app['random.medium'], $app->getApplicationBox(), $enabled, $templates);
        });

        $app['authentication.providers'] = $app->share(function (Application $app) {

            $providers = new ProvidersCollection();

            $authConf = $app['conf']->get('authentication');
            foreach ($authConf['providers'] as $providerId => $data) {
                if (isset($data['enabled']) && false === $data['enabled']) {
                    continue;
                }
                $providers->register($app['authentication.providers.factory']->build($providerId, $data['options']));
            }

            return $providers;
        });

        $app['authentication.manager'] = $app->share(function (Application $app) {
            return new Manager($app['authentication'], $app['authentication.providers']);
        });

        $app['authentication.recovery_service'] = $app->share(function (Application $app) {
            return new RecoveryService(
                $app,
                $app['notification.deliverer'],
                $app['manipulator.token'],
                $app['repo.tokens'],
                $app['manipulator.user'],
                $app['repo.users'],
                $app['url_generator']
            );
        });

        $app['authentication.registration_service'] = $app->share(function (Application $app) {
            return new RegistrationService(
                $app,
                $app['phraseanet.appbox'],
                $app['acl'],
                $app['conf'],
                $app['orm.em'],
                $app['dispatcher'],
                $app['authentication.providers'],
                $app['repo.usr-auth-providers'],
                $app['repo.users'],
                $app['manipulator.user'],
                $app['manipulator.token'],
                $app['repo.tokens'],
                $app['manipulator.registration'],
                $app['registration.manager']
            );
        });

        $app['auth.password-encoder'] = $app->share(function (Application $app) {
            return new PasswordEncoder($app['conf']->get(['main','key']));
        });

        $app['auth.old-password-encoder'] = $app->share(function (Application $app) {
            return new OldPasswordEncoder();
        });

        $app['auth.native.failure-manager'] = $app->share(function (Application $app) {
            $authConf = $app['conf']->get(['registry', 'webservices']);

            return new FailureManager($app['repo.auth-failures'], $app['orm.em'], $app['recaptcha'], isset($authConf['trials-before-display']) ? $authConf['trials-before-display'] : 9);
        });

        $app['auth.password-checker'] = $app->share(function (Application $app) {
            return new NativeAuthentication($app['auth.password-encoder'], $app['auth.old-password-encoder'], $app['manipulator.user'], $app['repo.users']);
        });

        $app['auth.native'] = $app->share(function (Application $app) {
            $authConf = $app['conf']->get('registry');

            if ($authConf['webservices']['captchas-enabled']) {

                return new FailureHandledNativeAuthentication(
                    $app['auth.password-checker'],
                    $app['auth.native.failure-manager']
                );
            } else {

                return $app['auth.password-checker'];
            }
        });
    }

    public function boot(Application $app)
    {
        $app['dispatcher'] = $app->share(
            $app->extend('dispatcher', function ($dispatcher, Application $app) {
                $dispatcher->addSubscriber(new PersistentCookieSubscriber($app));

                return $dispatcher;
            })
        );
    }
}
