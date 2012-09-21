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

use Silex\Application as SilexApplication;
use Alchemy\Phrasea\Application as PhraseaApplication;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @package     APIv1
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function() {

            $app = new PhraseaApplication();

            $app->get('/', function(Request $request, SilexApplication $app) {
                    $registry = $app['phraseanet.registry'];

                    $apiAdapter = new \API_V1_adapter($app);

                    $result = new \API_V1_result($request, $apiAdapter);

                    return $result->set_datas(
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
                        )->get_response();
                });

            return $app;
        }
);
