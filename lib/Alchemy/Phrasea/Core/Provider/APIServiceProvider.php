<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\ControllerProvider\Api\V2;
use Silex\Application;
use Silex\ServiceProviderInterface;

class APIServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['oauth2-server'] = $app->share(function ($app) {
            return new \API_OAuth2_Adapter($app, ['api_version' => $app['api.default_version']]);
        });
        $app['token'] = $app->share(function (Application $app) {
            /** @var \API_OAuth2_Adapter $oauth2 */
            $oauth2 = $app['oauth2-server'];

            $token = $oauth2->getToken();

            return $token ? $app['repo.api-oauth-tokens']->find($token) : null;
        });

        $app['api.default_version'] = V2::VERSION;
    }

    public function boot(Application $app)
    {
        Result::setDefaultVersion($app['api.default_version']);
    }
}
