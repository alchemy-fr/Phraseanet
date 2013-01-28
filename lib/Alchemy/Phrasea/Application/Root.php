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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return call_user_func(function($environment = null) {

    $app = new PhraseaApplication($environment);

    $app->before(function () use ($app) {
        $app['firewall']->requireSetup();
    });

    $app->before(function(Request $request) use ($app) {
        if ($request->cookies->has('persistent') && !$app->isAuthenticated()) {
            try {
                $auth = new \Session_Authentication_PersistentCookie($app, $request->cookies->get('persistent'));
                $app->openAccount($auth, $auth->getSessionId());
            } catch (\Exception $e) {

            }
        }
    });

    $app->bindRoutes();

    $app->error(function(\Exception $e) use ($app) {
        $request = $app['request'];

        if ($e instanceof \Bridge_Exception) {
            $params = array(
                'message'      => $e->getMessage()
                , 'file'         => $e->getFile()
                , 'line'         => $e->getLine()
                , 'r_method'     => $request->getMethod()
                , 'r_action'     => $request->getRequestUri()
                , 'r_parameters' => ($request->getMethod() == 'GET' ? array() : $request->request->all())
            );

            if ($e instanceof \Bridge_Exception_ApiConnectorNotConfigured) {
                $params = array_merge($params, array('account' => $app['current_account']));

                $response = new Response($app['twig']->render('/prod/actions/Bridge/notconfigured.html.twig', $params), 200, array('X-Status-Code' => 200));
            } elseif ($e instanceof \Bridge_Exception_ApiConnectorNotConnected) {
                $params = array_merge($params, array('account' => $app['current_account']));

                $response = new Response($app['twig']->render('/prod/actions/Bridge/disconnected.html.twig', $params), 200, array('X-Status-Code' => 200));
            } elseif ($e instanceof \Bridge_Exception_ApiConnectorAccessTokenFailed) {
                $params = array_merge($params, array('account' => $app['current_account']));

                $response = new Response($app['twig']->render('/prod/actions/Bridge/disconnected.html.twig', $params), 200, array('X-Status-Code' => 200));
            } elseif ($e instanceof \Bridge_Exception_ApiDisabled) {
                $params = array_merge($params, array('api' => $e->get_api()));

                $response = new Response($app['twig']->render('/prod/actions/Bridge/deactivated.html.twig', $params), 200, array('X-Status-Code' => 200));
            } else {
                $response = new Response($app['twig']->render('/prod/actions/Bridge/error.html.twig', $params), 200, array('X-Status-Code' => 200));
            }

            $response->headers->set('Phrasea-StatusCode', 200);

            return $response;
        }

        if ($request->getRequestFormat() == 'json') {
            $datas = array(
                'success' => false
                , 'message' => $e->getMessage()
            );

            return $app->json($datas, 200, array('X-Status-Code' => 200));
        }

        if ($e instanceof HttpExceptionInterface) {
            $headers = $e->getHeaders();

            if (isset($headers['X-Phraseanet-Redirect'])) {
                return new RedirectResponse($headers['X-Phraseanet-Redirect'], 302, array('X-Status-Code' => 302));
            }

            $message = isset(Response::$statusTexts[$e->getStatusCode()]) ? Response::$statusTexts[$e->getStatusCode()] : '';

            return new Response($message, $e->getStatusCode(), $e->getHeaders());
        }

        if ($e instanceof \Exception_BadRequest) {
            return new Response('Bad Request', 400, array('X-Status-Code' => 400));
        }
        if ($e instanceof \Exception_Forbidden) {
            return new Response('Forbidden', 403, array('X-Status-Code' => 403));
        }

        if ($e instanceof \Exception_Session_NotAuthenticated) {
            $code = 403;
            $message = 'Forbidden';
        } elseif ($e instanceof \Exception_NotAllowed) {
            $code = 403;
            $message = 'Forbidden';
        } elseif ($e instanceof \Exception_NotFound) {
            $code = 404;
            $message = 'Not Found';
        } else {
            throw $e;
        }

        return new Response($message, $code, array('X-Status-Code' => $code));
    });

    return $app;
}, isset($environment) ? $environment : null);
