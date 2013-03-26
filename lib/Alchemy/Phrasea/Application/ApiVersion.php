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

use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Core\Event\ApiLoadEndEvent;
use Alchemy\Phrasea\Core\Event\ApiLoadStartEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @package     APIv1
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function() {

            $app = new \Silex\Application();

            $app->register(new \API_V1_Timer());

            $app['dispatcher']->dispatch(PhraseaEvents::API_LOAD_START, new ApiLoadStartEvent());

            $app["Core"] = \bootstrap::getCore();

            $app->get(
                '/', function(Request $request) use ($app) {
                    $registry = $app["Core"]->getRegistry();

                    $apiAdapter = new \API_V1_adapter($app, false, \appbox::get_instance($app['Core']), $app["Core"]);

                    $result = new \API_V1_result($app, $request, $apiAdapter);

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

            $app['dispatcher']->dispatch(PhraseaEvents::API_LOAD_END, new ApiLoadEndEvent());

            return $app;
        });

