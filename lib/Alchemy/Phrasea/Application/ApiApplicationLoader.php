<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\ControllerProvider\Api\OAuth2;
use Alchemy\Phrasea\ControllerProvider\Api\V1;
use Alchemy\Phrasea\ControllerProvider\Api\V2;
use Alchemy\Phrasea\ControllerProvider\Api\V3;
use Alchemy\Phrasea\ControllerProvider\Datafiles;
use Alchemy\Phrasea\ControllerProvider\MediaAccessor;
use Alchemy\Phrasea\ControllerProvider\Minifier;
use Alchemy\Phrasea\ControllerProvider\Permalink;
use Alchemy\Phrasea\Core\Event\ApiResultEvent;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiExceptionHandlerSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiOauth2ErrorsSubscriber;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Core\Provider\JsonSchemaServiceProvider;
use Alchemy\Phrasea\Report\ControllerProvider\ApiReportControllerProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ApiApplicationLoader extends BaseApplicationLoader
{
    protected function doPrePluginServiceRegistration(Application $app)
    {
        $app->register(new OAuth2());
        $app->register(new V1());
        $app->register(new V2());
        $app->register(new V3());
        $app->register(new ApiReportControllerProvider());
        $app->register(new JsonSchemaServiceProvider());
    }

    protected function createExceptionHandler(Application $app)
    {
        return new ApiExceptionHandlerSubscriber($app['monolog']);
    }

    protected function bindRoutes(Application $app)
    {
        $app['phraseanet.content-negotiation.priorities'] = array_merge(
            ['application/json', 'application/yaml', 'text/yaml', 'text/javascript', 'application/javascript'],
            V1::$extendedContentTypes['json'],
            V1::$extendedContentTypes['jsonp'],
            V1::$extendedContentTypes['yaml']
        );

        $app['phraseanet.content-negotiation.custom_formats'] = [
            // register custom API format
            Result::FORMAT_JSON_EXTENDED => V1::$extendedContentTypes['json'],
            Result::FORMAT_YAML_EXTENDED => V1::$extendedContentTypes['yaml'],
            Result::FORMAT_JSONP_EXTENDED => V1::$extendedContentTypes['jsonp'],
            Result::FORMAT_JSONP => ['text/javascript', 'application/javascript'],
        ];

        // handle API content negotiation
        $app->before(function(Request $request) {
            // set request format according to negotiated content or override format with JSONP if callback parameter is defined
            if (trim($request->get('callback')) !== '') {
                $request->setRequestFormat(Result::FORMAT_JSONP);
            }

            // tells whether asked format is extended or not
            $request->attributes->set('_extended', in_array(
                $request->getRequestFormat(Result::FORMAT_JSON),
                array(
                    Result::FORMAT_JSON_EXTENDED,
                    Result::FORMAT_YAML_EXTENDED,
                    Result::FORMAT_JSONP_EXTENDED
                ),
                false
            ));
        }, Application::EARLY_EVENT);

        $app->after(function(Request $request, Response $response) {
            if ($request->getRequestFormat(Result::FORMAT_JSON) === Result::FORMAT_JSONP && !$response->isOk() && !$response->isServerError()) {
                $response->setStatusCode(200);
            }

            // set response content type
            if (!$response->headers->get('Content-Type')) {
                $response->headers->set('Content-Type', $request->getMimeType($request->getRequestFormat(Result::FORMAT_JSON)));
            }
        });

        $app->get('/api/', function (Request $request, Application $app) {
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
                    ],
                    '2' => [
                        'number'                  => V2::VERSION,
                        'uri'                     => '/api/v2/',
                        'authenticationProtocol'  => 'OAuth2',
                        'authenticationVersion'   => 'draft#v9',
                        'authenticationEndPoints' => [
                            'authorization_token' => '/api/oauthv2/authorize',
                            'access_token'        => '/api/oauthv2/token'
                        ],
                    ],
                    '3' => [
                        'number'                  => V3::VERSION,
                        'uri'                     => '/api/v3/',
                        'authenticationProtocol'  => 'OAuth2',
                        'authenticationVersion'   => 'draft#v9',
                        'authenticationEndPoints' => [
                            'authorization_token' => '/api/oauthv2/authorize',
                            'access_token'        => '/api/oauthv2/token'
                        ]
                    ],
                ]
            ])->createResponse();
        });

        // Fake routes required to send emails. Sorry.
        $routes = array('root' => '/', 'admin' => '/admin/', 'prod' => '/prod/');
        foreach ($routes as $name => $route) {
            $app->get($route, function () {
                return '';
            })->bind($name);
        }

        $app->mount('/api/oauthv2', new OAuth2());
        $app->mount('/datafiles/', new Datafiles());
        $app->mount('/api/v1', new V1());
        $app->mount('/api/v2', new V2());
        $app->mount('/api/v3', new V3());
        $app->mount('/api/report', new ApiReportControllerProvider());
        $app->mount('/permalink/', new Permalink());
        $app->mount($app['controller.media_accessor.route_prefix'], new MediaAccessor());
        $app->mount('/include/minify/', new Minifier());
        $app->bindPluginRoutes('plugin.controller_providers.api');

        $app->after(function (Request $request, Response $response) use ($app) {
            $app['dispatcher']->dispatch(PhraseaEvents::API_RESULT, new ApiResultEvent($request, $response));
        });
    }

    protected function getDispatcherSubscribersFor(Application $app)
    {
        $subscribers = parent::getDispatcherSubscribersFor($app);

        $subscribers[] = new ApiOauth2ErrorsSubscriber($app['phraseanet.exception_handler'], $app['translator']);

        return $subscribers;
    }
}
