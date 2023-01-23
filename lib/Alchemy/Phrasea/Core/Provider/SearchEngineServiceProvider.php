<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\SearchEngine\Elastic\DataboxFetcherFactory;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchEngine;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\SearchEngine\Elastic\Index;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordIndexer;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\TermIndexer;
use Alchemy\Phrasea\SearchEngine\Elastic\IndexerSubscriber;
use Alchemy\Phrasea\SearchEngine\Elastic\IndexLocator;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\Escaper;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\FacetsResponse;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryCompiler;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContextFactory;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryVisitor;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;
use Alchemy\Phrasea\SearchEngine\SearchEngineLogger;
use Alchemy\Phrasea\SearchEngine\SearchEngineStructure;
use Elasticsearch\ClientBuilder;
use Hoa\Compiler;
use Hoa\File;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;

// use Alchemy\Phrasea\Utilities\Stopwatch;


class SearchEngineServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        /** @var PhraseaApplication $app */
        $this->registerElasticSearchClient($app);
        $this->registerQueryParser($app);
        $this->registerIndexer($app);
        $this->registerSearchEngine($app);
    }

    public function boot(Application $app)
    {
    }

    /**
     * @param  PhraseaApplication $app
     * @return Application
     */
    private function registerSearchEngine(PhraseaApplication $app)
    {
        $app['phraseanet.SE'] = function ($app) {
            return $app['search_engine'];
        };

        $app['phraseanet.SE.logger'] = $app->share(function (PhraseaApplication $app) {
            return new SearchEngineLogger($app);
        });

        $app['search_engine'] = $app->share(function ($app) {
//            $stopwatch = new Stopwatch("se");

//            $stopwatch->lap("se.options");
//            $r = new ElasticSearchEngine(
//                $app,
//                $app['search_engine.global_structure'],
//                $app['elasticsearch.client'],
//                $app['query_context.factory'],
//                $app['elasticsearch.facets_response.factory'],
//                $app['elasticsearch.options']
//            );
//            $stopwatch->lap("se.new");
//            $stopwatch->log();
//            return $r;

            return new ElasticSearchEngine(
                $app,
                $app['search_engine.global_structure'],
                $app['elasticsearch.client'],
                $app['query_context.factory'],
                $app['elasticsearch.facets_response.factory'],
                $app['elasticsearch.options'],
                $app['translator']
            );
        });

        $app['search_engine.structure'] = $app->share(function (PhraseaApplication $app) {
            return new SearchEngineStructure($app['cache']);
        });

        $app['search_engine.global_structure'] = $app->share(function (PhraseaApplication $app) {
//            $stopwatch = new Stopwatch("se.global_structure");
            /** @var SearchEngineStructure $s */
            $s = $app['search_engine.structure'];

//            $globalStructure = $s->getGlobalStructureFromDataboxes($app->getDataboxes());
//            $stopwatch->log();
//            return $globalStructure;

            return $s->getGlobalStructureFromDataboxes($app->getDataboxes());
        });

       $app['elasticsearch.facets_response.factory'] = $app->protect(function (array $response) use ($app) {
            return new FacetsResponse($app['elasticsearch.options'], new Escaper(), $app['translator'], $response, $app['search_engine.global_structure']);
        });

        return $app;
    }

    /**
     * @param Application $app
     * @return Application
     */
    private function registerIndexer(Application $app)
    {
        /* Indexer related services */
        $app['elasticsearch.index'] = $app->share(function ($app) {
            return new Index($app['elasticsearch.options'], $app['elasticsearch.index.locator']);
        });

        $app['elasticsearch.index.record'] = $app->share(function ($app) {
            return new Indexer\RecordIndex($app['search_engine.global_structure'], array_keys($app['locales.available']));
        });

        $app['elasticsearch.index.term'] = $app->share(function ($app) {
            return new Indexer\TermIndex(array_keys($app['locales.available']));
        });

        $app['elasticsearch.index.locator'] = $app->share(function ($app) {
            return new IndexLocator($app, 'elasticsearch.index.record', 'elasticsearch.index.term');
        });

        $app['elasticsearch.indexer'] = $app->share(function ($app) {
            return new Indexer(
                $app['elasticsearch.client'],
                $app['elasticsearch.index'],
                $app['elasticsearch.indexer.term_indexer'],
                $app['elasticsearch.indexer.record_indexer'],
                $app['monolog']
            );
        });

        $app['elasticsearch.indexer.term_indexer'] = $app->share(function ($app) {
            return new TermIndexer(
                $app['phraseanet.appbox'],
                $app['monolog']
            );
        });

        $app['elasticsearch.indexer.databox_fetcher_factory'] = $app->share(function ($app) {
            return new DataboxFetcherFactory(
                $app['conf'],
                $app['elasticsearch.record_helper'],
                $app['elasticsearch.options'],
                $app,
                'search_engine.global_structure',
                'thesaurus'
            );
        });

        $app['elasticsearch.indexer.record_indexer'] = $app->share(function ($app) {
            // TODO Use upcoming monolog factory
            $logger = new Logger('indexer');
            $logger->pushHandler(new ErrorLogHandler());

            return new RecordIndexer(
                $app['elasticsearch.indexer.databox_fetcher_factory'],
                $app['elasticsearch.record_helper'],
                $app['dispatcher'],
                $app['monolog']
            );
        });

        $app['elasticsearch.record_helper'] = $app->share(function ($app) {
            return new RecordHelper($app['phraseanet.appbox']);
        });

        $app['dispatcher'] = $app
            ->share($app->extend('dispatcher', function (EventDispatcherInterface $dispatcher, $app) {
                $subscriber = new IndexerSubscriber(new LazyLocator($app, 'elasticsearch.indexer'));

                $dispatcher->addSubscriber($subscriber);

                $listener = array($subscriber, 'flushQueue');

                // Add synchronous flush when used in CLI.
                if (isset($app['console'])) {
                    foreach (array_keys($subscriber->getSubscribedEvents()) as $eventName) {
                        $dispatcher->addListener($eventName, $listener, -10);
                    }

                    return $dispatcher;
                }

                $dispatcher->addListener(KernelEvents::TERMINATE, $listener);

                return $dispatcher;
            }));

        return $app;
    }

    /**
     * @param Application $app
     * @return Application
     */
    private function registerElasticSearchClient(Application $app)
    {
        /* Low-level elasticsearch services */
        $app['elasticsearch.client'] = $app->share(function ($app) {
            /** @var ElasticsearchOptions $options */
            $options = $app['elasticsearch.options'];
            $clientParams = ['hosts' => [sprintf('%s:%s', $options->getHost(), $options->getPort())]];

            // Create file logger for debug
            if ($app['debug']) {
                /** @var Logger $logger */
                $logger = new $app['monolog.logger.class']('search logger');
                $logger->pushHandler(new RotatingFileHandler($app['log.path'] . DIRECTORY_SEPARATOR . 'elasticsearch.log',
                    2, Logger::INFO));

                $clientParams['logObject'] = $logger;
                $clientParams['logging'] = true;
            }

            $clientBuilder = ClientBuilder::create()
                ->setHosts($clientParams['hosts']);
            if(array_key_exists('logObject', $clientParams)) {
                $clientBuilder->setLogger($clientParams['logObject']);
            }

            return $clientBuilder->build();
        });

        $app['elasticsearch.options'] = $app->share(function ($app) {
            $conf = $app['conf']->get(['main', 'search-engine', 'options'], []);
            $options = ElasticsearchOptions::fromArray($conf);

            if (empty($options->getIndexName())) {
                $options->setIndexName(strtolower(sprintf('phraseanet_%s', str_replace(
                    array('/', '.'), array('', ''),
                    $app['conf']->get(['main', 'key'])
                ))));
            }

            return $options;
        });

        return $app;
    }

    /**
     * @param Application $app
     */
    private function registerQueryParser(Application $app)
    {
        /* Querying helper services */
        $app['thesaurus'] = $app->share(function ($app) {
            $logger = new Logger('thesaurus');
            $logger->pushHandler(new ErrorLogHandler(
                ErrorLogHandler::OPERATING_SYSTEM,
                $app['debug'] ? Logger::DEBUG : Logger::ERROR
            ));

            return new Thesaurus(
                $app['elasticsearch.client'],
                $app['elasticsearch.options'],
                $logger
            );
        });

        $app['query_context.factory'] = $app->share(function ($app) {
            return new QueryContextFactory(
                $app['search_engine.global_structure'],
                array_keys($app['locales.available']),
                $app['locale']
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

        $app['query_visitor.factory'] = $app->protect(function () use ($app) {
            return new QueryVisitor($app['search_engine.global_structure']);
        });

        $app['query_compiler'] = $app->share(function ($app) {
            return new QueryCompiler(
                $app['query_parser'],
                $app['query_visitor.factory'],
                $app['thesaurus']
            );
        });
    }
}
