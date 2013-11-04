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
use Alchemy\Phrasea\Core\Event\Subscriber\ApiOauth2ErrorsSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiExceptionHandlerSubscriber;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;

return call_user_func(function ($environment = PhraseaApplication::ENV_PROD) {

    $app = new PhraseaApplication($environment);
    $app->loadPlugins();

    $app['exception_handler'] = $app->share(function ($app) {
        return new ApiExceptionHandlerSubscriber($app);
    });

    $app->register(new \API_V1_Timer());
    $app['dispatcher']->dispatch(PhraseaEvents::API_LOAD_START, new ApiLoadStartEvent());

    $app->get('/api/', function (Request $request, SilexApplication $app) {
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

    $app['dispatcher']->addSubscriber(new ApiOauth2ErrorsSubscriber($app['phraseanet.exception_handler']));
    $app['dispatcher']->dispatch(PhraseaEvents::API_LOAD_END, new ApiLoadEndEvent());

    return $app;
}, isset($environment) ? $environment : PhraseaApplication::ENV_PROD);
