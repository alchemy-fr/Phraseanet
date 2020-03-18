<?php

/*
 * This file is part of phrasea-4.1.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Webhook;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Queue\Message;
use Alchemy\Queue\MessageQueueRegistry;

/**
 * Class WebhookPublisher publishes webhook event notifications in message queues
 * @package Alchemy\Phrasea\Webhook
 */
class WebhookPublisher implements WebhookPublisherInterface
{
    /**
     * @var MessageQueueRegistry
     */
    private $queueRegistry;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @param MessageQueueRegistry $queueRegistry
     * @param $queueName
     */
    public function __construct(MessageQueueRegistry $queueRegistry, $queueName)
    {
        $this->queueRegistry = $queueRegistry;
        $this->queueName = $queueName;
    }

    /**
     * @param WebhookEvent $event
     */
    public function publishWebhookEvent(WebhookEvent $event)
    {
        $queue = $this->queueRegistry->getQueue($this->queueName);
        $payload = [
            'message_type' => 'webhook',
            'payload' => [ 'id' => $event->getId() ]
        ];

        $queue->publish(new Message(json_encode($payload)));
    }
}
