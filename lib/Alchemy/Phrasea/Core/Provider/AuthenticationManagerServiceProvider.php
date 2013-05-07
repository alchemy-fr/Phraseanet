<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Authentication\Manager;
use Alchemy\Phrasea\Authentication\ProvidersCollection;
use Alchemy\Phrasea\Authentication\Phrasea\FailureManager;
use Alchemy\Phrasea\Authentication\Provider\Factory as ProviderFactory;
use Alchemy\Phrasea\Authentication\PersistentCookie\Manager as CookieManager;
use Alchemy\Phrasea\Authentication\Phrasea\NativeAuthentication;
use Alchemy\Phrasea\Authentication\Phrasea\OldPasswordEncoder;
use Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder;
use Alchemy\Phrasea\Authentication\SuggestionFinder;
use Alchemy\Phrasea\Authentication\Token\TokenValidator;
use Silex\Application;
use Silex\ServiceProviderInterface;

class AuthenticationManagerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['authentication'] = $app->share(function (Application $app){
            return new Authenticator($app, $app['browser'], $app['session'], $app['EM'], $app['phraseanet.registry']);
        });

        $app['authentication.token-validator'] = $app->share(function (Application $app){
            return new TokenValidator($app);
        });

        $app['authentication.persistent-manager'] = $app->share(function (Application $app){
            return new CookieManager($app['auth.password-encoder'], $app['EM'], $app['browser']);
        });

        $app['authentication.suggestion-finder'] = $app->share(function (Application $app) {
            return new SuggestionFinder($app);
        });

        $app['authentication.providers.factory'] = $app->share(function (Application $app) {
           return new ProviderFactory($app['url_generator'], $app['session']);
        });

        $app['authentication.providers'] = $app->share(function (Application $app) {

            $providers = new ProvidersCollection();

            $authConf = $app['phraseanet.configuration']->get('authentication');
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

        $app['auth.password-encoder'] = $app->share(function (Application $app) {
            return new PasswordEncoder($app['phraseanet.registry']->get('GV_sit'));
        });

        $app['auth.old-password-encoder'] = $app->share(function (Application $app) {
            return new OldPasswordEncoder();
        });

        $app['auth.native.failure-manager'] = $app->share(function (Application $app) {

            $authConf = $app['phraseanet.configuration']->get('authentication');

            return new FailureManager($app['EM'], $app['recaptcha'], isset($authConf['trials-before-failure']) ? $authConf['trials-before-failure'] : 9);
        });

        $app['auth.native'] = $app->share(function (Application $app) {
            return new NativeAuthentication($app['auth.password-encoder'], $app['auth.old-password-encoder'], $app['auth.native.failure-manager'], $app['phraseanet.appbox']->get_connection());
        });
    }

    public function boot(Application $app)
    {
    }
}
