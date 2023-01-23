<?php

namespace Alchemy\Phrasea\WorkerManager\Subscriber;

use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\WorkerManager\Event\PopulateIndexEvent;
use Alchemy\Phrasea\WorkerManager\Event\PopulateIndexFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchengineSubscriber implements EventSubscriberInterface
{
    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    /** @var callable  */
    private $repoWorkerJobLocator;

    public function __construct(MessagePublisher $messagePublisher, callable $repoWorkerJobLocator)
    {
        $this->messagePublisher     = $messagePublisher;
        $this->repoWorkerJobLocator = $repoWorkerJobLocator;
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

            $this->messagePublisher->publishMessage($payload, MessagePublisher::POPULATE_INDEX_TYPE);
        }
    }

    public function onPopulateIndexFailure(PopulateIndexFailureEvent $event)
    {
        $repoWorker = $this->getRepoWorkerJob();

        $payload = [
            'message_type' => MessagePublisher::POPULATE_INDEX_TYPE,
            'payload' => [
                'host'          => $event->getHost(),
                'port'          => $event->getPort(),
                'indexName'     => $event->getIndexName(),
                'databoxId'     => $event->getDataboxId(),
                'workerJobId'   => $event->getWorkerJobId()
            ]
        ];

        $em = $repoWorker->getEntityManager();
        // check connection an re-connect if needed
        $repoWorker->reconnect();

        /** @var WorkerRunningJob $workerRunningJob */
        $workerRunningJob = $repoWorker->find($event->getWorkerJobId());

        if ($workerRunningJob) {
            $em->beginTransaction();
            try {
                // count-1  for the number of finished attempt
                $workerRunningJob
                    ->setInfo(WorkerRunningJob::ATTEMPT. ($event->getCount() - 1))
                    ->setStatus(WorkerRunningJob::ERROR)
                ;

                $em->persist($workerRunningJob);
                $em->flush();
                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
            }
        }

        $this->messagePublisher->publishRetryMessage(
            $payload,
            MessagePublisher::POPULATE_INDEX_TYPE,
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

    /**
     * @return WorkerRunningJobRepository
     */
    private function getRepoWorkerJob()
    {
        $callable = $this->repoWorkerJobLocator;

        return $callable();
    }
}

