<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

require_once __DIR__ . "/../../../../lib/bootstrap.php";
require_once __DIR__ . "/../../../../lib/classes/API/OAuth2/Autoloader.class.php";
\bootstrap::register_autoloads();
\API_OAuth2_Autoloader::register();

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
return call_user_func(function()
                {
                  $app = new \Silex\Application();

                  $app['Core'] = \bootstrap::getCore();

                  $app->register(new \Silex\Provider\ValidatorServiceProvider(), array(
                      'validator.class_path' => __DIR__ . '/../../../../lib/vendor/symfony/src',
                  ));


                  $app['appbox'] = function()
                          {
                            return \appbox::get_instance();
                          };


                  $app['oauth'] = function($app)
                          {
                            return new \API_OAuth2_Adapter($app['appbox']);
                          };


                  $app['user'] = function($app)
                          {
                            if ($app['appbox']->get_session()->is_authenticated())
                            {
                              $user = \user_adapter::getInstance(
                                              $app['appbox']->get_session()->get_usr_id()
                                              , $app['appbox']
                              );

                              return $user;
                            }
                            else
                            {
                              return null;
                            }
                          };


                  /**
                   * Protected Closure
                   * @var Closure
                   * @return Symfony\Component\HttpFoundation\Response
                   */
                  $app['response'] = $app->protect(function ($template, $variable) use ($app)
                          {
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


                  /*                   * *******************************************************************
                   *                        AUTHENTIFICATION API
                   */


                  /**
                   *  AUTHORIZE ENDPOINT
                   *
                   * Authorization endpoint - used to obtain authorization from the
                   * resource owner via user-agent redirection.
                   */
                  $authorize_func = function() use ($app)
                          {
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
                            $custom_template = $app['appbox']->get_registry()->get('GV_RootPath') . 'config/templates/web/api/auth/end_user_authorization/' . $client->get_id() . '.twig';
                            if (file_exists($custom_template))
                            {
                              $template = 'api/auth/end_user_authorization/' . $client->get_id() . '.twig';
                            }

                            if (!$authenticated)
                            {
                              if ($action_login !== null)
                              {
                                try
                                {
                                  $login = $request->get("login");
                                  $password = $request->get("password");
                                  $auth = new \Session_Authentication_Native($app['appbox'], $login, $password);
                                  $session->authenticate($auth);
                                }
                                catch (Exception $e)
                                {
                                  $params = array(
                                      "auth" => $oauth2_adapter
                                      , "session" => $session
                                      , "errorMessage" => true
                                      , "user" => $app['user']
                                  );
                                  $html = $twig->render($template, $params);

                                  return new Response($html, 200, array("content-type" => "text/html"));
                                }
                              }
                              else
                              {
                                $params = array(
                                    "auth" => $oauth2_adapter
                                    , "session" => $session
                                    , "errorMessage" => $errorMessage
                                    , "user" => $app['user']
                                );
                                $html = $twig->render($template, $params);

                                return new Response($html, 200, array("content-type" => "text/html"));
                              }
                            }

                            //check if current client is alreadu authorized by current user
                            $user_auth_clients = \API_OAuth2_Application::load_authorized_app_by_user($app['appbox'], $app['user']);

                            foreach ($user_auth_clients as $auth_client)
                            {
                              if ($client->get_client_id() == $auth_client->get_client_id())
                                $app_authorized = true;
                            }

                            $account = $oauth2_adapter->updateAccount($session->get_usr_id());
                            $params['account_id'] = $account->get_id();

                            if (!$app_authorized && $action_accept === null)
                            {
                              $params = array(
                                  "auth" => $oauth2_adapter
                                  , "session" => $session
                                  , "errorMessage" => $errorMessage
                                  , "user" => $app['user']
                              );

                              $html = $twig->render($template, $params);

                              return new Response($html, 200, array("content-type" => "text/html"));
                            }
                            elseif (!$app_authorized && $action_accept !== null)
                            {
                              $app_authorized = !!$action_accept;
                              $account->set_revoked(!$app_authorized);
                            }

                            //if native app show template
                            if ($oauth2_adapter->isNativeApp($params['redirect_uri']))
                            {
                              $params = $oauth2_adapter->finishNativeClientAuthorization($app_authorized, $params);
                              $html = $twig->render("api/auth/native_app_access_token.twig", $params);

                              return new Response($html, 200, array("content-type" => "text/html"));
                            }
                            else
                            {
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
                  $app->post($route, function() use ($app)
                          {
                            $app['oauth']->grantAccessToken();
                            ob_flush();
                            flush();

                            return;
                          });


                  /**
                   * MANAGEMENT APPS
                   *
                   *
                   */
                  /**
                   * list of all authorized apps by logged user
                   */
                  $route = '/applications';
                  $app->get($route, function() use ($app)
                          {
                            $apps = \API_OAuth2_Application::load_app_by_user($app['appbox'], $app['user']);

                            return $app['response']('api/auth/applications.twig', array("apps" => $apps, 'user' => $app['user']));
                          });

                  /**
                   * list of apps created by user
                   */
                  $route = "/applications/dev";
                  $app->get($route, function() use ($app)
                          {
                            $rs = \API_OAuth2_Application::load_dev_app_by_user($app['appbox'], $app['user']);

                            return $app['response']('api/auth/application_dev.twig', array("apps" => $rs));
                          });

                  /**
                   * display a new app form
                   */
                  $route = "/applications/dev/new";
                  $app->get($route, function() use ($app)
                          {
                            $var = array("violations" => null, 'form' => null);

                            return $app['response']('api/auth/application_dev_new.twig', $var);
                          });



                  $route = "/applications/dev/create";
                  $app->post($route, function() use ($app)
                          {
                            $submit = false;
                            $post = new \API_OAuth2_Form_DevApp($app['request']);
                            $violations = $app['validator']->validate($post);

                            if ($violations->count() == 0)
                              $submit = true;

                            $request = $app['request'];

                            if ($submit)
                            {
                              $application = \API_OAuth2_Application::create($app['appbox'], $app['user'], $request->get('name'));
                              $application->set_description($request->get('description'))
                                      ->set_redirect_uri($request->get('callback'))
                                      ->set_type($request->get('type'))
                                      ->set_website($request->get('website'));

                              return $app->redirect("/api/oauthv2/applications/dev/" . $application->get_id() . "/show");
                            }

                            $var = array(
                                "violations" => $violations,
                                "form" => $post
                            );

                            return $app['response']('api/auth/application_dev_new.twig', $var);
                          });


                  /**
                   * show details of app identified by its id
                   */
                  $route = "/applications/dev/{id}/show";
                  $app->get($route, function($id) use ($app)
                          {
                            $client = new \API_OAuth2_Application($app['appbox'], $id);
                            $token = $client->get_user_account($app['user'])->get_token()->get_value();
                            $var = array("app" => $client, "user" => $app['user'], "token" => $token);

                            return $app['response']('api/auth/application_dev_show.twig', $var);
                          })->assert('id', '\d+');

                  /**
                   * revoke access from a user to the app
                   * identified by  account id
                   */
                  $route = "/applications/revoke_access/";
                  $app->post($route, function() use ($app)
                          {
                            $result = array("ok" => false);
                            try
                            {
                              $account = new \API_OAuth2_Account($app['appbox'], $app['request']->get('account_id'));
                              $account->set_revoked((bool) $app['request']->get('revoke'));
                              $result['ok'] = true;
                            }
                            catch (Exception $e)
                            {
                              
                            }

                            $Serializer = $app['Core']['Serializer'];

                            return new Response(
                                            $Serializer->serialize($result, 'json')
                                            , 200
                                            , array("content-type" => "application/json")
                            );
                          });

                  $route = "/applications/{id}/generate_access_token/";
                  $app->post($route, function($id) use ($app)
                          {
                            $result = array("ok" => false);
                            try
                            {
                              $client = new \API_OAuth2_Application($app['appbox'], $id);
                              $account = $client->get_user_account($app['user']);

                              $token = $account->get_token();

                              if ($token instanceof API_OAuth2_Token)
                                $token->renew();
                              else
                                $token = \API_OAuth2_Token::create($app['appbox'], $account);

                              $result = array(
                                  "ok" => true
                                  , 'token' => $token->get_value()
                              );
                            }
                            catch (Exception $e)
                            {
                              
                            }

                            $Serializer = $app['Core']['Serializer'];

                            return new Response(
                                            $Serializer->serialize($result, 'json')
                                            , 200
                                            , array("content-type" => "application/json")
                            );
                          })->assert('id', '\d+');

                  $route = "/applications/oauth_callback";
                  $app->post($route, function() use ($app)
                          {
                            $app_id = $app['request']->request->get("app_id");
                            $app_callback = $app["request"]->request->get("callback");
                            $result = array("success" => false);
                            try
                            {
                              $client = new \API_OAuth2_Application($app['appbox'], $app_id);
                              $client->set_redirect_uri($app_callback);
                              $result['success'] = true;
                            }
                            catch (Exception $e)
                            {
                              
                            }

                            $Serializer = $app['Core']['Serializer'];

                            return new Response(
                                            $Serializer->serialize($result, 'json')
                                            , 200
                                            , array("content-type" => "application/json")
                            );
                          });

                  $route = "/applications/{id}";
                  $app->delete($route, function($id) use ($app)
                          {
                            $result = array("success" => false);
                            try
                            {
                              $client = new \API_OAuth2_Application($app['appbox'], $id);
                              $client->delete();
                              $result['success'] = true;
                            }
                            catch (\Exception $e)
                            {
                              
                            }

                            $Serializer = $app['Core']['Serializer'];

                            return new Response(
                                            $Serializer->serialize($result, 'json')
                                            , 200
                                            , array("content-type" => "application/json")
                            );
                          })->assert('id', '\d+');
                  /**
                   * *******************************************************************
                   *
                   * Route Errors
                   *
                   */
                  $app->error(function (\Exception $e) use ($app)
                          {
                            if ($e instanceof NotFoundHttpException || $e instanceof \Exception_NotFound)
                            {
                              return new Response('The requested page could not be found.', 404);
                            }

                            $code = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

                            return new Response('We are sorry, but something went terribly wrong.<br />' . $e->getMessage(), $code);
                          });


                  return $app;
                });
