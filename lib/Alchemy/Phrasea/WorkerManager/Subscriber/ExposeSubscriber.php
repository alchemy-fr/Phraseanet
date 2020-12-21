<?php

namespace Alchemy\Phrasea\WorkerManager\Subscriber;

use Alchemy\Phrasea\WorkerManager\Event\ExposeUploadEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExposeSubscriber implements EventSubscriberInterface
{
    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    public function __construct(MessagePublisher $messagePublisher)
    {
        $this->messagePublisher     = $messagePublisher;
    }

    public function onExposeUploadAssets(ExposeUploadEvent $event)
    {
        foreach (explode(";", $event->getLst()) as $bas_rec) {
            $basrec = explode('_', $bas_rec);
            if (count($basrec) != 2) {
                continue;
            }

            $payload = [
                'message_type'  => MessagePublisher::EXPOSE_UPLOAD_TYPE,
                'payload'       => [
                    'recordId'      => (int) $basrec[1],
                    'databoxId'     => (int) $basrec[0],
                    'exposeName'    => $event->getExposeName(),
                    'publicationId' => $event->getPublicationId(),
                    'accessToken'   => $event->getAccessToken()
                ]
            ];

            $this->messagePublisher->publishMessage($payload, MessagePublisher::EXPOSE_UPLOAD_TYPE);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            WorkerEvents::EXPOSE_UPLOAD_ASSETS  => 'onExposeUploadAssets',
        ];
    }
}
