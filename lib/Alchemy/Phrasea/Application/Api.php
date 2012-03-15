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

require_once __DIR__ . "/../../../../lib/classes/API/OAuth2/Autoloader.class.php";
require_once __DIR__ . "/../../../../lib/bootstrap.php";

\API_OAuth2_Autoloader::register();

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception;

/**
 *
 * @package     APIv1
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function()
    {


      $app = new \Silex\Application();

      $app["Core"] = \bootstrap::getCore();

      $app["appbox"] = \appbox::get_instance($app['Core']);

      /**
       * Associated user to related token
       * @var User_Adapter
       */
      $app['p4user'] = null;

      /**
       * @var API_OAuth2_Token
       */
      $app['token'] = null;

      /**
       * Protected Closure
       * @var Closure
       * @return Symfony\Component\HttpFoundation\Response
       */
      $app['response'] = $app->protect(function ($result)
        {
          $response = new Response(
              $result->format()
              , $result->get_http_code()
              , array('Content-Type' => $result->get_content_type())
          );
          $response->setCharset('UTF-8');

          return $response;
        });

      /**
       * Api Service
       * @var Closure
       */
      $app['api'] = function () use ($app)
        {
          return new \API_V1_adapter(false, $app["appbox"], $app["Core"]);
        };



      $parseRoute = function ($route, Response $response)
        {
          $ressource      = $general        = $aspect         = $action         = null;
          $exploded_route = explode('/', \p4string::delFirstSlash(\p4string::delEndSlash($route)));
          if (sizeof($exploded_route) > 0 && $response->isOk())
          {
            $ressource = $exploded_route[0];

            if (sizeof($exploded_route) == 2 && (int) $exploded_route[1] == 0)
            {
              $general = $exploded_route[1];
            }
            else
            {
              switch ($ressource)
              {
                case \API_V1_Log::DATABOXES_RESSOURCE :
                  if ((int) $exploded_route[1] > 0 && sizeof($exploded_route) == 3)
                    $aspect = $exploded_route[2];
                  break;
                case \API_V1_Log::RECORDS_RESSOURCE :
                  if ((int) $exploded_route[1] > 0 && sizeof($exploded_route) == 4)
                  {
                    if (!isset($exploded_route[3]))
                      $aspect = "record";
                    elseif (preg_match("/^set/", $exploded_route[3]))
                      $action = $exploded_route[3];
                    else
                      $aspect = $exploded_route[3];
                  }
                  break;
                case \API_V1_Log::BASKETS_RESSOURCE :
                  if ((int) $exploded_route[1] > 0 && sizeof($exploded_route) == 3)
                  {
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
       * oAuth verification process
       */
      $app->before(function($request) use ($app)
        {
          $session        = $app["appbox"]->get_session();
          $oauth2_adapter = new \API_OAuth2_Adapter($app["appbox"]);
          $oauth2_adapter->verifyAccessToken();

          $app['p4user'] = \User_Adapter::getInstance($oauth2_adapter->get_usr_id(), $app["appbox"]);
          $app['token']  = \API_OAuth2_Token::load_by_oauth_token($app["appbox"], $oauth2_adapter->getToken());

          if ($session->is_authenticated())
          {
            return;
          }
          
          if ($oauth2_adapter->has_ses_id())
          {
            try
            {
              $session->restore($app['p4user'], $oauth2_adapter->get_ses_id());

              return;
            }
            catch (\Exception $e)
            {

            }
          }
          $auth = new \Session_Authentication_None($app['p4user']);
          $session->authenticate($auth);
          $oauth2_adapter->remember_this_ses_id($session->get_ses_id());

          return;
        });


      /**
       * oAUth log process
       */
      $app->after(function (Request $request, Response $response) use ($app, $parseRoute)
        {
          $account  = $app['token']->get_account();
          $pathInfo = $request->getPathInfo();
          $route    = $parseRoute($pathInfo, $response);
          $log      = \API_V1_Log::create(
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
      $bad_request_exception = function()
        {
          throw new \API_V1_exception_badrequest();
        };

      /**
       * *******************************************************************
       * Route : /databoxes/list/FORMAT/
       *
       * Method : GET
       *
       * Parameters :
       *
       */
      $route = '/databoxes/list/';
      $app->get(
        $route, function() use ($app)
        {
          return $app['response']($app['api']->get_databoxes($app['request']));
        }
      );

      /**
       * *******************************************************************
       *
       * Route /databoxes/DATABOX_ID/collections/FORMAT/
       *
       * Method : GET
       *
       * Parameters ;
       *    DATABOX_ID : required INT
       */
      $route = '/databoxes/{databox_id}/collections/';
      $app->get(
        $route, function($databox_id) use ($app)
        {
          $result = $app['api']->get_databox_collections($app['request'], $databox_id);

          return $app['response']($result);
        }
      )->assert('databox_id', '\d+');

      $app->get('/databoxes/{any_id}/collections/', $bad_request_exception);


      /**
       * *******************************************************************
       * Route /databoxes/DATABOX_ID/status/FORMAT/
       *
       * Method : GET
       *
       * Parameters ;
       *    DATABOX_ID : required INT
       *
       */
      $route = '/databoxes/{databox_id}/status/';
      $app->get(
        $route, function($databox_id) use ($app)
        {
          $result = $app['api']->get_databox_status($app['request'], $databox_id);

          return $app['response']($result);
        }
      )->assert('databox_id', '\d+');

      $app->get('/databoxes/{any_id}/status/', $bad_request_exception);

      /**
       * Route /databoxes/DATABOX_ID/metadatas/FORMAT/
       *
       * Method : GET
       *
       * Parameters ;
       *    DATABOX_ID : required INT
       */
      $route = '/databoxes/{databox_id}/metadatas/';
      $app->get(
        $route, function($databox_id) use ($app)
        {
          $result = $app['api']->get_databox_metadatas($app['request'], $databox_id);

          return $app['response']($result);
        }
      )->assert('databox_id', '\d+');

      $app->get('/databoxes/{any_id}/metadatas/', $bad_request_exception);

      /**
       * Route /databoxes/DATABOX_ID/termsOfUse/FORMAT/
       *
       * Method : GET
       *
       * Parameters ;
       *    DATABOX_ID : required INT
       */
      $route = '/databoxes/{databox_id}/termsOfUse/';
      $app->get(
        $route, function($databox_id) use ($app)
        {
          $result = $app['api']->get_databox_terms($app['request'], $databox_id);

          return $app['response']($result);
        }
      )->assert('databox_id', '\d+');

      $app->get('/databoxes/{any_id}/termsOfUse/', $bad_request_exception);





      /**
       * Route : /records/search/FORMAT/
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
        $route, function() use ($app)
        {
          $result = $app['api']->search_records($app['request']);

          return $app['response']($result);
        }
      );


      $route = '/records/{databox_id}/{record_id}/caption/';
      $app->get(
        $route, function($databox_id, $record_id) use ($app)
        {
          $result = $app['api']->caption_records($app['request'], $databox_id, $record_id);

          return $app['response']($result);
        }
      )->assert('databox_id', '\d+')->assert('record_id', '\d+');

      $app->get('/records/{any_id}/{anyother_id}/caption/', $bad_request_exception);


      /**
       * Route : /records/DATABOX_ID/RECORD_ID/metadatas/FORMAT/
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
        $route, function($databox_id, $record_id) use ($app)
        {
          $result = $app['api']->get_record_metadatas($app['request'], $databox_id, $record_id);

          return $app['response']($result);
        }
      )->assert('databox_id', '\d+')->assert('record_id', '\d+');

      $app->get('/records/{any_id}/{anyother_id}/metadatas/', $bad_request_exception);

      /**
       * Route : /records/DATABOX_ID/RECORD_ID/status/FORMAT/
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
        $route, function($databox_id, $record_id) use ($app)
        {
          $result = $app['api']->get_record_status($app['request'], $databox_id, $record_id);

          return $app['response']($result);
        }
      )->assert('databox_id', '\d+')->assert('record_id', '\d+');

      $app->get('/records/{any_id}/{anyother_id}/status/', $bad_request_exception);

      /**
       * Route : /records/DATABOX_ID/RECORD_ID/related/FORMAT/
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
        $route, function($databox_id, $record_id) use ($app)
        {
          $result = $app['api']->get_record_related($app['request'], $databox_id, $record_id);

          return $app['response']($result);
        }
      )->assert('databox_id', '\d+')->assert('record_id', '\d+');

      $app->get('/records/{any_id}/{anyother_id}/related/', $bad_request_exception);

      /**
       * Route : /records/DATABOX_ID/RECORD_ID/embed/FORMAT/
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
        $route, function($databox_id, $record_id) use ($app)
        {
          $result = $app['api']->get_record_embed($app['request'], $databox_id, $record_id);

          return $app['response']($result);
        }
      )->assert('databox_id', '\d+')->assert('record_id', '\d+');

      $app->get('/records/{any_id}/{anyother_id}/embed/', $bad_request_exception);

      /**
       * Route : /records/DATABOX_ID/RECORD_ID/setmetadatas/FORMAT/
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
        $route, function($databox_id, $record_id) use ($app)
        {
          $result = $app['api']->set_record_metadatas($app['request'], $databox_id, $record_id);

          return $app['response']($result);
        }
      )->assert('databox_id', '\d+')->assert('record_id', '\d+');

      $app->post('/records/{any_id}/{anyother_id}/setmetadatas/', $bad_request_exception);

      /**
       * Route : /records/DATABOX_ID/RECORD_ID/setstatus/FORMAT/
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
        $route, function($databox_id, $record_id) use ($app)
        {
          $result = $app['api']->set_record_status($app['request'], $databox_id, $record_id);

          return $app['response']($result);
        }
      )->assert('databox_id', '\d+')->assert('record_id', '\d+');

      $app->post('/records/{any_id}/{anyother_id}/setstatus/', $bad_request_exception);


      /**
       * Route : /records/DATABOX_ID/RECORD_ID/setcollection/FORMAT/
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
        $route, function($databox_id, $record_id) use ($app)
        {
          $result = $app['api']->set_record_collection($app['request'], $databox_id, $record_id);

          return $app['response']($result);
        }
      )->assert('databox_id', '\d+')->assert('record_id', '\d+');
      $app->post('/records/{wrong_databox_id}/{wrong_record_id}/setcollection/', $bad_request_exception);


      $route = '/records/{databox_id}/{record_id}/';
      $app->get($route, function($databox_id, $record_id) use ($app)
        {
          $result = $app['api']->get_record($app['request'], $databox_id, $record_id);

          return $app['response']($result);
        })->assert('databox_id', '\d+')->assert('record_id', '\d+');
      $app->get('/records/{any_id}/{anyother_id}/', $bad_request_exception);

      /**
       * Route : /baskets/list/FORMAT/
       *
       * Method : POST
       *
       * Parameters :
       *
       */
      $route = '/baskets/list/';
      $app->get(
        $route, function() use ($app)
        {
          $result = $app['api']->search_baskets($app['request']);

          return $app['response']($result);
        }
      );


      /**
       * Route : /baskets/add/FORMAT/
       *
       * Method : POST
       *
       * Parameters :
       *
       */
      $route = '/baskets/add/';
      $app->post(
        $route, function() use ($app)
        {
          $result = $app['api']->create_basket($app['request']);

          return $app['response']($result);
        }
      );



      /**
       * Route : /baskets/BASKET_ID/content/FORMAT/
       *
       * Method : GET
       *
       * Parameters :
       *    BASKET_ID : required INT
       *
       */
      $route = '/baskets/{basket_id}/content/';
      $app->get(
        $route, function($basket_id) use ($app)
        {
          $result = $app['api']->get_basket($app['request'], $basket_id);

          return $app['response']($result);
        }
      )->assert('basket_id', '\d+');
      $app->get('/baskets/{wrong_basket_id}/content/', $bad_request_exception);


      /**
       * Route : /baskets/BASKET_ID/settitle/FORMAT/
       *
       * Method : GET
       *
       * Parameters :
       *    BASKET_ID : required INT
       *
       */
      $route = '/baskets/{basket_id}/setname/';
      $app->post(
        $route, function($basket_id) use ($app)
        {
          $result = $app['api']->set_basket_title($app['request'], $basket_id);

          return $app['response']($result);
        }
      )->assert('basket_id', '\d+');
      $app->post('/baskets/{wrong_basket_id}/setname/', $bad_request_exception);


      /**
       * Route : /baskets/BASKET_ID/setdescription/FORMAT/
       *
       * Method : POST
       *
       * Parameters :
       *    BASKET_ID : required INT
       *
       */
      $route = '/baskets/{basket_id}/setdescription/';
      $app->post(
        $route, function($basket_id) use ($app)
        {
          $result = $app['api']->set_basket_description($app['request'], $basket_id);

          return $app['response']($result);
        }
      )->assert('basket_id', '\d+');
      $app->post('/baskets/{wrong_basket_id}/setdescription/', $bad_request_exception);

      /**
       * Route : /baskets/BASKET_ID/delete/FORMAT/
       *
       * Method : POST
       *
       * Parameters :
       *    BASKET_ID : required INT
       *
       */
      $route = '/baskets/{basket_id}/delete/';
      $app->post(
        $route, function($basket_id) use ($app)
        {
          $result = $app['api']->delete_basket($app['request'], $basket_id);

          return $app['response']($result);
        }
      )->assert('basket_id', '\d+');
      $app->post('/baskets/{wrong_basket_id}/delete/', $bad_request_exception);


      /**
       * Route : /feeds/list/FORMAT/
       *
       * Method : POST
       *
       * Parameters :
       *
       */
//  public function search_publications(\Symfony\Component\HttpFoundation\Request $app['request']);


      $route = '/feeds/list/';
      $app->get(
        $route, function() use ($app)
        {
          $result = $app['api']->search_publications($app['request'], $app['p4user']);

          return $app['response']($result);
        }
      );

      /**
       * Route : /feeds/PUBLICATION_ID/content/FORMAT/
       *
       * Method : GET
       *
       * Parameters :
       *    PUBLICATION_ID : required INT
       *
       */
//  public function get_publication(\Symfony\Component\HttpFoundation\Request $app['request'], $publication_id);

      $route = '/feeds/{feed_id}/content/';
      $app->get(
        $route, function($feed_id) use ($app)
        {
          $result = $app['api']->get_publication($app['request'], $feed_id, $app['p4user']);

          return $app['response']($result);
        }
      )->assert('feed_id', '\d+');
      $app->get('/feeds/{wrong_feed_id}/content/', $bad_request_exception);

      /**
       * *******************************************************************
       *
       * Route Errors
       *
       */
      $app->error(function (\Exception $e) use ($app)
        {

          if ($e instanceof \API_V1_exception_methodnotallowed)
            $code = \API_V1_result::ERROR_METHODNOTALLOWED;
          elseif ($e instanceof Exception\MethodNotAllowedHttpException)
            $code = \API_V1_result::ERROR_METHODNOTALLOWED;
          elseif ($e instanceof \API_V1_exception_badrequest)
            $code = \API_V1_result::ERROR_BAD_REQUEST;
          elseif ($e instanceof \API_V1_exception_forbidden)
            $code = \API_V1_result::ERROR_FORBIDDEN;
          elseif ($e instanceof \API_V1_exception_unauthorized)
            $code = \API_V1_result::ERROR_UNAUTHORIZED;
          elseif ($e instanceof \API_V1_exception_internalservererror)
            $code = \API_V1_result::ERROR_INTERNALSERVERERROR;
//          elseif ($e instanceof API_V1_exception_notfound)
//              $code = API_V1_result::ERROR_NOTFOUND;
          elseif ($e instanceof \Exception_NotFound)
            $code = \API_V1_result::ERROR_NOTFOUND;
          elseif ($e instanceof Exception\NotFoundHttpException)
            $code = \API_V1_result::ERROR_NOTFOUND;
          else
            $code = \API_V1_result::ERROR_INTERNALSERVERERROR;

          $result = $app['api']->get_error_message($app['request'], $code);

          return $app['response']($result);
        });
////  public function get_version();
////
////
////  /**
////   * Route : /records/DATABOX_ID/RECORD_ID/addtobasket/FORMAT/
////   *
////   * Method : POST
////   *
////   * Parameters :
////   *    DATABOX_ID : required INT
////   *    RECORD_ID : required INT
////   *
////   */
////  public function add_record_tobasket(\Symfony\Component\HttpFoundation\Request $app['request'], $databox_id, $record_id);
////
////
////  /**
////   * Route : /feeds/PUBLICATION_ID/remove/FORMAT/
////   *
////   * Method : GET
////   *
////   * Parameters :
////   *    PUBLICATION_ID : required INT
////   *
////   */
////  public function remove_publications(\Symfony\Component\HttpFoundation\Request $app['request'], $publication_id);
////
////
////  /**
////   * Route : /users/search/FORMAT/
////   *
////   * Method : POST-GET
////   *
////   * Parameters :
////   *
////   */
////  public function search_users(\Symfony\Component\HttpFoundation\Request $app['request']);
////
////  /**
////   * Route : /users/USER_ID/access/FORMAT/
////   *
////   * Method : GET
////   *
////   * Parameters :
////   *    USER_ID : required INT
////   *
////   */
////  public function get_user_acces(\Symfony\Component\HttpFoundation\Request $app['request'], $usr_id);
////
////  /**
////   * Route : /users/add/FORMAT/
////   *
////   * Method : POST
////   *
////   * Parameters :
////   *
////   */
////  public function add_user(\Symfony\Component\HttpFoundation\Request $app['request']);
      return $app;
    });
