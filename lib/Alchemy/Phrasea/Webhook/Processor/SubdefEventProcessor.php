<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;

class SubdefEventProcessor implements ProcessorInterface
{

    public function process(WebhookEvent $event)
    {
        $data = $event->getData();
        $time = $data['time'];
        unset($data['time']);

        return [
            'event' => $event->getName(),
            'data'  => $data,
            'time'  => $time
        ];
    }
}
