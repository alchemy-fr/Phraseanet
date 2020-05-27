<?php

namespace Alchemy\Phrasea\WorkerManager\Subscriber;

use Alchemy\Phrasea\Model\Entities\WorkerRunningUploader;
use Alchemy\Phrasea\WorkerManager\Event\AssetsCreateEvent;
use Alchemy\Phrasea\WorkerManager\Event\AssetsCreationFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\AssetsCreationRecordFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetsIngestSubscriber implements EventSubscriberInterface
{
    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    public function __construct(MessagePublisher $messagePublisher)
    {
        $this->messagePublisher = $messagePublisher;
    }

    public function onAssetsCreate(AssetsCreateEvent $event)
    {
        // this is an uploader PUSH mode
        $payload = [
            'message_type'  => MessagePublisher::ASSETS_INGEST_TYPE,
            'payload'       => array_merge($event->getData(), ['type' => WorkerRunningUploader::TYPE_PUSH])
        ];


        $this->messagePublisher->publishMessage($payload, MessagePublisher::ASSETS_INGEST_QUEUE);
    }

    public function onAssetsCreationFailure(AssetsCreationFailureEvent $event)
    {
        $payload = [
            'message_type'  => MessagePublisher::ASSETS_INGEST_TYPE,
            'payload'       => $event->getPayload()
        ];

        $this->messagePublisher->publishMessage(
            $payload,
            MessagePublisher::RETRY_ASSETS_INGEST_QUEUE,
            $event->getCount(),
            $event->getWorkerMessage()
        );
    }

    public function onAssetsCreationRecordFailure(AssetsCreationRecordFailureEvent $event)
    {
        $payload = [
            'message_type'  => MessagePublisher::CREATE_RECORD_TYPE,
            'payload'       => $event->getPayload()
        ];

        $this->messagePublisher->publishMessage(
            $payload,
            MessagePublisher::RETRY_CREATE_RECORD_QUEUE,
            $event->getCount(),
            $event->getWorkerMessage()
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            WorkerEvents::ASSETS_CREATE                  => 'onAssetsCreate',
            WorkerEvents::ASSETS_CREATION_FAILURE        => 'onAssetsCreationFailure',
            WorkerEvents::ASSETS_CREATION_RECORD_FAILURE => 'onAssetsCreationRecordFailure'
        ];
    }
}
