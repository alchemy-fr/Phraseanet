<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;

class SubdefEventProcessor implements ProcessorInterface
{

    public function process(WebhookEvent $event)
    {
        return [
            'event' => $event->getName(),
            'data'  => $event->getData()
        ];
    }
}
