<?php

namespace Alchemy\Phrasea\WorkerManager\Subscriber;

use Alchemy\Phrasea\WorkerManager\Event\PopulateIndexEvent;
use Alchemy\Phrasea\WorkerManager\Event\PopulateIndexFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchengineSubscriber implements EventSubscriberInterface
{
    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    public function __construct(MessagePublisher $messagePublisher)
    {
        $this->messagePublisher = $messagePublisher;
    }

    public function onPopulateIndex(PopulateIndexEvent $event)
    {
        $populateInfo = $event->getData();

        // make payload per databoxId
        foreach ($populateInfo['databoxIds'] as $databoxId) {
            $payload = [
                'message_type' => MessagePublisher::POPULATE_INDEX_TYPE,
                'payload' => [
                    'host'      => $populateInfo['host'],
                    'port'      => $populateInfo['port'],
                    'indexName' => $populateInfo['indexName'],
                    'databoxId' => $databoxId
                ]
            ];

            $this->messagePublisher->publishMessage($payload, MessagePublisher::POPULATE_INDEX_QUEUE);
        }
    }

    public function onPopulateIndexFailure(PopulateIndexFailureEvent $event)
    {
        $payload = [
            'message_type' => MessagePublisher::POPULATE_INDEX_TYPE,
            'payload' => [
                'host'      => $event->getHost(),
                'port'      => $event->getPort(),
                'indexName' => $event->getIndexName(),
                'databoxId' => $event->getDataboxId(),
            ]
        ];

        $this->messagePublisher->publishMessage(
            $payload,
            MessagePublisher::RETRY_POPULATE_INDEX_QUEUE,
            $event->getCount(),
            $event->getWorkerMessage()
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            WorkerEvents::POPULATE_INDEX          => 'onPopulateIndex',
            WorkerEvents::POPULATE_INDEX_FAILURE  => 'onPopulateIndexFailure'
        ];
    }
}

