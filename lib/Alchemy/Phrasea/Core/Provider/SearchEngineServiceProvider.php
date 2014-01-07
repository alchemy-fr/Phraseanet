<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\SearchEngine\SearchEngineLogger;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Elasticsearch\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SearchEngineServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['phraseanet.SE'] = $app->share(function ($app) {
            $engineOptions = $app['conf']->get(['main', 'search-engine', 'options']);

            return $app['phraseanet.SE.engine-class']::create($app, $engineOptions);
        });

        $app['phraseanet.SE.logger'] = $app->share(function (Application $app) {
            return new SearchEngineLogger($app);
        });

        $app['phraseanet.SE.engine-class'] = $app->share(function ($app) {
            $engineClass = $app['conf']->get(['main', 'search-engine', 'type']);

            if (!class_exists($engineClass) || $engineClass instanceof SearchEngineInterface) {
                throw new InvalidArgumentException(sprintf('%s is not valid SearchEngineInterface', $engineClass));
            }

            return $engineClass;
        });

        $app['phraseanet.SE.subscriber'] = $app->share(function ($app) {
            return $app['phraseanet.SE.engine-class']::createSubscriber($app);
        });

        $app['ES.indexer'] = $app->share(function ($app) {
            return new Indexer($app['phraseanet.SE'], $app['monolog'], $app['phraseanet.appbox']);
        });
    }

    public function boot(Application $app)
    {
        if ($app['phraseanet.configuration']->isSetup()) {
            $app['dispatcher'] = $app->share(
                $app->extend('dispatcher', function ($dispatcher, Application $app) {
                    $dispatcher->addSubscriber($app['phraseanet.SE.subscriber']);

                    return $dispatcher;
                })
            );
        }
    }
}
