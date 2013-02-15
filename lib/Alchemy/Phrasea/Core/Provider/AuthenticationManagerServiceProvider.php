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
use Alchemy\Phrasea\Authentication\Provider\Facebook;
use Alchemy\Phrasea\Authentication\Phrasea\FailureManager;
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


//        $app['authentication.suggestion-finder'] = $app->share(function (Application $app) {
//            return new SuggestionFinder($app);
//        });

        $app['authentication.providers'] = $app->share(function (Application $app) {

//            $config = array();
//            $config['appId'] = '252378391562465';
//            $config['secret'] = 'd9df4bb1ad34aab4f6728b4076e1f9c4';
//
//            $facebook = new \Facebook($config);

            $providers = new ProvidersCollection();
//            $providers->register(new Facebook($facebook, $app['url_generator']));

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
            return new FailureManager($app['EM'], $app['recaptcha']);
        });

        $app['auth.native'] = $app->share(function (Application $app) {
            return new NativeAuthentication($app['auth.password-encoder'], $app['auth.old-password-encoder'], $app['auth.native.failure-manager'], $app['phraseanet.appbox']->get_connection());
        });
    }

    public function boot(Application $app)
    {
    }
}
