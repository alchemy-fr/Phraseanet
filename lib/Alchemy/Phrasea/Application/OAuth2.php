<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @package     OAuth2 Connector
 *
 * @see         http://oauth.net/2/
 * @uses        http://code.google.com/p/oauth2-php/
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function($environment = 'prod') {

    $app = new PhraseaApplication($environment);

    $app['oauth'] = function($app) {
        return new \API_OAuth2_Adapter($app);
    };

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
            , $app['phraseanet.appbox']->get_registry()->get('GV_RootPath')
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

    $app->match('/authorize', $authorize_func)->method('GET|POST');

    /**
     *  TOKEN ENDPOINT
     *  Token endpoint - used to exchange an authorization grant for an access token.
     */
    $app->post('/token', function(\Silex\Application $app, Request $request) {

        $app['oauth']->grantAccessToken();
        ob_flush();
        flush();

        return;
    })->requireHttps();

    /**
     * Error Handler
     */
    $app->error(function (\Exception $e) use ($app) {
        if ($e instanceof NotFoundHttpException || $e instanceof \Exception_NotFound) {
            return new Response('The requested page could not be found.', 404, array('X-Status-Code' => 404));
        }

        $code = 500;
        $msg = 'We are sorry, but something went wrong';
        $headers = array();

        if ($e instanceof HttpExceptionInterface) {
            $headers = $e->getHeaders();
            $msg = $e->getMessage();
            $code = $e->getStatusCode();

            if (isset($headers['content-type']) && $headers['content-type'] == 'application/json') {
                $msg = json_encode(array('msg'  => $msg, 'code' => $code));
            }
        }

        return new Response($msg, $code, $headers);
    });

    return $app;
}, isset($environment) ? $environment : null);
