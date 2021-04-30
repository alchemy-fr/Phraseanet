<?php

namespace Alchemy\Phrasea\WorkerManager\Subscriber;

use Alchemy\Phrasea\WorkerManager\Event\WebhookDeliverFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WebhookSubscriber implements EventSubscriberInterface
{
    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    public function __construct(MessagePublisher $messagePublisher)
    {
        $this->messagePublisher = $messagePublisher;
    }

    public function onWebhookDeliverFailure(WebhookDeliverFailureEvent $event)
    {
        // count = 0  mean do not retry because no api application defined
        if ($event->getCount() != 0) {
            $payload = [
                'message_type' => MessagePublisher::WEBHOOK_TYPE,
                'payload' => [
                    'id'            => $event->getWebhookEventId(),
                    'delivery_id'   => $event->getDeleveryId(),
                ]
            ];

            $this->messagePublisher->publishRetryMessage(
                $payload,
                MessagePublisher::WEBHOOK_TYPE,
                $event->getCount(),
                $event->getWorkerMessage()
            );
        }

    }

    public static function getSubscribedEvents()
    {
        return [
            WorkerEvents::WEBHOOK_DELIVER_FAILURE => 'onWebhookDeliverFailure',
        ];
    }
}
