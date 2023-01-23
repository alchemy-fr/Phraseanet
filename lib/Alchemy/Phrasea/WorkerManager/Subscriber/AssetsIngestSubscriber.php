<?php

namespace Alchemy\Phrasea\WorkerManager\Subscriber;

use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
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

    /** @var callable  */
    private $repoWorkerJobLocator;

    public function __construct(MessagePublisher $messagePublisher, callable $repoWorkerJobLocator)
    {
        $this->messagePublisher     = $messagePublisher;
        $this->repoWorkerJobLocator = $repoWorkerJobLocator;
    }

    public function onAssetsCreate(AssetsCreateEvent $event)
    {
        // this is an uploader PUSH mode
        $payload = [
            'message_type'  => MessagePublisher::ASSETS_INGEST_TYPE,
            'payload'       => array_merge($event->getData(), ['type' => WorkerRunningJob::TYPE_PUSH])
        ];

        $this->messagePublisher->publishMessage($payload, MessagePublisher::ASSETS_INGEST_TYPE);
    }

    public function onAssetsCreationFailure(AssetsCreationFailureEvent $event)
    {
        $payload = [
            'message_type'  => MessagePublisher::ASSETS_INGEST_TYPE,
            'payload'       => $event->getPayload()
        ];

        $this->messagePublisher->publishRetryMessage(
            $payload,
            MessagePublisher::ASSETS_INGEST_TYPE,
            $event->getCount(),
            $event->getWorkerMessage()
        );
    }

    public function onAssetsCreationRecordFailure(AssetsCreationRecordFailureEvent $event)
    {
        $repoWorker = $this->getRepoWorkerJob();

        $payload = [
            'message_type'  => MessagePublisher::CREATE_RECORD_TYPE,
            'payload'       => $event->getPayload()
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
                    ->setStatus(WorkerRunningJob::FINISHED)
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
            MessagePublisher::CREATE_RECORD_TYPE,     // todo
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

    /**
     * @return WorkerRunningJobRepository
     */
    private function getRepoWorkerJob()
    {
        $callable = $this->repoWorkerJobLocator;

        return $callable();
    }
}
