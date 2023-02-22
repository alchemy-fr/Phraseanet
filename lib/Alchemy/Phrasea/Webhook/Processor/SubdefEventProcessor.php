<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;

class SubdefEventProcessor implements ProcessorInterface
{
    use ApplicationBoxAware;

    public function process(WebhookEvent $event)
    {
        $data         = $event->getData();
        $eventTime    = $data['event_time'];
        $url          = $data['url'];
        $instanceName = $data['instance_name'];

        unset($data['event_time']);
        unset($data['url']);
        unset($data['instance_name']);

        try {
            $record = $this->findDataboxById($data['databox_id'])->get_record($data['record_id']);
            $subdef = $record->get_subdef($data['subdef_name']);

            if (empty($data['permalink'])) {
                $data['permalink'] = $subdef->get_permalink()->get_url()->__toString();
            }

            if (empty($data['size'])) {
                $data['size'] = $subdef->get_size();
            }

            if (empty($data['type'])) {
                $data['type'] = $subdef->get_mime();
            }

        } catch (\Exception $e) {
            // if some error, we use the initial webhook data
        }

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
