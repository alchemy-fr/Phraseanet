<?php

namespace Alchemy\Phrasea\Webhook;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Webhook\Processor\FeedEntryProcessor;
use Alchemy\Phrasea\Webhook\Processor\UserRegistrationProcessor;

class EventProcessorFactory
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function get(WebhookEvent $event)
    {
        switch ($event->getType()) {
            case WebhookEvent::FEED_ENTRY_TYPE:
                return new FeedEntryProcessor($this->app);
            case WebhookEvent::USER_REGISTRATION_TYPE:
                return new UserRegistrationProcessor($this->app, $this->app['repo.users']);
            default:
                throw new \RuntimeException(sprintf('No processor found for %s', $event->getType()));
        }
    }
}
