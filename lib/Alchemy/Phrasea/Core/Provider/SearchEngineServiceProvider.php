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
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchEngine;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine;
use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngineSubscriber;
use Elasticsearch\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SearchEngineServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['phraseanet.SE'] = function ($app) {
            return $app['search_engine'];
        };

        $app['search_engine'] = $app->share(function ($app) {
            $type = $app['search_engine.type'];
            switch ($type) {
                case SearchEngineInterface::TYPE_ELASTICSEARCH:
                    return new ElasticSearchEngine(
                        $app,
                        $app['elasticsearch.client'],
                        $app['serializer.es-record'],
                        $app['elasticsearch.options']['index']
                    );
                case SearchEngineInterface::TYPE_PHRASEA:
                    return new PhraseaEngine($app);
                default:
                    throw new InvalidArgumentException(sprintf('Invalid search engine type "%s".', $type));
            }
        });

        $app['search_engine.type'] = function ($app) {
            return $app['conf']->get(['main', 'search-engine', 'type']);
        };

        $app['phraseanet.SE.logger'] = $app->share(function (Application $app) {
            return new SearchEngineLogger($app);
        });

        // Only used for Phrasea search engine
        $app['phraseanet.SE.subscriber'] = $app->share(function ($app) {
            return new PhraseaEngineSubscriber($app);
        });

        $app['elasticsearch.indexer'] = $app->share(function ($app) {
            return new Indexer(
                $app['phraseanet.SE'],
                $app['elasticsearch.options'],
                $app['monolog'],
                $app['phraseanet.appbox']
            );
        });

        $app['elasticsearch.client'] = $app->share(function($app) {
            $options = $app['elasticsearch.options'];
            $host = sprintf('%s:%s', $options['host'], $options['port']);

            // TODO (mdarse) Add logging support

            return new Client(array('hosts' => array($host)));
        });

        $app['elasticsearch.options'] = $app->share(function($app) {
            $options = $app['conf']->get(['main', 'search-engine', 'options']);
            $defaults = [
                'host'     => '127.0.0.1',
                'port'     => 9200,
                'index'    => 'phraseanet',
                'shards'   => 3,
                'replicas' => 0
            ];

            return array_replace($defaults, $options);
        });
    }

    public function boot(Application $app)
    {
        if (!$app['phraseanet.configuration']->isSetup()) {
            return;
        }

        if ($app['search_engine.type'] === SearchEngineInterface::TYPE_PHRASEA) {
            $app['dispatcher'] = $app->share($app->extend('dispatcher', function ($dispatcher, Application $app) {
                $dispatcher->addSubscriber($app['phraseanet.SE.subscriber']);

                return $dispatcher;
            }));
        }
    }
}
