<?php

namespace Alchemy\Phrasea\WorkerManager\Queue;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Webhook\WebhookPublisherInterface;

class WebhookPublisher implements WebhookPublisherInterface
{
    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    public function __construct(MessagePublisher $messagePublisher)
    {
        $this->messagePublisher = $messagePublisher;
    }

    public function publishWebhookEvent(WebhookEvent $event)
    {
        $payload = [
            'message_type' => MessagePublisher::WEBHOOK_TYPE,
            'payload' => [
                'id'    => $event->getId()
            ]
        ];

        $this->messagePublisher->publishMessage($payload, MessagePublisher::WEBHOOK_TYPE);
    }
}
