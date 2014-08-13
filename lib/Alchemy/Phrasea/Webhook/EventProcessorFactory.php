<?php

namespace Alchemy\Phrasea\Webhook;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Webhook\Processor\FeedEntryProcessor;

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
                return new FeedEntryProcessor($event, $this->app);
            break;
            default:
                throw new \RuntimeException(sprintf('No processor found for %s', $event->getType()));
        }
    }
}
