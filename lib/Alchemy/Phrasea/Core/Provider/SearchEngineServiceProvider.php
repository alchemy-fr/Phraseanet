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
use Alchemy\Phrasea\SearchEngine\Elastic\Search\Escaper;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\FacetsResponse;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryCompiler;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;
use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngineSubscriber;
use Elasticsearch\Client;
use Hoa\Compiler;
use Hoa\File;
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

        $app['phraseanet.SE.logger'] = $app->share(function (Application $app) {
            return new SearchEngineLogger($app);
        });

        $app['search_engine'] = $app->share(function ($app) {
            $type = $app['conf']->get(['main', 'search-engine', 'type']);
            if ($type !== SearchEngineInterface::TYPE_ELASTICSEARCH) {
                    throw new InvalidArgumentException(sprintf('Invalid search engine type "%s".', $type));
            }
            return new ElasticSearchEngine(
                $app,
                $app['search_engine.structure'],
                $app['elasticsearch.client'],
                $app['elasticsearch.options']['index'],
                $app['locales.available'],
                $app['elasticsearch.record_helper'],
                $app['elasticsearch.facets_response.factory']
            );
        });

        $app['search_engine.structure'] = $app->share(function ($app) {
            $databoxes = $app['phraseanet.appbox']->get_databoxes();
            return Structure::fromDataboxes($databoxes);
        });

        $app['elasticsearch.facets_response.factory'] = $app->protect(function (array $response) use ($app) {
            return new FacetsResponse(new Escaper(), $response);
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
            // TODO Use upcomming monolog factory
            $logger = new \Monolog\Logger('indexer');
            $logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());
            return new RecordIndexer(
                $app['search_engine.structure'],
                $app['elasticsearch.record_helper'],
                $app['thesaurus'],
                $app['phraseanet.appbox'],
                array_keys($app['locales.available']),
                $logger
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
            // TODO Use upcomming monolog factory
            $logger = new \Monolog\Logger('thesaurus');
            $logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());
            return new Thesaurus(
                $app['elasticsearch.client'],
                $app['elasticsearch.options']['index'],
                $logger
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
            return Compiler\Llk\Llk::load(new File\Read($grammarPath));
        });

        $app['query_compiler'] = $app->share(function ($app) {
            return new QueryCompiler(
                $app['query_parser'],
                $app['thesaurus']
            );
        });
    }

    public function boot(Application $app)
    {
    }
}
