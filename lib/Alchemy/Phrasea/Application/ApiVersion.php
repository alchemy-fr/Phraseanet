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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception;

/**
 *
 * @package     APIv1
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function() {

            $app = new \Silex\Application();

            $app["Core"] = \bootstrap::getCore();

            $app["appbox"] = \appbox::get_instance($app['Core']);

            $app->get(
                '/', function(Request $request) use ($app) {
                    $registry = $app["Core"]->getRegistry();

                    $apiAdapter = new \API_V1_adapter(false, $app["appbox"], $app["Core"]);

                    $result = new \API_V1_result($request, $apiAdapter);

                    $result->set_datas(
                        array(
                            'name'          => $registry->get('GV_homeTitle'),
                            'type'          => 'phraseanet',
                            'description'   => $registry->get('GV_metaDescription'),
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
                        )
                    );

                    return $result->get_response();
                });

            return $app;
        });

