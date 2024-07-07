<?php

namespace Alchemy\Phrasea\Webhook;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Webhook\Processor\CallableProcessorFactory;
use Alchemy\Phrasea\Webhook\Processor\FeedEntryProcessorFactory;
use Alchemy\Phrasea\Webhook\Processor\OrderNotificationProcessorFactory;
use Alchemy\Phrasea\Webhook\Processor\ProcessorFactory;
use Alchemy\Phrasea\Webhook\Processor\ProcessorInterface;
use Alchemy\Phrasea\Webhook\Processor\RecordEventProcessor;
use Alchemy\Phrasea\Webhook\Processor\UserDeletedProcessorFactory;
use Alchemy\Phrasea\Webhook\Processor\UserProcessorFactory;
use Alchemy\Phrasea\Webhook\Processor\UserRegistrationProcessorFactory;
use Alchemy\Phrasea\Webhook\Processor\SubdefEventProcessor;

class EventProcessorFactory
{

    /**
     * @var ProcessorFactory[]
     */
    private $processorFactories = [];

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->registerFactory(WebhookEvent::FEED_ENTRY_TYPE, new FeedEntryProcessorFactory($app));
        $this->registerFactory(WebhookEvent::USER_REGISTRATION_TYPE, new UserRegistrationProcessorFactory($app));
        $this->registerFactory(WebhookEvent::ORDER_TYPE, new OrderNotificationProcessorFactory($app));

        $this->registerFactory(WebhookEvent::USER_TYPE, new UserProcessorFactory());
        $this->registerCallableFactory(WebhookEvent::RECORD_SUBDEF_TYPE, function ()  use ($app) {
            return (new SubdefEventProcessor())
                ->setApplicationBox($app['phraseanet.appbox']);
        });
        $this->registerCallableFactory(WebhookEvent::RECORD_TYPE, function () {
            return new RecordEventProcessor();
        });
    }

    /**
     * @param string $eventType
     * @param ProcessorFactory $processorFactory
     */
    public function registerFactory($eventType, ProcessorFactory $processorFactory)
    {
        $this->processorFactories[$eventType] = $processorFactory;
    }

    /**
     * @param string $eventType
     * @param callback|callable|\Closure $callable
     */
    public function registerCallableFactory($eventType, $callable)
    {
        if (! is_callable($callable)) {
            throw new InvalidArgumentException(sprintf(
                'Expected a callable, got "%s" instead',
                is_object($callable) ? get_class($callable) : gettype($callable)
            ));
        }

        $this->processorFactories[$eventType] = new CallableProcessorFactory($callable);
    }

    /**
     * @param WebhookEvent $event
     * @return Processor\ProcessorInterface
     * @deprecated Use getProcessor() instead
     */
    public function get(WebhookEvent $event)
    {
        return $this->getProcessor($event);
    }

    /**
     * @param WebhookEvent $event
     * @return ProcessorInterface
     */
    public function getProcessor(WebhookEvent $event)
    {
        if (!isset($this->processorFactories[$event->getType()])) {
            throw new \RuntimeException(sprintf('No processor found for %s', $event->getType()));
        }

        $factory = $this->processorFactories[$event->getType()];

        return $factory->createProcessor();
    }
}
