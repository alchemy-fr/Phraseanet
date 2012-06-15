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

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function() {


            $app = new \Silex\Application();

            /**
             * @var Alchemy\Phrasea\Core
             */
            $app["Core"] = \bootstrap::getCore();

            /**
             * @var appbox
             */
            $app["appbox"] = \appbox::get_instance($app['Core']);

            /**
             * @var API_OAuth2_Token
             */
            $app['token'] = null;

            /**
             * Api Service
             * @var Closure
             */
            $app['api'] = function () use ($app) {
                    return new \API_V1_adapter(false, $app["appbox"], $app["Core"]);
                };

            /**
             * oAuth token verification process
             * - Check if oauth_token exists && is valid
             * - Check if request comes from phraseanet Navigator && phraseanet Navigator
             *  is enbale on current instance
             * - restore user session
             *
             * @ throws \API_V1_exception_unauthorized
             * @ throws \API_V1_exception_forbidden
             */
            $app->before(function($request) use ($app) {
                    $session = $app["appbox"]->get_session();
                    $registry = $app['Core']->getRegistry();
                    $oauth2_adapter = new \API_OAuth2_Adapter($app["appbox"]);
                    $oauth2_adapter->verifyAccessToken();

                    $user = \User_Adapter::getInstance($oauth2_adapter->get_usr_id(), $app["appbox"]);
                    $app['token'] = \API_OAuth2_Token::load_by_oauth_token($app["appbox"], $oauth2_adapter->getToken());

                    $oAuth2App = $app['token']->get_account()->get_application();
                    /* @var $oAuth2App \API_OAuth2_Application */

                    if ($oAuth2App->get_client_id() == \API_OAuth2_Application_Navigator::CLIENT_ID
                        && ! $registry->get('GV_client_navigator')) {
                        throw new \API_V1_exception_forbidden(_('The use of phraseanet Navigator is not allowed'));
                    }

                    if ($session->is_authenticated()) {
                        return;
                    }

                    if ($oauth2_adapter->has_ses_id()) {
                        try {
                            $session->restore($user, $oauth2_adapter->get_ses_id());

                            return;
                        } catch (\Exception $e) {
                            
                        }
                    }
                    $auth = new \Session_Authentication_None($user);
                    $session->authenticate($auth);
                    $oauth2_adapter->remember_this_ses_id($session->get_ses_id());

                    return;
                });


            /**
             * oAUth log process
             *
             * Parse the requested route to fetch
             * - the ressource (databox, basket, record etc ..)
             * - general action (list, add, search)
             * - the action (setstatus, setname etc..)
             * - the aspect (collections, related, content etc..)
             *
             * @return array
             */
            $parseRoute = function ($route, Response $response) {
                    $ressource = $general = $aspect = $action = null;
                    $exploded_route = explode('/', \p4string::delFirstSlash(\p4string::delEndSlash($route)));
                    if (sizeof($exploded_route) > 0 && $response->isOk()) {
                        $ressource = $exploded_route[0];

                        if (sizeof($exploded_route) == 2 && (int) $exploded_route[1] == 0) {
                            $general = $exploded_route[1];
                        } else {
                            switch ($ressource) {
                                case \API_V1_Log::DATABOXES_RESSOURCE :
                                    if ((int) $exploded_route[1] > 0 && sizeof($exploded_route) == 3)
                                        $aspect = $exploded_route[2];
                                    break;
                                case \API_V1_Log::RECORDS_RESSOURCE :
                                    if ((int) $exploded_route[1] > 0 && sizeof($exploded_route) == 4) {
                                        if ( ! isset($exploded_route[3]))
                                            $aspect = "record";
                                        elseif (preg_match("/^set/", $exploded_route[3]))
                                            $action = $exploded_route[3];
                                        else
                                            $aspect = $exploded_route[3];
                                    }
                                    break;
                                case \API_V1_Log::BASKETS_RESSOURCE :
                                    if ((int) $exploded_route[1] > 0 && sizeof($exploded_route) == 3) {
                                        if (preg_match("/^set/", $exploded_route[2]) || preg_match("/^delete/", $exploded_route[2]))
                                            $action = $exploded_route[2];
                                        else
                                            $aspect = $exploded_route[2];
                                    }
                                    break;
                                case \API_V1_Log::FEEDS_RESSOURCE :
                                    if ((int) $exploded_route[1] > 0 && sizeof($exploded_route) == 3)
                                        $aspect = $exploded_route[2];
                                    break;
                            }
                        }
                    }

                    return array('ressource' => $ressource, 'general'   => $general, 'aspect'    => $aspect, 'action'    => $action);
                };

            /**
             * Log occurs in after filter
             */
            $app->after(function (Request $request, Response $response) use ($app, $parseRoute) {
                    $account = $app['token']->get_account();
                    $pathInfo = $request->getPathInfo();
                    $route = $parseRoute($pathInfo, $response);
                    \API_V1_Log::create(
                        $app["appbox"]
                        , $account
                        , $request->getMethod() . " " . $pathInfo
                        , $response->getStatusCode()
                        , $response->headers->get('content-type')
                        , $route['ressource']
                        , $route['general']
                        , $route['aspect']
                        , $route['action']
                    );
                });

            /**
             * Method Not Allowed Closure
             */
            $bad_request_exception = function() {
                    throw new \API_V1_exception_badrequest();
                };

            /**
             * Check wether the current user is Admin
             */
            $mustBeAdmin = function (Request $request) use ($app) {
                    /* @var $user \User_Adapter */
                    $user = $app['token']->get_account()->get_user();
                    if ( ! $user->is_admin()) {
                        throw new \API_V1_exception_unauthorized('You are not authorized');
                    }
                };

            /**
             * Get all tasks information
             *
             * Route : /monitor/phraseanet/
             *
             * Method : GET
             *
             * Parameters :
             *
             */
            $route = '/monitor/tasks/';
            $app->get(
                $route, function(\Silex\Application $app, Request $request) {
                    return $app['api']->get_task_list($app)->get_response();
                }
            )->before($mustBeAdmin);

            /**
             * *******************************************************************
             * Get task informations
             *
             * Route : /monitor/phraseanet/
             *
             * Method : GET
             *
             * Parameters :
             *
             */
            $route = '/monitor/task/{task_id}/';
            $app->get(
                $route, function(\Silex\Application $app, Request $request, $task_id) {
                    return $app['api']->get_task($app, $task_id)->get_response();
                }
            )->before($mustBeAdmin)->assert('task_id', '\d+');

            /**
             * *******************************************************************
             * Start task
             *
             * Route : /monitor/task/{task_id}/
             *
             * Method : POST
             *
             * Parameters :
             * - name (string) change the name of the task
             * - autostart (boolean) start task when scheduler starts
             */
            $route = '/monitor/task/{task_id}/';
            $app->post(
                $route, function(\Silex\Application $app, Request $request, $task_id) {
                    return $app['api']->set_task_property($app, $task_id)->get_response();
                }
            )->before($mustBeAdmin)->assert('task_id', '\d+');

            /**
             * *******************************************************************
             * Start task
             *
             * Route : /monitor/task/{task_id}/start/
             *
             * Method : POST
             *
             * Parameters :
             *
             */
            $route = '/monitor/task/{task_id}/start/';
            $app->post(
                $route, function(\Silex\Application $app, Request $request, $task_id) {
                    return $app['api']->start_task($app, $task_id)->get_response();
                }
            )->before($mustBeAdmin);

            /**
             * *******************************************************************
             * Stop task
             *
             * Route : /monitor/task/{task_id}/stop/
             *
             * Method : POST
             *
             * Parameters :
             *
             */
            $route = '/monitor/task/{task_id}/stop/';
            $app->post(
                $route, function(\Silex\Application $app, Request $request, $task_id) {
                    return $app['api']->stop_task($app, $task_id)->get_response();
                }
            )->before($mustBeAdmin);

            /**
             * *******************************************************************
             * Get some information about phraseanet
             *
             * Route : /monitor/phraseanet/
             *
             * Method : GET
             *
             * Parameters :
             *
             */
            $route = '/monitor/phraseanet/';
            $app->get(
                $route, function(\Silex\Application $app, Request $request) {
                    return $app['api']->get_phraseanet_monitor($app)->get_response();
                }
            )->before($mustBeAdmin);


            /**
             * *******************************************************************
             * Route : /databoxes/list/
             *
             * Method : GET
             *
             * Parameters :
             *
             */
            $route = '/databoxes/list/';
            $app->get(
                $route, function(\Silex\Application $app, Request $request) {
                    return $app['api']->get_databoxes($request)->get_response();
                }
            );

            /**
             * *******************************************************************
             *
             * Route /databoxes/DATABOX_ID/collections/
             *
             * Method : GET
             *
             * Parameters ;
             *    DATABOX_ID : required INT
             */
            $route = '/databoxes/{databox_id}/collections/';
            $app->get(
                $route, function($databox_id) use ($app) {
                    $result = $app['api']->get_databox_collections($app['request'], $databox_id);

                    return $result->get_response();
                }
            )->assert('databox_id', '\d+');

            $app->get('/databoxes/{any_id}/collections/', $bad_request_exception);


            /**
             * *******************************************************************
             * Route /databoxes/DATABOX_ID/status/
             *
             * Method : GET
             *
             * Parameters ;
             *    DATABOX_ID : required INT
             *
             */
            $route = '/databoxes/{databox_id}/status/';
            $app->get(
                $route, function($databox_id) use ($app) {
                    $result = $app['api']->get_databox_status($app['request'], $databox_id);

                    return $result->get_response();
                }
            )->assert('databox_id', '\d+');

            $app->get('/databoxes/{any_id}/status/', $bad_request_exception);

            /**
             * Route /databoxes/DATABOX_ID/metadatas/
             *
             * Method : GET
             *
             * Parameters ;
             *    DATABOX_ID : required INT
             */
            $route = '/databoxes/{databox_id}/metadatas/';
            $app->get(
                $route, function($databox_id) use ($app) {
                    $result = $app['api']->get_databox_metadatas($app['request'], $databox_id);

                    return $result->get_response();
                }
            )->assert('databox_id', '\d+');

            $app->get('/databoxes/{any_id}/metadatas/', $bad_request_exception);

            /**
             * Route /databoxes/DATABOX_ID/termsOfUse/
             *
             * Method : GET
             *
             * Parameters ;
             *    DATABOX_ID : required INT
             */
            $route = '/databoxes/{databox_id}/termsOfUse/';
            $app->get(
                $route, function($databox_id) use ($app) {
                    $result = $app['api']->get_databox_terms($app['request'], $databox_id);

                    return $result->get_response();
                }
            )->assert('databox_id', '\d+');

            $app->get('/databoxes/{any_id}/termsOfUse/', $bad_request_exception);


            $route = '/quarantine/list/';
            $app->get(
                $route, function(\Silex\Application $app, Request $request) {
                    return $app['api']->list_quarantine($app, $request)->get_response();
                }
            );

            $route = '/quarantine/item/{lazaret_id}/';
            $app->get(
                $route, function($lazaret_id, \Silex\Application $app, Request $request) {
                    return $app['api']->list_quarantine_item($lazaret_id, $app, $request)->get_response();
                }
            );


            /**
             * *******************************************************************
             * Route : /records/add/
             *
             * Method : POST
             *
             * Parameters :
             *
             */
            $route = '/records/add/';
            $app->post(
                $route, function(\Silex\Application $app, Request $request) {
                    return $app['api']->add_record($app, $request)->get_response();
                }
            );


            /**
             * Route : /records/search/
             *
             * Method : GET or POST
             *
             * Parameters :
             *    bases[] : array
             *    status[] : array
             *    fields[] : array
             *    record_type : boolean
             *    media_type : string
             *
             * Response :
             *    Array of record objects
             *
             */
            $route = '/records/search/';
            $app->match(
                $route, function() use ($app) {
                    $result = $app['api']->search_records($app['request']);

                    return $result->get_response();
                }
            );


            $route = '/records/{databox_id}/{record_id}/caption/';
            $app->get(
                $route, function($databox_id, $record_id) use ($app) {
                    $result = $app['api']->caption_records($app['request'], $databox_id, $record_id);

                    return $result->get_response();
                }
            )->assert('databox_id', '\d+')->assert('record_id', '\d+');

            $app->get('/records/{any_id}/{anyother_id}/caption/', $bad_request_exception);


            /**
             * Route : /records/DATABOX_ID/RECORD_ID/metadatas/
             *
             * Method : GET
             *
             * Parameters :
             *    DATABOX_ID : required INT
             *    RECORD_ID : required INT
             *
             */
            $route = '/records/{databox_id}/{record_id}/metadatas/';
            $app->get(
                $route, function($databox_id, $record_id) use ($app) {
                    $result = $app['api']->get_record_metadatas($app['request'], $databox_id, $record_id);

                    return $result->get_response();
                }
            )->assert('databox_id', '\d+')->assert('record_id', '\d+');

            $app->get('/records/{any_id}/{anyother_id}/metadatas/', $bad_request_exception);

            /**
             * Route : /records/DATABOX_ID/RECORD_ID/status/
             *
             * Method : GET
             *
             * Parameters :
             *    DATABOX_ID : required INT
             *    RECORD_ID : required INT
             *
             */
            $route = '/records/{databox_id}/{record_id}/status/';
            $app->get(
                $route, function($databox_id, $record_id) use ($app) {
                    $result = $app['api']->get_record_status($app['request'], $databox_id, $record_id);

                    return $result->get_response();
                }
            )->assert('databox_id', '\d+')->assert('record_id', '\d+');

            $app->get('/records/{any_id}/{anyother_id}/status/', $bad_request_exception);

            /**
             * Route : /records/DATABOX_ID/RECORD_ID/related/
             *
             * Method : GET
             *
             * Parameters :
             *    DATABOX_ID : required INT
             *    RECORD_ID : required INT
             *
             */
            $route = '/records/{databox_id}/{record_id}/related/';
            $app->get(
                $route, function($databox_id, $record_id) use ($app) {
                    $result = $app['api']->get_record_related($app['request'], $databox_id, $record_id);

                    return $result->get_response();
                }
            )->assert('databox_id', '\d+')->assert('record_id', '\d+');

            $app->get('/records/{any_id}/{anyother_id}/related/', $bad_request_exception);

            /**
             * Route : /records/DATABOX_ID/RECORD_ID/embed/
             *
             * Method : GET
             *
             * Parameters :
             *    DATABOX_ID : required INT
             *    RECORD_ID : required INT
             *
             */
            $route = '/records/{databox_id}/{record_id}/embed/';
            $app->get(
                $route, function($databox_id, $record_id) use ($app) {
                    $result = $app['api']->get_record_embed($app['request'], $databox_id, $record_id);

                    return $result->get_response();
                }
            )->assert('databox_id', '\d+')->assert('record_id', '\d+');

            $app->get('/records/{any_id}/{anyother_id}/embed/', $bad_request_exception);

            /**
             * Route : /records/DATABOX_ID/RECORD_ID/setmetadatas/
             *
             * Method : POST
             *
             * Parameters :
             *    DATABOX_ID : required INT
             *    RECORD_ID : required INT
             *
             */
            $route = '/records/{databox_id}/{record_id}/setmetadatas/';
            $app->post(
                $route, function($databox_id, $record_id) use ($app) {
                    $result = $app['api']->set_record_metadatas($app['request'], $databox_id, $record_id);

                    return $result->get_response();
                }
            )->assert('databox_id', '\d+')->assert('record_id', '\d+');

            $app->post('/records/{any_id}/{anyother_id}/setmetadatas/', $bad_request_exception);

            /**
             * Route : /records/DATABOX_ID/RECORD_ID/setstatus/
             *
             * Method : POST
             *
             * Parameters :
             *    DATABOX_ID : required INT
             *    RECORD_ID : required INT
             *
             */
            $route = '/records/{databox_id}/{record_id}/setstatus/';
            $app->post(
                $route, function($databox_id, $record_id) use ($app) {
                    $result = $app['api']->set_record_status($app['request'], $databox_id, $record_id);

                    return $result->get_response();
                }
            )->assert('databox_id', '\d+')->assert('record_id', '\d+');

            $app->post('/records/{any_id}/{anyother_id}/setstatus/', $bad_request_exception);


            /**
             * Route : /records/DATABOX_ID/RECORD_ID/setcollection/
             *
             * Method : POST
             *
             * Parameters :
             *    DATABOX_ID : required INT
             *    RECORD_ID : required INT
             *
             */
            $route = '/records/{databox_id}/{record_id}/setcollection/';
            $app->post(
                $route, function($databox_id, $record_id) use ($app) {
                    $result = $app['api']->set_record_collection($app['request'], $databox_id, $record_id);

                    return $result->get_response();
                }
            )->assert('databox_id', '\d+')->assert('record_id', '\d+');
            $app->post('/records/{wrong_databox_id}/{wrong_record_id}/setcollection/', $bad_request_exception);


            $route = '/records/{databox_id}/{record_id}/';
            $app->get($route, function($databox_id, $record_id) use ($app) {
                    $result = $app['api']->get_record($app['request'], $databox_id, $record_id);

                    return $result->get_response();
                })->assert('databox_id', '\d+')->assert('record_id', '\d+');
            $app->get('/records/{any_id}/{anyother_id}/', $bad_request_exception);

            /**
             * Route : /baskets/list/
             *
             * Method : POST
             *
             * Parameters :
             *
             */
            $route = '/baskets/list/';
            $app->get(
                $route, function() use ($app) {
                    $result = $app['api']->search_baskets($app['request']);

                    return $result->get_response();
                }
            );


            /**
             * Route : /baskets/add/
             *
             * Method : POST
             *
             * Parameters :
             *
             */
            $route = '/baskets/add/';
            $app->post(
                $route, function() use ($app) {
                    $result = $app['api']->create_basket($app['request']);

                    return $result->get_response();
                }
            );



            /**
             * Route : /baskets/BASKET_ID/content/
             *
             * Method : GET
             *
             * Parameters :
             *    BASKET_ID : required INT
             *
             */
            $route = '/baskets/{basket_id}/content/';
            $app->get(
                $route, function($basket_id) use ($app) {
                    $result = $app['api']->get_basket($app['request'], $basket_id);

                    return $result->get_response();
                }
            )->assert('basket_id', '\d+');
            $app->get('/baskets/{wrong_basket_id}/content/', $bad_request_exception);


            /**
             * Route : /baskets/BASKET_ID/settitle/
             *
             * Method : GET
             *
             * Parameters :
             *    BASKET_ID : required INT
             *
             */
            $route = '/baskets/{basket_id}/setname/';
            $app->post(
                $route, function($basket_id) use ($app) {
                    $result = $app['api']->set_basket_title($app['request'], $basket_id);

                    return $result->get_response();
                }
            )->assert('basket_id', '\d+');
            $app->post('/baskets/{wrong_basket_id}/setname/', $bad_request_exception);


            /**
             * Route : /baskets/BASKET_ID/setdescription/
             *
             * Method : POST
             *
             * Parameters :
             *    BASKET_ID : required INT
             *
             */
            $route = '/baskets/{basket_id}/setdescription/';
            $app->post(
                $route, function($basket_id) use ($app) {
                    $result = $app['api']->set_basket_description($app['request'], $basket_id);

                    return $result->get_response();
                }
            )->assert('basket_id', '\d+');
            $app->post('/baskets/{wrong_basket_id}/setdescription/', $bad_request_exception);

            /**
             * Route : /baskets/BASKET_ID/delete/
             *
             * Method : POST
             *
             * Parameters :
             *    BASKET_ID : required INT
             *
             */
            $route = '/baskets/{basket_id}/delete/';
            $app->post(
                $route, function($basket_id) use ($app) {
                    $result = $app['api']->delete_basket($app['request'], $basket_id);

                    return $result->get_response();
                }
            )->assert('basket_id', '\d+');
            $app->post('/baskets/{wrong_basket_id}/delete/', $bad_request_exception);


            /**
             * Route : /feeds/list/
             *
             * Method : POST
             *
             * Parameters :
             *
             */
            $route = '/feeds/list/';
            $app->get(
                $route, function() use ($app) {
                    $result = $app['api']->search_publications($app['request'], $app['Core']->getAuthenticatedUser());

                    return $result->get_response();
                }
            );


            $route = '/feeds/content/';
            $app->get(
                $route, function() use ($app) {
                    $result = $app['api']->get_publications($app['request'], $app['Core']->getAuthenticatedUser());

                    return $result->get_response();
                }
            );

            $route = '/feeds/entry/{entry_id}/';
            $app->get(
                $route, function($entry_id) use ($app) {
                    $result = $app['api']->get_feed_entry($app['request'], $entry_id, $app['Core']->getAuthenticatedUser());

                    return $result->get_response();
                }
            )->assert('entry_id', '\d+');
            $app->get('/feeds/entry/{entry_id}/', $bad_request_exception);

            /**
             * Route : /feeds/PUBLICATION_ID/content/
             *
             * Method : GET
             *
             * Parameters :
             *    PUBLICATION_ID : required INT
             *
             */
            $route = '/feeds/{feed_id}/content/';
            $app->get(
                $route, function($feed_id) use ($app) {
                    $result = $app['api']->get_publication($app['request'], $feed_id, $app['Core']->getAuthenticatedUser());

                    return $result->get_response();
                }
            )->assert('feed_id', '\d+');
            $app->get('/feeds/{wrong_feed_id}/content/', $bad_request_exception);

            /**
             * *******************************************************************
             *
             * Route Errors
             *
             */
            $app->error(function (\Exception $e) use ($app) {

                    $headers = array();
                    
                    if ($e instanceof \API_V1_exception_methodnotallowed) {
                        $code = \API_V1_result::ERROR_METHODNOTALLOWED;
                    } elseif ($e instanceof Exception\MethodNotAllowedHttpException) {
                        $code = \API_V1_result::ERROR_METHODNOTALLOWED;
                    } elseif ($e instanceof \API_V1_exception_badrequest) {
                        $code = \API_V1_result::ERROR_BAD_REQUEST;
                    } elseif ($e instanceof \API_V1_exception_forbidden) {
                        $code = \API_V1_result::ERROR_FORBIDDEN;
                    } elseif ($e instanceof \API_V1_exception_unauthorized) {
                        $code = \API_V1_result::ERROR_UNAUTHORIZED;
                    } elseif ($e instanceof \API_V1_exception_internalservererror) {
                        $code = \API_V1_result::ERROR_INTERNALSERVERERROR;
                    } elseif ($e instanceof \Exception_NotFound) {
                        $code = \API_V1_result::ERROR_NOTFOUND;
                    } elseif ($e instanceof Exception\NotFoundHttpException) {
                        $code = \API_V1_result::ERROR_NOTFOUND;
                    } else {
                        $code = \API_V1_result::ERROR_INTERNALSERVERERROR;
                    }

                    if($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                        $headers = $e->getHeaders();
                    }
                    
                    $result = $app['api']->get_error_message($app['request'], $code, $e->getMessage());
                    $response = $result->get_response();

                    foreach ($headers as $key => $value) {
                        $response->headers->set($key, $value);
                    }
                    
                    return $response;
                });
            return $app;
        });
