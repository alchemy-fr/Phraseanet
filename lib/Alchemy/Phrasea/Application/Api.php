<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiCorsSubscriber;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Controller\Api\Oauth2;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Api\V1;
use Alchemy\Phrasea\Core\Event\ApiResultEvent;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiOauth2ErrorsSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiExceptionHandlerSubscriber;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

return call_user_func(function ($environment = PhraseaApplication::ENV_PROD) {

    $app = new PhraseaApplication($environment);
    $app->loadPlugins();

    $app['exception_handler'] = $app->share(function ($app) {
        return new ApiExceptionHandlerSubscriber($app);
    });
    $app['monolog'] = $app->share($app->extend('monolog', function (Logger $monolog) {
        $monolog->pushProcessor(new WebProcessor());

        return $monolog;
    }));

    $app->get('/api/', function (Request $request, SilexApplication $app) {
        return Result::create($request, [
            'name'          => $app['conf']->get(['registry', 'general', 'title']),
            'type'          => 'phraseanet',
            'description'   => $app['conf']->get(['registry', 'general', 'description']),
            'documentation' => 'https://docs.phraseanet.com/Devel',
            'versions'      => [
                '1' => [
                    'number'                  => V1::VERSION,
                    'uri'                     => '/api/v1/',
                    'authenticationProtocol'  => 'OAuth2',
                    'authenticationVersion'   => 'draft#v9',
                    'authenticationEndPoints' => [
                        'authorization_token' => '/api/oauthv2/authorize',
                        'access_token'        => '/api/oauthv2/token'
                    ]
                ]
            ]
        ])->createResponse();
    });

    $app->mount('/api/oauthv2', new Oauth2());
    $app->mount('/api/v1', new V1());

    $app['dispatcher'] = $app->share($app->extend('dispatcher', function ($dispatcher, PhraseaApplication $app) {
        $dispatcher->addSubscriber(new ApiOauth2ErrorsSubscriber($app['phraseanet.exception_handler'], $app['translator']));

        return $dispatcher;
    }));
    $app->after(function (Request $request, Response $response) use ($app) {
        $app['dispatcher']->dispatch(PhraseaEvents::API_RESULT, new ApiResultEvent($request, $response));
    });
    $app['dispatcher']->addSubscriber(new ApiCorsSubscriber($app));

    return $app;
}, isset($environment) ? $environment : PhraseaApplication::ENV_PROD);
