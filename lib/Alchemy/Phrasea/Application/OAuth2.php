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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 *
 * @package     OAuth2 Connector
 *
 * @see         http://oauth.net/2/
 * @uses        http://code.google.com/p/oauth2-php/
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function() {
            $app = new \Silex\Application();

            $app['Core'] = \bootstrap::getCore();

            $app['appbox'] = function() use ($app) {
                    return \appbox::get_instance($app['Core']);
                };

            $app['oauth'] = function($app) {
                    return new \API_OAuth2_Adapter($app['appbox']);
                };

            /**
             * Protected Closure
             * @var Closure
             * @return Symfony\Component\HttpFoundation\Response
             */
            $app['response'] = $app->protect(function ($template, $variable) use ($app) {
                    /* @var $twig \Twig_Environment */
                    $twig = $app['Core']->getTwig();

                    $response = new Response(
                            $twig->render($template, $variable)
                            , 200
                            , array('Content-Type' => 'text/html')
                    );
                    $response->setCharset('UTF-8');

                    return $response;
                });

            /* * *******************************************************************
             *                        AUTHENTIFICATION API
             */

            /**
             *  AUTHORIZE ENDPOINT
             *
             * Authorization endpoint - used to obtain authorization from the
             * resource owner via user-agent redirection.
             */
            $authorize_func = function() use ($app) {
                    $request = $app['request'];
                    $oauth2_adapter = $app['oauth'];
                    /* @var $twig \Twig_Environment */
                    $twig = $app['Core']->getTwig();
                    $session = $app['appbox']->get_session();

                    //Check for auth params, send error or redirect if not valid
                    $params = $oauth2_adapter->getAuthorizationRequestParameters($request);

                    $authenticated = $session->is_authenticated();
                    $app_authorized = false;
                    $errorMessage = false;

                    $client = \API_OAuth2_Application::load_from_client_id($app['appbox'], $params['client_id']);

                    $oauth2_adapter->setClient($client);

                    $action_accept = $request->get("action_accept", null);
                    $action_login = $request->get("action_login", null);

                    $template = "api/auth/end_user_authorization.twig";

                    $custom_template = sprintf(
                        "%sconfig/templates/web/api/auth/end_user_authorization/%s.twig"
                        , $app['appbox']->get_registry()->get('GV_RootPath')
                        , $client->get_id()
                    );

                    if (file_exists($custom_template)) {
                        $template = sprintf(
                            'api/auth/end_user_authorization/%s.twig'
                            , $client->get_id()
                        );
                    }

                    if ( ! $authenticated) {
                        if ($action_login !== null) {
                            try {
                                $login = $request->get("login");
                                $password = $request->get("password");
                                $auth = new \Session_Authentication_Native($app['appbox'], $login, $password);
                                $session->authenticate($auth);
                            } catch (\Exception $e) {
                                $params = array(
                                    "auth"         => $oauth2_adapter
                                    , "session"      => $session
                                    , "errorMessage" => true
                                    , "user"         => $app['Core']->getAuthenticatedUser()
                                );
                                $html = $twig->render($template, $params);

                                return new Response($html, 200, array("content-type" => "text/html"));
                            }
                        } else {
                            $params = array(
                                "auth"         => $oauth2_adapter
                                , "session"      => $session
                                , "errorMessage" => $errorMessage
                                , "user"         => $app['Core']->getAuthenticatedUser()
                            );
                            $html = $twig->render($template, $params);

                            return new Response($html, 200, array("content-type" => "text/html"));
                        }
                    }

                    //check if current client is already authorized by current user
                    $user_auth_clients = \API_OAuth2_Application::load_authorized_app_by_user(
                            $app['appbox']
                            , $app['Core']->getAuthenticatedUser()
                    );

                    foreach ($user_auth_clients as $auth_client) {
                        if ($client->get_client_id() == $auth_client->get_client_id())
                            $app_authorized = true;
                    }

                    $account = $oauth2_adapter->updateAccount($session->get_usr_id());

                    $params['account_id'] = $account->get_id();

                    if ( ! $app_authorized && $action_accept === null) {
                        $params = array(
                            "auth"         => $oauth2_adapter
                            , "session"      => $session
                            , "errorMessage" => $errorMessage
                            , "user"         => $app['Core']->getAuthenticatedUser()
                        );

                        $html = $twig->render($template, $params);

                        return new Response($html, 200, array("content-type" => "text/html"));
                    } elseif ( ! $app_authorized && $action_accept !== null) {
                        $app_authorized = ! ! $action_accept;
                        $account->set_revoked( ! $app_authorized);
                    }

                    //if native app show template
                    if ($oauth2_adapter->isNativeApp($params['redirect_uri'])) {
                        $params = $oauth2_adapter->finishNativeClientAuthorization($app_authorized, $params);
                        $params['user'] = $app['Core']->getAuthenticatedUser();
                        $html = $twig->render("api/auth/native_app_access_token.twig", $params);

                        return new Response($html, 200, array("content-type" => "text/html"));
                    } else {
                        $oauth2_adapter->finishClientAuthorization($app_authorized, $params);
                    }
                };

            $route = '/authorize';
            $app->get($route, $authorize_func);
            $app->post($route, $authorize_func);

            /**
             *  TOKEN ENDPOINT
             *  Token endpoint - used to exchange an authorization grant for an access token.
             */
            $route = '/token';
            $app->post($route, function(\Silex\Application $app, Request $request) {
                    if ( ! $request->isSecure()) {
                        throw new HttpException(400, 'require the use of the https', null, array('content-type' => 'application/json'));
                    }

                    $app['oauth']->grantAccessToken();
                    ob_flush();
                    flush();

                    return;
                });

            /**
             * *******************************************************************
             *
             * Route Errors
             *
             */
            $app->error(function (\Exception $e) use ($app) {
                    if ($e instanceof NotFoundHttpException || $e instanceof \Exception_NotFound) {
                        return new Response('The requested page could not be found.', 404);
                    }

                    $code = 500;
                    $msg = 'We are sorry, but something went wrong';
                    $headers = array();

                    if ($e instanceof HttpExceptionInterface) {
                        $headers = $e->getHeaders();
                        $msg = $e->getMessage();
                        $code = $e->getStatusCode();

                        if (isset($headers['content-type']) && $headers['content-type'] == 'application/json') {
                            $obj = new \stdClass();
                            $obj->msg = $msg;
                            $obj->code = $code;
                            $msg = json_encode($obj);
                        }
                    }

                    return new Response($msg, $code, $headers);
                });

            return $app;
        });
