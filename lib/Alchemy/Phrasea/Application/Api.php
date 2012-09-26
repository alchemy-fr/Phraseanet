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
use Silex\Application as SilexApplication;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function($environment = 'prod') {

            $app = new PhraseaApplication($environment);

            /**
             * @var API_OAuth2_Token
             */
            $app['token'] = null;

            /**
             * Api Service
             * @var Closure
             */
            $app['api'] = function () use ($app) {
                    return new \API_V1_adapter($app);
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
                    $registry = $app['phraseanet.registry'];
                    $oauth2_adapter = new \API_OAuth2_Adapter($app);
                    $oauth2_adapter->verifyAccessToken();

                    $app['token'] = \API_OAuth2_Token::load_by_oauth_token($app, $oauth2_adapter->getToken());

                    $oAuth2App = $app['token']->get_account()->get_application();
                    /* @var $oAuth2App \API_OAuth2_Application */

                    if ($oAuth2App->get_client_id() == \API_OAuth2_Application_Navigator::CLIENT_ID
                        && !$registry->get('GV_client_navigator')) {
                        throw new \API_V1_exception_forbidden(_('The use of phraseanet Navigator is not allowed'));
                    }

                    if ($app->isAuthenticated()) {
                        return;
                    }

                    $user = \User_Adapter::getInstance($oauth2_adapter->get_usr_id(), $app);
                    $auth = new \Session_Authentication_None($user);

                    $app->openAccount($auth, $oauth2_adapter->get_ses_id());

                    /**
                     * TODO Neutron => remove
                     */
                    $oauth2_adapter->remember_this_ses_id($app['session']->get('phrasea_session_id'));

                    return;
                });

            /**
             * OAuth log process
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
                                        if (!isset($exploded_route[3]))
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
                        $app
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
             * Check wether the current user is Admin or not
             */
            $mustBeAdmin = function (Request $request) use ($app) {
                    /* @var $user \User_Adapter */
                    $user = $app['token']->get_account()->get_user();
                    if (!$user->ACL()->is_admin()) {
                        throw new \API_V1_exception_unauthorized('You are not authorized');
                    }
                };

            /**
             * Get scheduler informations
             *
             * Route : /monitor/scheduler/
             *
             * Method : GET
             *
             * Parameters :
             *
             */
            $route = '/monitor/scheduler/';
            $app->get(
                $route, function(SilexApplication $app, Request $request) {
                    return $app['api']->get_scheduler($app)->get_response();
                }
            )->before($mustBeAdmin);

            /**
             * Get all tasks information
             *
             * Route : /monitor/tasks/
             *
             * Method : GET
             *
             * Parameters :
             *
             */
            $app->get('/monitor/tasks/', function(SilexApplication $app, Request $request) {
                    return $app['api']->get_task_list($app)->get_response();
                }
            )->before($mustBeAdmin);

            /**
             * Get task informations
             *
             * Route : /monitor/task/{task_id}/
             *
             * Method : GET
             *
             * Parameters :
             *
             */
            $app->get('/monitor/task/{task_id}/', function(SilexApplication $app, Request $request, $task_id) {
                    return $app['api']->get_task($app, $task_id)->get_response();
                }
            )->before($mustBeAdmin)->assert('task_id', '\d+');

            /**
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
            $app->post('/monitor/task/{task_id}/', function(SilexApplication $app, Request $request, $task_id) {
                    return $app['api']->set_task_property($app, $task_id)->get_response();
                }
            )->before($mustBeAdmin)->assert('task_id', '\d+');

            /**
             * Start task
             *
             * Route : /monitor/task/{task_id}/start/
             *
             * Method : POST
             *
             * Parameters :
             *
             */
            $app->post('/monitor/task/{task_id}/start/', function(SilexApplication $app, Request $request, $task_id) {
                    return $app['api']->start_task($app, $task_id)->get_response();
                }
            )->before($mustBeAdmin);

            /**
             * Stop task
             *
             * Route : /monitor/task/{task_id}/stop/
             *
             * Method : POST
             *
             * Parameters :
             *
             */
            $app->post('/monitor/task/{task_id}/stop/', function(SilexApplication $app, Request $request, $task_id) {
                    return $app['api']->stop_task($app, $task_id)->get_response();
                }
            )->before($mustBeAdmin);

            /**
             * Get some information about phraseanet
             *
             * Route : /monitor/phraseanet/
             *
             * Method : GET
             *
             * Parameters :
             *
             */
            $app->get('/monitor/phraseanet/', function(SilexApplication $app, Request $request) {
                    return $app['api']->get_phraseanet_monitor($app)->get_response();
                }
            )->before($mustBeAdmin);

            /**
             * Route : /databoxes/list/
             *
             * Method : GET
             *
             * Parameters :
             *
             */
            $app->get('/databoxes/list/', function(SilexApplication $app, Request $request) {
                    return $app['api']->get_databoxes($request)->get_response();
                }
            );

            /**
             * Route /databoxes/DATABOX_ID/collections/
             *
             * Method : GET
             *
             * Parameters ;
             *    DATABOX_ID : required INT
             */
            $app->get('/databoxes/{databox_id}/collections/', function(SilexApplication $app, $databox_id) {
                    return $app['api']
                            ->get_databox_collections($app['request'], $databox_id)
                            ->get_response();
                }
            )->assert('databox_id', '\d+');

            $app->get('/databoxes/{any_id}/collections/', $bad_request_exception);

            /**
             * Route /databoxes/DATABOX_ID/status/
             *
             * Method : GET
             *
             * Parameters ;
             *    DATABOX_ID : required INT
             *
             */
            $app->get('/databoxes/{databox_id}/status/', function(SilexApplication $app, $databox_id) {
                    return $app['api']
                            ->get_databox_status($app['request'], $databox_id)
                            ->get_response();
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
            $app->get('/databoxes/{databox_id}/metadatas/', function(SilexApplication $app, $databox_id) {
                    return $app['api']
                            ->get_databox_metadatas($app['request'], $databox_id)
                            ->get_response();
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
            $app->get('/databoxes/{databox_id}/termsOfUse/', function(SilexApplication $app, $databox_id) {
                    return $app['api']
                            ->get_databox_terms($app['request'], $databox_id)
                            ->get_response();
                }
            )->assert('databox_id', '\d+');

            $app->get('/databoxes/{any_id}/termsOfUse/', $bad_request_exception);

            $app->get('/quarantine/list/', function(SilexApplication $app, Request $request) {
                    return $app['api']->list_quarantine($app, $request)->get_response();
                }
            );

            $app->get('/quarantine/item/{lazaret_id}/', function($lazaret_id, SilexApplication $app, Request $request) {
                    return $app['api']->list_quarantine_item($lazaret_id, $app, $request)->get_response();
                }
            );

            /**
             * Route : /records/add/
             *
             * Method : POST
             *
             * Parameters :
             *
             */
            $app->post('/records/add/', function(SilexApplication $app, Request $request) {
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
            $app->match('/records/search/', function(SilexApplication $app) {
                    return $app['api']->search_records($app['request'])->get_response();
                }
            );

            $app->get('/records/{databox_id}/{record_id}/caption/', function(SilexApplication $app, $databox_id, $record_id) {
                    return $app['api']
                            ->caption_records($app['request'], $databox_id, $record_id)
                            ->get_response();
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
            $app->get('/records/{databox_id}/{record_id}/metadatas/', function(SilexApplication $app, $databox_id, $record_id) {
                    return $app['api']
                            ->get_record_metadatas($app['request'], $databox_id, $record_id)
                            ->get_response();
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
            $app->get('/records/{databox_id}/{record_id}/status/', function(SilexApplication $app, $databox_id, $record_id) {
                    return $app['api']
                            ->get_record_status($app['request'], $databox_id, $record_id)
                            ->get_response();
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
            $app->get('/records/{databox_id}/{record_id}/related/', function(SilexApplication $app, $databox_id, $record_id) {
                    return $app['api']
                            ->get_record_related($app['request'], $databox_id, $record_id)
                            ->get_response();
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
            $app->get('/records/{databox_id}/{record_id}/embed/', function(SilexApplication $app, $databox_id, $record_id) {
                    return $app['api']
                            ->get_record_embed($app['request'], $databox_id, $record_id)
                            ->get_response();
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
            $app->post('/records/{databox_id}/{record_id}/setmetadatas/', function(SilexApplication $app, $databox_id, $record_id) {
                    return $app['api']
                            ->set_record_metadatas($app['request'], $databox_id, $record_id)
                            ->get_response();
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
            $app->post('/records/{databox_id}/{record_id}/setstatus/', function(SilexApplication $app, $databox_id, $record_id) {
                    return $app['api']
                            ->set_record_status($app['request'], $databox_id, $record_id)
                            ->get_response();
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
            $app->post('/records/{databox_id}/{record_id}/setcollection/', function(SilexApplication $app, $databox_id, $record_id) {
                    return $app['api']
                            ->set_record_collection($app['request'], $databox_id, $record_id)
                            ->get_response();
                }
            )->assert('databox_id', '\d+')->assert('record_id', '\d+');

            $app->post('/records/{wrong_databox_id}/{wrong_record_id}/setcollection/', $bad_request_exception);

            $app->get('/records/{databox_id}/{record_id}/', function(SilexApplication $app, $databox_id, $record_id) {
                    return $app['api']
                            ->get_record($app['request'], $databox_id, $record_id)
                            ->get_response();
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
            $app->get('/baskets/list/', function(SilexApplication $app) {
                    return $app['api']->search_baskets($app['request'])->get_response();
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
            $app->post('/baskets/add/', function(SilexApplication $app) {
                    return $app['api']->create_basket($app['request'])->get_response();
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
            $app->get('/baskets/{basket_id}/content/', function(SilexApplication $app, $basket_id) {
                    return $app['api']->get_basket($app['request'], $basket_id)->get_response();
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
            $app->post('/baskets/{basket_id}/setname/', function(SilexApplication $app, $basket_id) {
                    return $app['api']
                            ->set_basket_title($app['request'], $basket_id)
                            ->get_response();
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
            $app->post('/baskets/{basket_id}/setdescription/', function(SilexApplication $app, $basket_id) {
                    return $app['api']
                            ->set_basket_description($app['request'], $basket_id)
                            ->get_response();
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
            $app->post('/baskets/{basket_id}/delete/', function(SilexApplication $app, $basket_id) {
                    return $app['api']->delete_basket($app['request'], $basket_id)->get_response();
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
            $app->get('/feeds/list/', function(SilexApplication $app) {
                    return $app['api']
                            ->search_publications($app['request'], $app['phraseanet.user'])
                            ->get_response();
                }
            );

            $app->get('/feeds/content/', function(SilexApplication $app) {
                    return $app['api']
                            ->get_publications($app['request'], $app['phraseanet.user'])
                            ->get_response();
                }
            );

            $app->get('/feeds/entry/{entry_id}/', function(SilexApplication $app, $entry_id) {
                    return $app['api']
                            ->get_feed_entry($app['request'], $entry_id, $app['phraseanet.user'])
                            ->get_response();
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
            $app->get('/feeds/{feed_id}/content/', function(SilexApplication $app, $feed_id) {
                    return $app['api']
                            ->get_publication($app['request'], $feed_id, $app['phraseanet.user'])
                            ->get_response();
                }
            )->assert('feed_id', '\d+');

            $app->get('/feeds/{wrong_feed_id}/content/', $bad_request_exception);

            /**
             * Route Errors
             */
            $app->error(function (\Exception $e) use ($app) {

                    $headers = array();

                    if ($e instanceof \API_V1_exception_methodnotallowed) {
                        $code = \API_V1_result::ERROR_METHODNOTALLOWED;
                    } elseif ($e instanceof MethodNotAllowedHttpException) {
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
                    } elseif ($e instanceof NotFoundHttpException) {
                        $code = \API_V1_result::ERROR_NOTFOUND;
                    } else {
                        $code = \API_V1_result::ERROR_INTERNALSERVERERROR;
                    }

                    if ($e instanceof HttpException) {
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
        }, $environment ? : null
);
