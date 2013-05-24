<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Api;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;

class Oauth2 implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['oauth'] = $app->share(function($app) {
            return new \API_OAuth2_Adapter($app);
        });

        /**
         * AUTHORIZE ENDPOINT
         *
         * Authorization endpoint - used to obtain authorization from the
         * resource owner via user-agent redirection.
         */
        $authorize_func = function() use ($app) {
            $request = $app['request'];
            $oauth2_adapter = $app['oauth'];

            //Check for auth params, send error or redirect if not valid
            $params = $oauth2_adapter->getAuthorizationRequestParameters($request);

            $app_authorized = false;
            $errorMessage = false;

            $client = \API_OAuth2_Application::load_from_client_id($app, $params['client_id']);

            $oauth2_adapter->setClient($client);

            $action_accept = $request->get("action_accept");
            $action_login = $request->get("action_login");

            $template = "api/auth/end_user_authorization.html.twig";

            $custom_template = sprintf(
                "%sconfig/templates/web/api/auth/end_user_authorization/%s.html.twig"
                , $app['phraseanet.registry']->get('GV_RootPath')
                , $client->get_id()
            );

            if (file_exists($custom_template)) {
                $template = sprintf(
                    'api/auth/end_user_authorization/%s.html.twig'
                    , $client->get_id()
                );
            }

            if (!$app->isAuthenticated()) {
                if ($action_login !== null) {
                    try {
                        $auth = new \Session_Authentication_Native(
                                $app, $request->get("login"), $request->get("password")
                        );

                        $app->openAccount($auth);
                    } catch (\Exception $e) {
                        return new Response($app['twig']->render($template, array("auth" => $oauth2_adapter)));
                    }
                } else {
                    return new Response($app['twig']->render($template, array("auth" => $oauth2_adapter)));
                }
            }

            //check if current client is already authorized by current user
            $user_auth_clients = \API_OAuth2_Application::load_authorized_app_by_user(
                    $app
                    , $app['phraseanet.user']
            );

            foreach ($user_auth_clients as $auth_client) {
                if ($client->get_client_id() == $auth_client->get_client_id()) {
                    $app_authorized = true;
                }
            }

            $account = $oauth2_adapter->updateAccount($app['phraseanet.user']->get_id());

            $params['account_id'] = $account->get_id();

            if (!$app_authorized && $action_accept === null) {
                $params = array(
                    "auth"         => $oauth2_adapter,
                    "errorMessage" => $errorMessage,
                );

                return new Response($app['twig']->render($template, $params));
            } elseif (!$app_authorized && $action_accept !== null) {
                $app_authorized = (Boolean) $action_accept;
                $account->set_revoked(!$app_authorized);
            }

            //if native app show template
            if ($oauth2_adapter->isNativeApp($params['redirect_uri'])) {
                $params = $oauth2_adapter->finishNativeClientAuthorization($app_authorized, $params);

                return new Response($app['twig']->render("api/auth/native_app_access_token.html.twig", $params));
            } else {
                $oauth2_adapter->finishClientAuthorization($app_authorized, $params);
            }
        };

        $controllers->match('/authorize', $authorize_func)->method('GET|POST');

        /**
         *  TOKEN ENDPOINT
         *  Token endpoint - used to exchange an authorization grant for an access token.
         */
        $controllers->post('/token', function(\Silex\Application $app, Request $request) {
            if ( ! $request->isSecure()) {
                throw new HttpException(400, 'This route requires the use of the https scheme', null, array('content-type' => 'application/json'));
            }

            $app['oauth']->grantAccessToken();
            ob_flush();
            flush();

            return;
        });

        return $controllers;
    }
}
