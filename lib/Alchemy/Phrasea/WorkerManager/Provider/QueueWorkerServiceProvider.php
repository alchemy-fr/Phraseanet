<?php

/*
 * This file is part of Phraseanet graylog plugin
 *
 * (c) 2005-2019 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\WorkerManager\Provider;

use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Model\Manipulator\WebhookEventManipulator;
use Alchemy\Phrasea\Plugin\PluginProviderInterface;
use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Alchemy\Phrasea\WorkerManager\Queue\MessageHandler;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Alchemy\Phrasea\WorkerManager\Queue\WebhookPublisher;
use Alchemy\Phrasea\WorkerManager\Subscriber\AssetsIngestSubscriber;
use Alchemy\Phrasea\WorkerManager\Subscriber\ExportSubscriber;
use Alchemy\Phrasea\WorkerManager\Subscriber\ExposeSubscriber;
use Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber;
use Alchemy\Phrasea\WorkerManager\Subscriber\SearchengineSubscriber;
use Alchemy\Phrasea\WorkerManager\Subscriber\SubtitleSubscriber;
use Alchemy\Phrasea\WorkerManager\Subscriber\WebhookSubscriber;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class QueueWorkerServiceProvider implements PluginProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['alchemy_worker.amqp.connection'] = $app->share(function (Application $app) {
            return new AMQPConnection($app['conf']);
        });

        $app['alchemy_worker.message.handler'] = $app->share(function (Application $app) {
            return new MessageHandler($app['alchemy_worker.message.publisher']);
        });

        $app['alchemy_worker.message.publisher'] = $app->share(function (Application $app) {
            return new MessagePublisher($app['alchemy_worker.amqp.connection'], $app['alchemy_worker.logger']);
        });

        $app['alchemy_worker.webhook.publisher'] = $app->share(function (Application $app) {
            return new WebhookPublisher($app['alchemy_worker.message.publisher']);
        });

        $app['manipulator.webhook-event'] = $app->share(function (Application $app) {
            return new WebhookEventManipulator(
                $app['orm.em'],
                $app['repo.webhook-event'],
                $app['alchemy_worker.webhook.publisher']
            );
        });

        $app['dispatcher'] = $app->share(
            $app->extend('dispatcher', function (EventDispatcherInterface $dispatcher, Application $app) {

                $dispatcher->addSubscriber(
                    new RecordSubscriber($app, new LazyLocator($app, 'phraseanet.appbox'))
                );
                $dispatcher->addSubscriber(new ExportSubscriber($app['alchemy_worker.message.publisher']));
                $dispatcher->addSubscriber(new AssetsIngestSubscriber($app['alchemy_worker.message.publisher'], new LazyLocator($app, 'repo.worker-running-job')));
                $dispatcher->addSubscriber(new SearchengineSubscriber($app['alchemy_worker.message.publisher'], new LazyLocator($app, 'repo.worker-running-job')));
                $dispatcher->addSubscriber(new WebhookSubscriber($app['alchemy_worker.message.publisher']));
                $dispatcher->addSubscriber(new SubtitleSubscriber(new LazyLocator($app, 'repo.worker-job'), $app['alchemy_worker.message.publisher']));
                $dispatcher->addSubscriber(new ExposeSubscriber($app['alchemy_worker.message.publisher']));

                return $dispatcher;
            })
        );

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
