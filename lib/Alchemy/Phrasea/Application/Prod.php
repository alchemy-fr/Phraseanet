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

use Alchemy\Phrasea\Controller\Prod as Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function() {
            $app = new \Silex\Application();
            $app['Core'] = \bootstrap::getCore();

            $app->before(function(Request $request) {
                    $request->setRequestFormat(
                        $request->getFormat(
                            array_shift(
                                $request->getAcceptableContentTypes()
                            )
                        )
                    );
                });

            $app->mount('/UserPreferences/', new Controller\UserPreferences());
            $app->mount('/query/', new Controller\Query());
            $app->mount('/baskets', new Controller\Basket());
            $app->mount('/story', new Controller\Story());
            $app->mount('/WorkZone', new Controller\WorkZone());
            $app->mount('/lists', new Controller\UsrLists());
            $app->mount('/MustacheLoader', new Controller\MustacheLoader());
            $app->mount('/records/edit', new Controller\Edit());
            $app->mount('/records/movecollection', new Controller\MoveCollection());
            $app->mount('/bridge/', new Controller\Bridge());
            $app->mount('/push/', new Controller\Push());
            $app->mount('/printer/', new Controller\Printer());
            $app->mount('/TOU/', new Controller\TOU());
            $app->mount('/feeds', new Controller\Feed());
            $app->mount('/tooltip', new Controller\Tooltip());
            $app->mount('/language', new Controller\Language());
            $app->mount('/tools/', new Controller\Tools());
            $app->mount('/lazaret/', new Controller\Lazaret());
            $app->mount('/upload/', new Controller\Upload());
            $app->mount('/', new Controller\Root());

            $app->error(function (\Exception $e, $code) use ($app) {
                    /* @var $request \Symfony\Component\HttpFoundation\Request */
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

                        /* @var $twig \Twig_Environment */
                        $twig = $app['Core']->getTwig();

                        if ($e instanceof \Bridge_Exception_ApiConnectorNotConfigured) {
                            $params = array_merge($params, array('account' => $app['current_account']));

                            return new response($twig->render('/prod/actions/Bridge/notconfigured.twig', $params), 200);
                        } elseif ($e instanceof \Bridge_Exception_ApiConnectorNotConnected) {
                            $params = array_merge($params, array('account' => $app['current_account']));

                            return new response($twig->render('/prod/actions/Bridge/disconnected.twig', $params), 200);
                        } elseif ($e instanceof \Bridge_Exception_ApiConnectorAccessTokenFailed) {
                            $params = array_merge($params, array('account' => $app['current_account']));

                            return new response($twig->render('/prod/actions/Bridge/disconnected.twig', $params), 200);
                        } elseif ($e instanceof \Bridge_Exception_ApiDisabled) {
                            $params = array_merge($params, array('api' => $e->get_api()));

                            return new response($twig->render('/prod/actions/Bridge/deactivated.twig', $params), 200);
                        }

                        return new response($twig->render('/prod/actions/Bridge/error.twig', $params), 200);
                    }
                    if ($request->getRequestFormat() == 'json') {
                        $datas = array(
                            'success' => false
                            , 'message' => $e->getMessage()
                        );

                        $json = $app['Core']['Serializer']->serialize($datas, 'json');

                        return new Response($json, 200, array('Content-Type' => 'application/json'));
                    }
                    if ($e instanceof \Exception_BadRequest) {
                        return new Response('Bad Request', 400);
                    }
                    if ($e instanceof \Exception_NotFound) {
                        return new Response('Not Found', 404);
                    }
                    if ($e instanceof \Exception_Forbidden) {
                        return new Response('Not Found', 403);
                    }
                });

            return $app;
        });
