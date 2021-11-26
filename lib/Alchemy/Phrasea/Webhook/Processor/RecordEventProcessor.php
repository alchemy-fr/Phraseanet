<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;

class RecordEventProcessor implements ProcessorInterface
{
    public function process(WebhookEvent $event)
    {
        $data = $event->getData();
        $time = $data['time'];
        $url  = $data['url'];
        $instanceName = $data['instance_name'];

        unset($data['time']);
        unset($data['url']);
        unset($data['instance_name']);

        return [
            'event'         => $event->getName(),
            'version'       => WebhookEvent::WEBHOOK_VERSION,
            'url'           => $url,
            'instance_name' => $instanceName,
            'data'          => $data,
            'time'          => $time
        ];
    }
}
