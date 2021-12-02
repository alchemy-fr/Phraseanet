<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;

class UserProcessor implements ProcessorInterface
{

    public function process(WebhookEvent $event)
    {
        $data = $event->getData();

        if (! isset($data['user_id'])) {
            return null;
        }

        return [
            'event'         => $event->getName(),
            'webhookId'     => $event->getId(),
            'version'       => WebhookEvent::WEBHOOK_VERSION,
            'url'           => $data['url'],
            'instance_name' => $data['instance_name'],
            'user' => [
                'id'    => $data['user_id'],
                'email' => $data['email'],
                'login' => $data['login'],
            ],
            'event_time' => $data['event_time']
        ];
    }
}
