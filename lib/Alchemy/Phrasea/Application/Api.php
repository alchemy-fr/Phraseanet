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
use Alchemy\Phrasea\Controller\Datafiles;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiCorsSubscriber;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Controller\Api\Oauth2;
use Alchemy\Phrasea\Controller\Api\V1;
use Alchemy\Phrasea\Core\Event\ApiLoadEndEvent;
use Alchemy\Phrasea\Core\Event\ApiLoadStartEvent;
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
        $request->setFormat(\API_V1_result::FORMAT_JSON_EXTENDED, \API_V1_adapter::$extendedContentTypes['json']);
        $request->setFormat(\API_V1_result::FORMAT_YAML_EXTENDED, \API_V1_adapter::$extendedContentTypes['yaml']);
        $request->setFormat(\API_V1_result::FORMAT_JSONP_EXTENDED, \API_V1_adapter::$extendedContentTypes['jsonp']);
        $request->setFormat(\API_V1_result::FORMAT_JSONP, array('text/javascript', 'application/javascript'));

        // handle content negociation
        $priorities = array('application/json', 'application/yaml', 'text/yaml', 'text/javascript', 'application/javascript');
        foreach (\API_V1_adapter::$extendedContentTypes['json'] as $priorities[]);
        foreach (\API_V1_adapter::$extendedContentTypes['yaml'] as $priorities[]);
        $format = $app['format.negociator']->getBest($request->headers->get('accept', 'application/json') ,$priorities);

        // throw unacceptable http error if API can not handle asked format
        if (null === $format) {
            $app->abort(406);
        }
        // set request format according to negotiated content or override format with JSONP if callback parameter is defined
        if (trim($request->get('callback')) !== '') {
            $request->setRequestFormat(\API_V1_result::FORMAT_JSONP);
        } else {
            $request->setRequestFormat($request->getFormat($format->getValue()));
        }

        // tells whether asked format is extended or not
        $request->attributes->set('_extended', in_array(
            $request->getRequestFormat(\API_V1_result::FORMAT_JSON),
            array(
                \API_V1_result::FORMAT_JSON_EXTENDED,
                \API_V1_result::FORMAT_YAML_EXTENDED,
                \API_V1_result::FORMAT_JSONP_EXTENDED
            )
        ));
    }, PhraseaApplication::EARLY_EVENT);

    $app->after(function(Request $request, Response $response) use ($app) {
        if ($request->getRequestFormat(\API_V1_result::FORMAT_JSON) === \API_V1_result::FORMAT_JSONP && !$response->isOk() && !$response->isServerError()) {
            $response->setStatusCode(200);
        }
        // set response content type
        if (!$response->headers->get('Content-Type')) {
            $response->headers->set('Content-Type', $request->getMimeType($request->getRequestFormat(\API_V1_result::FORMAT_JSON)));
        }
    });

    $app->register(new JsonSchemaServiceProvider());
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
    $app->mount('/datafiles/', new Datafiles());
    $app->mount('/api/v1', new V1());

    $app['dispatcher']->addSubscriber(new ApiOauth2ErrorsSubscriber($app['phraseanet.exception_handler']));
    $app['dispatcher']->addSubscriber(new ApiCorsSubscriber($app));
    $app['dispatcher']->dispatch(PhraseaEvents::API_LOAD_END, new ApiLoadEndEvent());

    return $app;
}, isset($environment) ? $environment : PhraseaApplication::ENV_PROD);
