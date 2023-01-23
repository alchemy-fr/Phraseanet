<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Api;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Api\OAuth2Controller;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;

class OAuth2 extends Api implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.oauth2'] = $app->share(function (PhraseaApplication $app) {
            return (new OAuth2Controller($app))
                ->setDispatcher($app['dispatcher']);
        });
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        $firewall = $this->getFirewall($app);

        $requireUnauthenticated = function () use ($firewall) {
            if (null !== $response = $firewall->requireNotAuthenticated()) {
                return $response;
            }

            return null;
        };

        if (! $this->isApiEnabled($app)) {
            return $app['controllers_factory'];
        }

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->match('/authorize', 'controller.oauth2:authorizeAction')
            ->method('GET|POST')
            ->bind('oauth2_authorize');

        $controllers->post('/token', 'controller.oauth2:tokenAction');

        $controllers->get('/provider/{providerId}/authorize/', 'controller.oauth2:authorizeWithProviderAction')
            ->before($requireUnauthenticated)
            ->bind('oauth2_provider_authorize');

        // AuthProviders callbacks
        $controllers->get('/provider/{providerId}/callback/', 'controller.oauth2:authorizeCallbackAction')
            ->before($requireUnauthenticated)
            ->bind('login_authentication_provider_callback');

        return $controllers;
    }
}
