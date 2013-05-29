<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Controller\Api\Oauth2;
use Alchemy\Phrasea\Controller\Api\V1;
use Alchemy\Phrasea\Core\Event\ApiLoadEndEvent;
use Alchemy\Phrasea\Core\Event\ApiLoadStartEvent;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

return call_user_func(function($environment = 'prod') {

    $app = new PhraseaApplication($environment);
    $app->disableCookies();

    $app->register(new \API_V1_Timer());
    $app['dispatcher']->dispatch(PhraseaEvents::API_LOAD_START, new ApiLoadStartEvent());

    $app->get('/api/', function(Request $request, SilexApplication $app) {
        $apiAdapter = new \API_V1_adapter($app);

        $result = new \API_V1_result($app, $request, $apiAdapter);

        return $result->set_datas(array(
            'name'          => $app['phraseanet.registry']->get('GV_homeTitle'),
            'type'          => 'phraseanet',
            'description'   => $app['phraseanet.registry']->get('GV_metaDescription'),
            'documentation' => 'https://docs.phraseanet.com/Devel',
            'versions'      => array(
                '1' => array(
                    'number'                  => $apiAdapter->get_version(),
                    'uri'                     => '/api/v1/',
                    'authenticationProtocol'  => 'OAuth2',
                    'authenticationVersion'   => 'draft#v9',
                    'authenticationEndPoints' => array(
                        'authorization_token' => '/api/oauthv2/authorize',
                        'access_token'        => '/api/oauthv2/token'
                    )
                )
            )
        ))->get_response();
    });

    $app->mount('/api/oauthv2', new Oauth2());
    $app->mount('/api/v1', new V1());

    /**
     * Route Errors
     */
    $app->error(function (\Exception $e) use ($app) {

        $request = $app['request'];

        if (0 === strpos($request->getPathInfo(), '/api/oauthv2')) {
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
        }

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
        $response->headers->set('X-Status-Code', $result->get_http_code());

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    });

    $app['dispatcher']->dispatch(PhraseaEvents::API_LOAD_END, new ApiLoadEndEvent());

    return $app;
}, isset($environment) ? $environment : null);
