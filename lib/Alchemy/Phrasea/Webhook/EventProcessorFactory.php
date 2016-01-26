<?php

namespace Alchemy\Phrasea\Webhook;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Webhook\Processor\CallableProcessorFactory;
use Alchemy\Phrasea\Webhook\Processor\FeedEntryProcessorFactory;
use Alchemy\Phrasea\Webhook\Processor\ProcessorFactory;
use Alchemy\Phrasea\Webhook\Processor\UserRegistrationProcessorFactory;

class EventProcessorFactory
{

    /**
     * @var ProcessorFactory
     */
    private $processorFactories = [];

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->registerFactory(WebhookEvent::FEED_ENTRY_TYPE, new FeedEntryProcessorFactory($app));
        $this->registerFactory(WebhookEvent::USER_REGISTRATION_TYPE, new UserRegistrationProcessorFactory($app));
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
     * @param callback|callable $callable
     */
    public function registerCallableFactory($eventType, $callable)
    {
        if (! is_callable($callable)) {
            throw new InvalidArgumentException('Expected a callable, got instead: ' . gettype($callable));
        }

        $this->processorFactories[$eventType] = new CallableProcessorFactory($callable);
    }

    /**
     * @param WebhookEvent $event
     * @return Processor\ProcessorInterface
     */
    public function get(WebhookEvent $event)
    {
        if (! isset($this->processorFactories[$event->getType()])) {
            throw new \RuntimeException(sprintf('No processor found for %s', $event->getType()));
        }

        $factory = $this->processorFactories[$event->getType()];

        return $factory->createProcessor();
    }
}
