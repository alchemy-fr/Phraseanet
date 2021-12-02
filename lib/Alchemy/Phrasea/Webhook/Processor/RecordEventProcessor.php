<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;

class RecordEventProcessor implements ProcessorInterface
{
    public function process(WebhookEvent $event)
    {
        $data         = $event->getData();
        $eventTime    = $data['event_time'];
        $url          = $data['url'];
        $instanceName = $data['instance_name'];

        unset($data['event_time']);
        unset($data['url']);
        unset($data['instance_name']);

        return [
            'event'         => $event->getName(),
            'webhookId'     => $event->getId(),
            'version'       => WebhookEvent::WEBHOOK_VERSION,
            'url'           => $url,
            'instance_name' => $instanceName,
            'data'          => $data,
            'event_time'    => $eventTime
        ];
    }
}
