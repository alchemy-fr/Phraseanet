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

        return array(
            'event' => $event->getName(),
            'user' => [
                'id' => $data['user_id'],
                'email' => $data['email'],
                'login' => $data['login'],
            ],
            'time' => $data['time']
        );
    }
}