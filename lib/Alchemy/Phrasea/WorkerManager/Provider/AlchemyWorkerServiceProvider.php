<?php

namespace Alchemy\Phrasea\WorkerManager\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Plugin\PluginProviderInterface;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Alchemy\Phrasea\WorkerManager\Worker\AssetsIngestWorker;
use Alchemy\Phrasea\WorkerManager\Worker\CreateRecordWorker;
use Alchemy\Phrasea\WorkerManager\Worker\DeleteRecordWorker;
use Alchemy\Phrasea\WorkerManager\Worker\ExportMailWorker;
use Alchemy\Phrasea\WorkerManager\Worker\ExposeUploadWorker;
use Alchemy\Phrasea\WorkerManager\Worker\Factory\CallableWorkerFactory;
use Alchemy\Phrasea\WorkerManager\Worker\FtpWorker;
use Alchemy\Phrasea\WorkerManager\Worker\MainQueueWorker;
use Alchemy\Phrasea\WorkerManager\Worker\PopulateIndexWorker;
use Alchemy\Phrasea\WorkerManager\Worker\ProcessPool;
use Alchemy\Phrasea\WorkerManager\Worker\PullAssetsWorker;
use Alchemy\Phrasea\WorkerManager\Worker\EditRecordWorker;
use Alchemy\Phrasea\WorkerManager\Worker\RecordsActionsWorker;
use Alchemy\Phrasea\WorkerManager\Worker\Resolver\TypeBasedWorkerResolver;
use Alchemy\Phrasea\WorkerManager\Worker\ShareBasketWorker;
use Alchemy\Phrasea\WorkerManager\Worker\SubdefCreationWorker;
use Alchemy\Phrasea\WorkerManager\Worker\SubtitleWorker;
use Alchemy\Phrasea\WorkerManager\Worker\ValidationReminderWorker;
use Alchemy\Phrasea\WorkerManager\Worker\WebhookWorker;
use Alchemy\Phrasea\WorkerManager\Worker\WorkerInvoker;
use Alchemy\Phrasea\WorkerManager\Worker\WriteMetadatasWorker;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerAwareInterface;
use Silex\Application;

class AlchemyWorkerServiceProvider implements PluginProviderInterface
{
    public function register(Application $app)
    {
        $app['alchemy_worker.type_based_worker_resolver'] = $app->share(function () {
            return new TypeBasedWorkerResolver();
        });

        $app['alchemy_worker.logger'] = $app->share(function (Application $app) {
            $logger = new $app['monolog.logger.class']('alchemy-service logger');
//            $logger->pushHandler(new RotatingFileHandler(
//                $app['log.path'] . DIRECTORY_SEPARATOR . 'worker_service.log',
//                10,
//                Logger::INFO
//            ));

            $logger->pushHandler(new StreamHandler(
                $app['log.path'] . DIRECTORY_SEPARATOR . 'worker_service.log',
                Logger::INFO
            ));

            return $logger;
        });

        // use the console logger
        $loggerSetter = function (LoggerAwareInterface $loggerAware) use ($app) {
            if (isset($app['logger'])) {
                $loggerAware->setLogger($app['logger']);
            }

            return $loggerAware;
        };

        $app['alchemy_worker.process_pool'] = $app->share(function (Application $app) use ($loggerSetter) {
            return $loggerSetter(new ProcessPool());
        });

        $app['alchemy_worker.worker_invoker'] = $app->share(function (Application $app) use ($loggerSetter) {
            return $loggerSetter(new WorkerInvoker($app['alchemy_worker.process_pool']));
        });


        // register workers
        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::SUBDEF_CREATION_TYPE, new CallableWorkerFactory(function () use ($app) {
            return (new SubdefCreationWorker(
                $app['subdef.generator'],
                $app['alchemy_worker.message.publisher'],
                $app['alchemy_worker.logger'],
                $app['dispatcher'],
                $app['phraseanet.filesystem'],
                $app['repo.worker-running-job'],
                $app['elasticsearch.indexer']
            ))
                ->setApplicationBox($app['phraseanet.appbox'])
                ;
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::WRITE_METADATAS_TYPE, new CallableWorkerFactory(function () use ($app) {
            return (new WriteMetadatasWorker(
                $app['exiftool.writer'],
                $app['alchemy_worker.logger'],
                $app['alchemy_worker.message.publisher'],
                $app['repo.worker-running-job']
            ))
                ->setApplicationBox($app['phraseanet.appbox'])
                ->setDispatcher($app['dispatcher'])
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'))
                ;
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::EXPORT_MAIL_TYPE, new CallableWorkerFactory(function () use ($app) {
            return (new ExportMailWorker($app))
                ->setDelivererLocator(new LazyLocator($app, 'notification.deliverer'));
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::ASSETS_INGEST_TYPE, new CallableWorkerFactory(function () use ($app) {
            return (new AssetsIngestWorker($app))
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'));
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::WEBHOOK_TYPE, new CallableWorkerFactory(function () use ($app) {
            return (new WebhookWorker($app))
                ->setApplicationBox($app['phraseanet.appbox'])
                ->setDispatcher($app['dispatcher']);
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::CREATE_RECORD_TYPE, new CallableWorkerFactory(function () use ($app) {
            return (new CreateRecordWorker($app))
                ->setApplicationBox($app['phraseanet.appbox'])
                ->setBorderManagerLocator(new LazyLocator($app, 'border-manager'))
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'))
                ->setFileSystemLocator(new LazyLocator($app, 'filesystem'))
                ->setTemporaryFileSystemLocator(new LazyLocator($app, 'temporary-filesystem'))
                ->setDispatcher($app['dispatcher']);
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::POPULATE_INDEX_TYPE, new CallableWorkerFactory(function () use ($app) {
            return (new PopulateIndexWorker($app['alchemy_worker.message.publisher'], $app['elasticsearch.indexer'], $app['repo.worker-running-job']))
                ->setApplicationBox($app['phraseanet.appbox'])
                ->setDispatcher($app['dispatcher']);
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::PULL_ASSETS_TYPE, new CallableWorkerFactory(function () use ($app) {
            return new PullAssetsWorker($app['alchemy_worker.message.publisher'], $app['conf'], $app['repo.worker-running-job']);
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::DELETE_RECORD_TYPE, new CallableWorkerFactory(function () use ($app) {
            return (new DeleteRecordWorker($app['repo.worker-running-job'], $app['alchemy_worker.message.publisher']))
                ->setApplicationBox($app['phraseanet.appbox']);
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::EXPOSE_UPLOAD_TYPE, new CallableWorkerFactory(function () use ($app) {
            return (new ExposeUploadWorker($app))
                ->setApplicationBox($app['phraseanet.appbox']);
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::SUBTITLE_TYPE, new CallableWorkerFactory(function () use ($app) {
            return (new SubtitleWorker($app['repo.worker-running-job'], $app['conf'], new LazyLocator($app, 'phraseanet.appbox'), $app['alchemy_worker.logger'], $app['dispatcher']))
                ->setFileSystemLocator(new LazyLocator($app, 'filesystem'))
                ->setTemporaryFileSystemLocator(new LazyLocator($app, 'temporary-filesystem'));
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::MAIN_QUEUE_TYPE, new CallableWorkerFactory(function () use ($app) {
            return new MainQueueWorker($app['alchemy_worker.message.publisher'], $app['repo.worker-job']);
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::FTP_TYPE, new CallableWorkerFactory(function () use ($app) {
            return new FtpWorker($app);
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::VALIDATION_REMINDER_TYPE, new CallableWorkerFactory(function () use ($app) {
            return new ValidationReminderWorker($app);
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::RECORDS_ACTIONS_TYPE, new CallableWorkerFactory(function () use ($app) {
            return new RecordsActionsWorker($app);
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::EDIT_RECORD_TYPE, new CallableWorkerFactory(function () use ($app) {
            return (new EditRecordWorker($app['repo.worker-running-job'], $app['dispatcher'], $app['alchemy_worker.message.publisher']))
                   ->setApplicationBox($app['phraseanet.appbox'])
                ;
        }));

        $app['alchemy_worker.type_based_worker_resolver']->addFactory(MessagePublisher::SHARE_BASKET_TYPE, new CallableWorkerFactory(function () use ($app) {
            return new ShareBasketWorker($app);
        }));
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(PhraseaApplication $app)
    {
        return new static();
    }

}
