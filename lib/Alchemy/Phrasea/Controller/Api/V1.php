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

use Silex\ControllerProviderInterface;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Core\Event\PreAuthenticate;
use Alchemy\Phrasea\Core\Event\ApiOAuth2StartEvent;
use Alchemy\Phrasea\Core\Event\ApiOAuth2EndEvent;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class V1 implements ControllerProviderInterface
{
    public function connect(SilexApplication $app)
    {
        $controllers = $app['controllers_factory'];

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
        $controllers->before(function ($request) use ($app) {
            $context = new Context(Context::CONTEXT_OAUTH2_TOKEN);
            $app['dispatcher']->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request, $context));

            $app['dispatcher']->dispatch(PhraseaEvents::API_OAUTH2_START, new ApiOAuth2StartEvent());
            $oauth2_adapter = new \API_OAuth2_Adapter($app);
            $oauth2_adapter->verifyAccessToken();

            $app['token'] = \API_OAuth2_Token::load_by_oauth_token($app, $oauth2_adapter->getToken());

            $oAuth2App = $app['token']->get_account()->get_application();
            /* @var $oAuth2App \API_OAuth2_Application */

            if ($oAuth2App->get_client_id() == \API_OAuth2_Application_Navigator::CLIENT_ID
                && !$app['phraseanet.registry']->get('GV_client_navigator')) {
                throw new \API_V1_exception_forbidden(_('The use of phraseanet Navigator is not allowed'));
            }

            if ($oAuth2App->get_client_id() == \API_OAuth2_Application_OfficePlugin::CLIENT_ID
                && ! $app['phraseanet.registry']->get('GV_client_officeplugin')) {
                throw new \API_V1_exception_forbidden('The use of Office Plugin is not allowed.');
            }

            if ($app['authentication']->isAuthenticated()) {
                $app['dispatcher']->dispatch(PhraseaEvents::API_OAUTH2_END, new ApiOAuth2EndEvent());

                return;
            }

            $user = \User_Adapter::getInstance($oauth2_adapter->get_usr_id(), $app);

            $app['authentication']->openAccount($user);
            $oauth2_adapter->remember_this_ses_id($app['session']->get('session_id'));
            $app['dispatcher']->dispatch(PhraseaEvents::API_OAUTH2_END, new ApiOAuth2EndEvent());

            return;
        }, 256);

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
        $controllers->after(function (Request $request, Response $response) use ($app, $parseRoute) {
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

        $controllers->after(function () use ($app) {
            $app['authentication']->closeAccount();
        });

        /**
         * Method Not Allowed Closure
         */
        $bad_request_exception = function () {
            throw new \API_V1_exception_badrequest();
        };

        /**
         * Check wether the current user is Admin or not
         */
        $mustBeAdmin = function (Request $request) use ($app) {
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
        $controllers->get('/monitor/scheduler/', function (SilexApplication $app, Request $request) {
            return $app['api']->get_scheduler($app)->get_response();
        })->before($mustBeAdmin);

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
        $controllers->get('/monitor/tasks/', function (SilexApplication $app, Request $request) {
            return $app['api']->get_task_list($app)->get_response();
        })->before($mustBeAdmin);

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
        $controllers->get('/monitor/task/{task_id}/', function (SilexApplication $app, Request $request, $task_id) {
            return $app['api']->get_task($app, $task_id)->get_response();
        })->before($mustBeAdmin)->assert('task_id', '\d+');

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
        $controllers->post('/monitor/task/{task_id}/', function (SilexApplication $app, Request $request, $task_id) {
            return $app['api']->set_task_property($app, $task_id)->get_response();
        })->before($mustBeAdmin)->assert('task_id', '\d+');

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
        $controllers->post('/monitor/task/{task_id}/start/', function (SilexApplication $app, Request $request, $task_id) {
            return $app['api']->start_task($app, $task_id)->get_response();
        })->before($mustBeAdmin);

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
        $controllers->post('/monitor/task/{task_id}/stop/', function (SilexApplication $app, Request $request, $task_id) {
            return $app['api']->stop_task($app, $task_id)->get_response();
        })->before($mustBeAdmin);

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
        $controllers->get('/monitor/phraseanet/', function (SilexApplication $app, Request $request) {
            return $app['api']->get_phraseanet_monitor($app)->get_response();
        })->before($mustBeAdmin);

        /**
         * Route : /databoxes/list/
         *
         * Method : GET
         *
         * Parameters :
         *
         */
        $controllers->get('/databoxes/list/', function (SilexApplication $app, Request $request) {
            return $app['api']->get_databoxes($request)->get_response();
        });

        /**
         * Route /databoxes/DATABOX_ID/collections/
         *
         * Method : GET
         *
         * Parameters ;
         *    DATABOX_ID : required INT
         */
        $controllers->get('/databoxes/{databox_id}/collections/', function (SilexApplication $app, $databox_id) {
            return $app['api']
                    ->get_databox_collections($app['request'], $databox_id)
                    ->get_response();
        })->assert('databox_id', '\d+');

        $controllers->get('/databoxes/{any_id}/collections/', $bad_request_exception);

        /**
         * Route /databoxes/DATABOX_ID/status/
         *
         * Method : GET
         *
         * Parameters ;
         *    DATABOX_ID : required INT
         *
         */
        $controllers->get('/databoxes/{databox_id}/status/', function (SilexApplication $app, $databox_id) {
            return $app['api']
                    ->get_databox_status($app['request'], $databox_id)
                    ->get_response();
        })->assert('databox_id', '\d+');

        $controllers->get('/databoxes/{any_id}/status/', $bad_request_exception);

        /**
         * Route /databoxes/DATABOX_ID/metadatas/
         *
         * Method : GET
         *
         * Parameters ;
         *    DATABOX_ID : required INT
         */
        $controllers->get('/databoxes/{databox_id}/metadatas/', function (SilexApplication $app, $databox_id) {
            return $app['api']
                    ->get_databox_metadatas($app['request'], $databox_id)
                    ->get_response();
        })->assert('databox_id', '\d+');

        $controllers->get('/databoxes/{any_id}/metadatas/', $bad_request_exception);

        /**
         * Route /databoxes/DATABOX_ID/termsOfUse/
         *
         * Method : GET
         *
         * Parameters ;
         *    DATABOX_ID : required INT
         */
        $controllers->get('/databoxes/{databox_id}/termsOfUse/', function (SilexApplication $app, $databox_id) {
            return $app['api']
                    ->get_databox_terms($app['request'], $databox_id)
                    ->get_response();
        })->assert('databox_id', '\d+');

        $controllers->get('/databoxes/{any_id}/termsOfUse/', $bad_request_exception);

        $controllers->get('/quarantine/list/', function (SilexApplication $app, Request $request) {
            return $app['api']->list_quarantine($app, $request)->get_response();
        });

        $controllers->get('/quarantine/item/{lazaret_id}/', function ($lazaret_id, SilexApplication $app, Request $request) {
            return $app['api']->list_quarantine_item($lazaret_id, $app, $request)->get_response();
        });

        /**
         * Route : /records/add/
         *
         * Method : POST
         *
         * Parameters :
         *
         */
        $controllers->post('/records/add/', function (SilexApplication $app, Request $request) {
            return $app['api']->add_record($app, $request)->get_response();
        });

        /**
         * Route : /search/
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
         *    Array containing an array of records and stories collection
         *
         */
        $controllers->match('/search/', function () use ($app) {
            return $app['api']->search($app['request'])->get_response();
        });

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
        $controllers->match('/records/search/', function (SilexApplication $app) {
            return $app['api']->search_records($app['request'])->get_response();
        });

        $controllers->get('/records/{databox_id}/{record_id}/caption/', function (SilexApplication $app, $databox_id, $record_id) {
            return $app['api']
                    ->caption_records($app['request'], $databox_id, $record_id)
                    ->get_response();
        })->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/records/{any_id}/{anyother_id}/caption/', $bad_request_exception);

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
        $controllers->get('/records/{databox_id}/{record_id}/metadatas/', function (SilexApplication $app, $databox_id, $record_id) {
            return $app['api']
                    ->get_record_metadatas($app['request'], $databox_id, $record_id)
                    ->get_response();
        })->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/records/{any_id}/{anyother_id}/metadatas/', $bad_request_exception);

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
        $controllers->get('/records/{databox_id}/{record_id}/status/', function (SilexApplication $app, $databox_id, $record_id) {
            return $app['api']
                    ->get_record_status($app['request'], $databox_id, $record_id)
                    ->get_response();
        })->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/records/{any_id}/{anyother_id}/status/', $bad_request_exception);

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
        $controllers->get('/records/{databox_id}/{record_id}/related/', function (SilexApplication $app, $databox_id, $record_id) {
            return $app['api']
                    ->get_record_related($app['request'], $databox_id, $record_id)
                    ->get_response();
        })->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/records/{any_id}/{anyother_id}/related/', $bad_request_exception);

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
        $controllers->get('/records/{databox_id}/{record_id}/embed/', function (SilexApplication $app, $databox_id, $record_id) {
            return $app['api']
                    ->get_record_embed($app['request'], $databox_id, $record_id)
                    ->get_response();
        })->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/records/{any_id}/{anyother_id}/embed/', $bad_request_exception);

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
        $controllers->post('/records/{databox_id}/{record_id}/setmetadatas/', function (SilexApplication $app, $databox_id, $record_id) {
            return $app['api']
                    ->set_record_metadatas($app['request'], $databox_id, $record_id)
                    ->get_response();
        })->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->post('/records/{any_id}/{anyother_id}/setmetadatas/', $bad_request_exception);

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
        $controllers->post('/records/{databox_id}/{record_id}/setstatus/', function (SilexApplication $app, $databox_id, $record_id) {
            return $app['api']
                    ->set_record_status($app['request'], $databox_id, $record_id)
                    ->get_response();
        })->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->post('/records/{any_id}/{anyother_id}/setstatus/', $bad_request_exception);

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
        $controllers->post('/records/{databox_id}/{record_id}/setcollection/', function (SilexApplication $app, $databox_id, $record_id) {
            return $app['api']
                    ->set_record_collection($app['request'], $databox_id, $record_id)
                    ->get_response();
        })->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->post('/records/{wrong_databox_id}/{wrong_record_id}/setcollection/', $bad_request_exception);

        $controllers->get('/records/{databox_id}/{record_id}/', function (SilexApplication $app, $databox_id, $record_id) {
            return $app['api']
                    ->get_record($app['request'], $databox_id, $record_id)
                    ->get_response();
        })->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/records/{any_id}/{anyother_id}/', $bad_request_exception);

        /**
         * Route : /baskets/list/
         *
         * Method : POST
         *
         * Parameters :
         *
         */
        $controllers->get('/baskets/list/', function (SilexApplication $app) {
            return $app['api']->search_baskets($app['request'])->get_response();
        });

        /**
         * Route : /baskets/add/
         *
         * Method : POST
         *
         * Parameters :
         *
         */
        $controllers->post('/baskets/add/', function (SilexApplication $app) {
            return $app['api']->create_basket($app['request'])->get_response();
        });

        /**
         * Route : /baskets/BASKET_ID/content/
         *
         * Method : GET
         *
         * Parameters :
         *    BASKET_ID : required INT
         *
         */
        $controllers->get('/baskets/{basket_id}/content/', function (SilexApplication $app, $basket_id) {
            return $app['api']->get_basket($app['request'], $basket_id)->get_response();
        })->assert('basket_id', '\d+');

        $controllers->get('/baskets/{wrong_basket_id}/content/', $bad_request_exception);

        /**
         * Route : /baskets/BASKET_ID/settitle/
         *
         * Method : GET
         *
         * Parameters :
         *    BASKET_ID : required INT
         *
         */
        $controllers->post('/baskets/{basket_id}/setname/', function (SilexApplication $app, $basket_id) {
            return $app['api']
                    ->set_basket_title($app['request'], $basket_id)
                    ->get_response();
        })->assert('basket_id', '\d+');

        $controllers->post('/baskets/{wrong_basket_id}/setname/', $bad_request_exception);

        /**
         * Route : /baskets/BASKET_ID/setdescription/
         *
         * Method : POST
         *
         * Parameters :
         *    BASKET_ID : required INT
         *
         */
        $controllers->post('/baskets/{basket_id}/setdescription/', function (SilexApplication $app, $basket_id) {
            return $app['api']
                    ->set_basket_description($app['request'], $basket_id)
                    ->get_response();
        })->assert('basket_id', '\d+');

        $controllers->post('/baskets/{wrong_basket_id}/setdescription/', $bad_request_exception);

        /**
         * Route : /baskets/BASKET_ID/delete/
         *
         * Method : POST
         *
         * Parameters :
         *    BASKET_ID : required INT
         *
         */
        $controllers->post('/baskets/{basket_id}/delete/', function (SilexApplication $app, $basket_id) {
            return $app['api']->delete_basket($app['request'], $basket_id)->get_response();
        })->assert('basket_id', '\d+');

        $controllers->post('/baskets/{wrong_basket_id}/delete/', $bad_request_exception);

        /**
         * Route : /feeds/list/
         *
         * Method : POST
         *
         * Parameters :
         *
         */
        $controllers->get('/feeds/list/', function (SilexApplication $app) {
            return $app['api']
                    ->search_publications($app['request'], $app['authentication']->getUser())
                    ->get_response();
        });

        $controllers->get('/feeds/content/', function (SilexApplication $app) {
            return $app['api']
                    ->get_publications($app['request'], $app['authentication']->getUser())
                    ->get_response();
        });

        $controllers->get('/feeds/entry/{entry_id}/', function (SilexApplication $app, $entry_id) {
            return $app['api']
                    ->get_feed_entry($app['request'], $entry_id, $app['authentication']->getUser())
                    ->get_response();
        })->assert('entry_id', '\d+');

        $controllers->get('/feeds/entry/{entry_id}/', $bad_request_exception);

        /**
         * Route : /feeds/PUBLICATION_ID/content/
         *
         * Method : GET
         *
         * Parameters :
         *    PUBLICATION_ID : required INT
         *
         */
        $controllers->get('/feeds/{feed_id}/content/', function (SilexApplication $app, $feed_id) {
            return $app['api']
                    ->get_publication($app['request'], $feed_id, $app['authentication']->getUser())
                    ->get_response();
        })->assert('feed_id', '\d+');

        $controllers->get('/feeds/{wrong_feed_id}/content/', $bad_request_exception);

        /**
         * Route : /stories/DATABOX_ID/RECORD_ID/embed/
         *
         * Method : GET
         *
         * Parameters :
         *    DATABOX_ID : required INT
         *    RECORD_ID : required INT
         *
         */
        $controllers->get('/stories/{databox_id}/{story_id}/embed/', function ($databox_id, $story_id) use ($app) {
                $result = $app['api']->get_story_embed($app['request'], $databox_id, $story_id);

                return $result->get_response();
            }
        )->assert('databox_id', '\d+')->assert('story_id', '\d+');

        $controllers->get('/stories/{any_id}/{anyother_id}/embed/', $bad_request_exception);

        $controllers->get('/stories/{databox_id}/{story_id}/', function ($databox_id, $story_id) use ($app) {
            $result = $app['api']->get_story($app['request'], $databox_id, $story_id);

            return $result->get_response();
        })->assert('databox_id', '\d+')->assert('story_id', '\d+');

        $controllers->get('/stories/{any_id}/{anyother_id}/', $bad_request_exception);

        $controllers->get('/stories/{databox_id}/{story_id}/', function ($databox_id, $story_id) use ($app) {
            $result = $app['api']->get_story($app['request'], $databox_id, $story_id);

            return $result->get_response();
        })->assert('databox_id', '\d+')->assert('story_id', '\d+');
        $controllers->get('/stories/{any_id}/{anyother_id}/', $bad_request_exception);

        return $controllers;
    }
}
