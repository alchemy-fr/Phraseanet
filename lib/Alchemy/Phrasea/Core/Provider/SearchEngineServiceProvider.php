<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
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
use Alchemy\Phrasea\SearchEngine\Elastic\IndexerSubscriber;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordIndexer;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\TermIndexer;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryParser;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;
use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine;
use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngineSubscriber;
use Elasticsearch\Client;
use Hoa\Compiler\Llk\Llk;
use Hoa\File\Read;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
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
                    return $app['elasticsearch.engine'];
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

        $app['elasticsearch.engine'] = $app->share(function ($app) {
            return new ElasticSearchEngine(
                $app,
                $app['elasticsearch.client'],
                $app['elasticsearch.options']['index']
            );
        });


        /* Indexer related services */

        $app['elasticsearch.indexer'] = $app->share(function ($app) {
            return new Indexer(
                $app['elasticsearch.client'],
                $app['elasticsearch.options'],
                $app['elasticsearch.indexer.term_indexer'],
                $app['elasticsearch.indexer.record_indexer'],
                $app['phraseanet.appbox']
            );
        });

        $app['elasticsearch.indexer.term_indexer'] = $app->share(function ($app) {
            return new TermIndexer($app['phraseanet.appbox'], array_keys($app['locales.available']));
        });

        $app['elasticsearch.indexer.record_indexer'] = $app->share(function ($app) {
            return new RecordIndexer(
                $app['elasticsearch.record_helper'],
                $app['thesaurus'],
                $app['elasticsearch.engine'],
                $app['phraseanet.appbox'],
                array_keys($app['locales.available'])
            );
        });

        $app['elasticsearch.record_helper'] = $app->share(function ($app) {
            return new RecordHelper($app['phraseanet.appbox']);
        });

        $app['elasticsearch.indexer_subscriber'] = $app->share(function ($app) {
            return new IndexerSubscriber($app['elasticsearch.indexer']);
        });


        $app['dispatcher'] = $app->share($app->extend('dispatcher', function ($dispatcher, $app) {
            $dispatcher->addSubscriber($app['elasticsearch.indexer_subscriber']);

            return $dispatcher;
        }));

        /* Low-level elasticsearch services */

        $app['elasticsearch.client'] = $app->share(function($app) {
            $options        = $app['elasticsearch.options'];
            $clientParams   = ['hosts' => [sprintf('%s:%s', $options['host'], $options['port'])]];

            // Create file logger for debug
            if ($app['debug']) {
                $logger = new $app['monolog.logger.class']('search logger');
                $logger->pushHandler(new RotatingFileHandler($app['log.path'].DIRECTORY_SEPARATOR.'elasticsearch.log', 2), Logger::INFO);

                $clientParams['logObject'] = $logger;
                $clientParams['logging'] = true;
            }

            return new Client($clientParams);
        });

        $app['elasticsearch.options'] = $app->share(function($app) {
            $options = $app['conf']->get(['main', 'search-engine', 'options'], []);

            $indexName = sprintf('phraseanet_%s', str_replace(
                array('/', '.'), array('', ''),
                $app['conf']->get(['main', 'key'])
            ));

            $defaults = [
                'host'     => '127.0.0.1',
                'port'     => 9200,
                'index'    => strtolower($indexName),
                'shards'   => 3,
                'replicas' => 0
            ];

            return array_replace($defaults, $options);
        });


        /* Querying helper services */

        $app['thesaurus'] = $app->share(function ($app) {
            return new Thesaurus(
                $app['elasticsearch.client'],
                $app['elasticsearch.options']['index']
            );
        });

        $app['query_parser.grammar_path'] = function ($app) {
            $configPath = ['registry', 'searchengine', 'query-grammar-path'];
            $grammarPath = $app['conf']->get($configPath, 'grammar/query.pp');
            $projectRoot = '../../../../..';

            return realpath(implode('/', [__DIR__, $projectRoot, $grammarPath]));
        };

        $app['query_parser'] = $app->share(function ($app) {
            $grammarPath = $app['query_parser.grammar_path'];
            $parser = Llk::load(new Read($grammarPath));

            return new QueryParser($parser, $app['thesaurus']);
        });
    }

    public function boot(Application $app)
    {
        if ($app['search_engine.type'] === SearchEngineInterface::TYPE_PHRASEA) {
            $app['dispatcher'] = $app->share($app->extend('dispatcher', function ($dispatcher, Application $app) {
                $dispatcher->addSubscriber($app['phraseanet.SE.subscriber']);

                return $dispatcher;
            }));
        }
    }
}
