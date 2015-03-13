<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Minifier;
use Alchemy\Phrasea\Controller\Permalink;
use Alchemy\Phrasea\Controller\Datafiles;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiCorsSubscriber;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Controller\Api\Oauth2;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Api\V1;
use Alchemy\Phrasea\Core\Event\ApiResultEvent;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiOauth2ErrorsSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiExceptionHandlerSubscriber;
use Alchemy\Phrasea\Core\Provider\JsonSchemaServiceProvider;
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

    // handle API content negotiation
    $app->before(function(Request $request) use ($app) {
        // register custom API format
        $request->setFormat(Result::FORMAT_JSON_EXTENDED, V1::$extendedContentTypes['json']);
        $request->setFormat(Result::FORMAT_YAML_EXTENDED, V1::$extendedContentTypes['yaml']);
        $request->setFormat(Result::FORMAT_JSONP_EXTENDED, V1::$extendedContentTypes['jsonp']);
        $request->setFormat(Result::FORMAT_JSONP, array('text/javascript', 'application/javascript'));

        // handle content negociation
        $priorities = array('application/json', 'application/yaml', 'text/yaml', 'text/javascript', 'application/javascript');
        foreach (V1::$extendedContentTypes['json'] as $priorities[]);
        foreach (V1::$extendedContentTypes['yaml'] as $priorities[]);
        $format = $app['format.negociator']->getBest($request->headers->get('accept', 'application/json') ,$priorities);

        // throw unacceptable http error if API can not handle asked format
        if (null === $format) {
            $app->abort(406);
        }
        // set request format according to negotiated content or override format with JSONP if callback parameter is defined
        if (trim($request->get('callback')) !== '') {
            $request->setRequestFormat(Result::FORMAT_JSONP);
        } else {
            $request->setRequestFormat($request->getFormat($format->getValue()));
        }

        // tells whether asked format is extended or not
        $request->attributes->set('_extended', in_array(
            $request->getRequestFormat(Result::FORMAT_JSON),
            array(
                Result::FORMAT_JSON_EXTENDED,
                Result::FORMAT_YAML_EXTENDED,
                Result::FORMAT_JSONP_EXTENDED
            )
        ));
    }, PhraseaApplication::EARLY_EVENT);

    $app->after(function(Request $request, Response $response) use ($app) {
        if ($request->getRequestFormat(Result::FORMAT_JSON) === Result::FORMAT_JSONP && !$response->isOk() && !$response->isServerError()) {
            $response->setStatusCode(200);
        }
        // set response content type
        if (!$response->headers->get('Content-Type')) {
            $response->headers->set('Content-Type', $request->getMimeType($request->getRequestFormat(Result::FORMAT_JSON)));
        }
    });

    $app->register(new JsonSchemaServiceProvider());
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
    $app->mount('/datafiles/', new Datafiles());
    $app->mount('/api/v1', new V1());
    $app->mount('/permalink/', new Permalink());
    $app->mount('/include/minify/', new Minifier());

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
