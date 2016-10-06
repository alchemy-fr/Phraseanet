<?php

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Webhook\EventProcessorFactory;
use Alchemy\Phrasea\Webhook\EventProcessorWorker;
use Alchemy\Phrasea\Webhook\WebhookInvoker;
use Alchemy\Phrasea\Webhook\WebhookPublisher;
use Alchemy\Worker\CallableWorkerFactory;
use Alchemy\Worker\TypeBasedWorkerResolver;
use Silex\Application;
use Silex\ServiceProviderInterface;

class WebhookServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $this->createAlias($app, 'webhook.event_repository', 'repo.webhook-event');
        $this->createAlias($app, 'webhook.event_manipulator', 'manipulator.webhook-event');
        $this->createAlias($app, 'webhook.delivery_repository', 'repo.webhook-delivery');
        $this->createAlias($app, 'webhook.delivery_manipulator', 'manipulator.webhook-delivery');

        $app['webhook.processor_factory'] = $app->share(function ($app) {
            return new EventProcessorFactory($app);
        });

        $app['webhook.invoker'] = $app->share(function ($app) {
            return new WebhookInvoker(
                $app['repo.api-applications'],
                $app['webhook.processor_factory'],
                $app['webhook.event_repository'],
                $app['webhook.event_manipulator'],
                $app['webhook.delivery_repository'],
                $app['webhook.delivery_manipulator']
            );
        });

        $app['webhook.publisher'] = $app->share(function ($app) {
            return new WebhookPublisher($app['alchemy_worker.queue_registry'], $app['alchemy_worker.queue_name']);
        });

        $app['alchemy_worker.worker_resolver'] = $app->extend(
            'alchemy_worker.type_based_worker_resolver',
            function (TypeBasedWorkerResolver $resolver, Application $app) {
                $resolver->setFactory('webhook', new CallableWorkerFactory(function () use ($app) {
                    return new EventProcessorWorker(
                        $app['webhook.event_repository'],
                        $app['webhook.invoker']
                    );
                }));

                return $resolver;
            }
        );
    }

    private function createAlias(Application $app, $alias, $targetServiceKey)
    {
        $app[$alias] = $app->share(function () use ($app, $targetServiceKey) {
            return $app[$targetServiceKey];
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }
}
