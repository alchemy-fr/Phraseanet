<?php

namespace Alchemy\Phrasea\WorkerManager\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Record\DeletedEvent;
use Alchemy\Phrasea\Core\Event\Record\DeleteEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\SubdefinitionCreateEvent;
use Alchemy\Phrasea\Databox\Subdef\MediaSubdefRepository;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\WorkerManager\Event\RecordEditInWorkerEvent;
use Alchemy\Phrasea\WorkerManager\Event\RecordsWriteMetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\StoryCreateCoverEvent;
use Alchemy\Phrasea\WorkerManager\Event\SubdefinitionCreationFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\SubdefinitionWritemetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Alchemy\Phrasea\WorkerManager\Worker\CreateRecordWorker;
use Alchemy\Phrasea\WorkerManager\Worker\Factory\WorkerFactoryInterface;
use Alchemy\Phrasea\WorkerManager\Worker\Resolver\TypeBasedWorkerResolver;
use databox;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RecordSubscriber implements EventSubscriberInterface
{
    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    /** @var TypeBasedWorkerResolver  $workerResolver*/
    private $workerResolver;

    /** @var  Application */
    private $app;

    /**
     * @var callable
     */
    private $appboxLocator;

    public function __construct(Application $app, callable $appboxLocator)
    {
        $this->messagePublisher    = $app['alchemy_worker.message.publisher'];
        $this->workerResolver      = $app['alchemy_worker.type_based_worker_resolver'];
        $this->app                 = $app;
        $this->appboxLocator       = $appboxLocator;
    }

    public function onSubdefinitionCreate(SubdefinitionCreateEvent $event)
    {
        $record = $this->getApplicationBox()->get_databox($event->getRecord()->getDataboxId())->get_record($event->getRecord()->getRecordId());

        if (!$record->isStory()) {
            $subdefs = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType());

            if ($subdefs !== null) {
                foreach ($subdefs as $subdef) {
                    // if subdefsTodo = null , so make all subdefs if phraseanet can build it
                    if ($subdef->isTobuild() && ($event->getSubdefsTodo() === null || (!empty($event->getSubdefsTodo()) && in_array($subdef->get_name(), $event->getSubdefsTodo())))) {
                        $payload = [
                            'message_type' => MessagePublisher::SUBDEF_CREATION_TYPE,
                            'payload' => [
                                'recordId'      => $event->getRecord()->getRecordId(),
                                'databoxId'     => $event->getRecord()->getDataboxId(),
                                'subdefName'    => $subdef->get_name(),
                                'status'        => $event->isNewRecord() ? MessagePublisher::NEW_RECORD_MESSAGE : ''
                            ]
                        ];

                        $this->messagePublisher->publishMessage($payload, MessagePublisher::SUBDEF_CREATION_TYPE);
                    }
                }
            }
        }
    }

    public function onDelete(DeleteEvent $event)
    {
        //  first remove record from the grid answer, so first delete the record in the index elastic
        $this->app['dispatcher']->dispatch(WorkerEvents::RECORD_DELETE_INDEX, new DeletedEvent($event->getRecord()));

        //  publish payload to queue
        $payload = [
            'message_type' => MessagePublisher::DELETE_RECORD_TYPE,
            'payload' => [
                'recordId'      => $event->getRecord()->getRecordId(),
                'databoxId'     => $event->getRecord()->getDataboxId(),
            ]
        ];

        $this->messagePublisher->publishMessage($payload, MessagePublisher::DELETE_RECORD_TYPE);
    }

    public function onSubdefinitionCreationFailure(SubdefinitionCreationFailureEvent $event)
    {
        $payload = [
            'message_type' => MessagePublisher::SUBDEF_CREATION_TYPE,
            'payload' => [
                'recordId'      => $event->getRecord()->getRecordId(),
                'databoxId'     => $event->getRecord()->getDataboxId(),
                'subdefName'    => $event->getSubdefName(),
                'status'        => '',
                'workerJobId'   => $event->getWorkerJobId()
            ]
        ];

        $repoWorker = $this->getRepoWorker();
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
                    ->setFlock(null)            // unlock !
                ;

                $em->persist($workerRunningJob);
                $em->flush();
                $em->commit();
            }
            catch (Exception $e) {
                $em->rollback();
            }
        }

        $this->messagePublisher->publishRetryMessage(
            $payload,
            MessagePublisher::SUBDEF_CREATION_TYPE,
            $event->getCount(),
            $event->getWorkerMessage()
        );
    }

    public function onRecordCreated(RecordEvent $event)
    {
        $this->messagePublisher->pushLog(sprintf('The %s= %d was successfully created',
            ($event->getRecord()->isStory() ? "story story_id" : "record record_id"),
            $event->getRecord()->getRecordId()
        ));
    }

    public function onRecordsWriteMeta(RecordsWriteMetaEvent $event)
    {
        $databoxId = $event->getDataboxId();
        $recordIds = $event->getRecordIds();

        foreach ($recordIds as $recordId) {
            $mediaSubdefRepository = $this->getMediaSubdefRepository($databoxId);
            $mediaSubdefs = $mediaSubdefRepository->findByRecordIdsAndNames([$recordId]);

            $databox = $this->getApplicationBox()->get_databox($databoxId);
            $record  = $databox->get_record($recordId);
            $type    = $record->getType();

            $subdefGroupe = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType());

            if ($subdefGroupe !== null) {
                $toWritemetaOriginalDocument = $subdefGroupe->toWritemetaOriginalDocument();
            } else {
                $toWritemetaOriginalDocument = true;
            }

            foreach ($mediaSubdefs as $subdef) {
                // check subdefmetadatarequired  from the subview setup in admin
                if (($subdef->get_name() == 'document' && $toWritemetaOriginalDocument) || $this->isSubdefMetadataUpdateRequired($databox, $type, $subdef->get_name())) {
                    $payload = [
                        'message_type' => MessagePublisher::WRITE_METADATAS_TYPE,
                        'payload' => [
                            'recordId'    => $recordId,
                            'databoxId'   => $databoxId,
                            'subdefName'  => $subdef->get_name()
                        ]
                    ];

                    if ($subdef->is_physically_present()) {
                        $this->messagePublisher->publishMessage($payload, MessagePublisher::WRITE_METADATAS_TYPE);
                    }
                    else {
                        $logMessage = sprintf('Subdef "%s" is not physically present! to be passed in the retry q of "%s" !  payload  >>> %s',
                            $subdef->get_name(),
                            MessagePublisher::WRITE_METADATAS_TYPE,
                            json_encode($payload)
                        );
                        $this->messagePublisher->pushLog($logMessage);

                        $this->messagePublisher->publishRetryMessage(
                            $payload,
                            MessagePublisher::WRITE_METADATAS_TYPE,
                            2,
                            'Subdef is not physically present!'
                        );
                    }
                }
            }
        }
    }

    public function onStoryCreateCover(StoryCreateCoverEvent $event)
    {
        /** @var  WorkerFactoryInterface[] $factories */
        $factories = $this->workerResolver->getFactories();

        /** @var CreateRecordWorker $createRecordWorker */
        $createRecordWorker = $factories[MessagePublisher::CREATE_RECORD_TYPE]->createWorker();

        $createRecordWorker->setStoryCover($event->getData());
    }

    public function onSubdefinitionWritemeta(SubdefinitionWritemetaEvent $event)
    {
        if ($event->getStatus() == SubdefinitionWritemetaEvent::FAILED) {
            $payload = [
                'message_type' => MessagePublisher::WRITE_METADATAS_TYPE,
                'payload' => [
                    'recordId'      => $event->getRecord()->getRecordId(),
                    'databoxId'     => $event->getRecord()->getDataboxId(),
                    'subdefName'    => $event->getSubdefName(),
                    'workerJobId'   => $event->getWorkerJobId()
                ]
            ];

            $logMessage = sprintf('Subdef "%s" write meta failed, error : "%s" ! to be passed in the retry q of "%s" !  payload  >>> %s',
                $event->getSubdefName(),
                $event->getWorkerMessage(),
                MessagePublisher::WRITE_METADATAS_TYPE,
                json_encode($payload)
            );
            $this->messagePublisher->pushLog($logMessage);

            $repoWorker = $this->getRepoWorker();
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
                }
                catch (Exception $e) {
                    $em->rollback();
                }
            }

            $this->messagePublisher->publishRetryMessage(
                $payload,
                MessagePublisher::WRITE_METADATAS_TYPE,
                $event->getCount(),
                $event->getWorkerMessage()
            );

        }
        else {
            $databoxId = $event->getRecord()->getDataboxId();
            $recordId = $event->getRecord()->getRecordId();

            $databox = $this->getApplicationBox()->get_databox($databoxId);
            $record  = $databox->get_record($recordId);
            $type    = $record->getType();

            $subdef = $record->get_subdef($event->getSubdefName());

            $subdefGroupe = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType());
            if ($subdefGroupe !== null) {
                $toWritemetaOriginalDocument = $subdefGroupe->toWritemetaOriginalDocument();
            } else {
                // default write meta on document
                $toWritemetaOriginalDocument = true;
            }

            //  only the required writemetadata from admin > subview setup is to be writing
            if (($subdef->get_name() == 'document' && $toWritemetaOriginalDocument) || $this->isSubdefMetadataUpdateRequired($databox, $type, $subdef->get_name())) {
                $payload = [
                    'message_type' => MessagePublisher::WRITE_METADATAS_TYPE,
                    'payload' => [
                        'recordId'      => $recordId,
                        'databoxId'     => $databoxId,
                        'subdefName'    => $event->getSubdefName()
                    ]
                ];

                $this->messagePublisher->publishMessage($payload, MessagePublisher::WRITE_METADATAS_TYPE);
            }
        }

    }

    public function onRecordEditInWorker(RecordEditInWorkerEvent $event)
    {
        //  publish payload to mainQ to split message per record
        $payload = [
            'message_type' => MessagePublisher::MAIN_QUEUE_TYPE,
            'payload' => [
                'type'           => MessagePublisher::EDIT_RECORD_TYPE, // used to specify the final Q to publish message
                'dataType'       => $event->getDataType(),
                'data'           => $event->getData(),
                'databoxId'      => $event->getDataboxId(),
                'sessionLogId'   => $event->getSessionLogId()
            ]
        ];

        $this->messagePublisher->publishMessage($payload, MessagePublisher::MAIN_QUEUE_TYPE);
    }

    public static function getSubscribedEvents()
    {
        return [
            /** @uses onRecordCreated */
            RecordEvents::CREATED                             => 'onRecordCreated',
            /** @uses onSubdefinitionCreate */
            RecordEvents::SUBDEFINITION_CREATE                => 'onSubdefinitionCreate',
            /** @uses onDelete */
            RecordEvents::DELETE                              => 'onDelete',
            /** @uses onSubdefinitionCreationFailure */
            WorkerEvents::SUBDEFINITION_CREATION_FAILURE      => 'onSubdefinitionCreationFailure',
            /** @uses onRecordsWriteMeta */
            WorkerEvents::RECORDS_WRITE_META                  => 'onRecordsWriteMeta',
            /** @uses onStoryCreateCover */
            WorkerEvents::STORY_CREATE_COVER                  => 'onStoryCreateCover',
            /** @uses onSubdefinitionWritemeta */
            WorkerEvents::SUBDEFINITION_WRITE_META            => 'onSubdefinitionWritemeta',
            /** @uses onRecordEditInWorker */
            WorkerEvents::RECORD_EDIT_IN_WORKER               => 'onRecordEditInWorker'
        ];
    }

    /**
     * @param $databoxId
     *
     * @return MediaSubdefRepository
     */
    private function getMediaSubdefRepository($databoxId)
    {
        return $this->app['provider.repo.media_subdef']->getRepositoryForDatabox($databoxId);
    }

    /**
     * @param databox $databox
     * @param string $subdefType
     * @param string $subdefName
     * @return bool
     */
    private function isSubdefMetadataUpdateRequired(databox $databox, $subdefType, $subdefName)
    {
        if ($databox->get_subdef_structure()->hasSubdef($subdefType, $subdefName)) {
            return $databox->get_subdef_structure()->get_subdef($subdefType, $subdefName)->isMetadataUpdateRequired();
        }

        return false;
    }

    /**
     * @return \appbox
     */
    private function getApplicationBox()
    {
        $callable = $this->appboxLocator;

        return $callable();
    }

    /**
     * @return WorkerRunningJobRepository
     */
    private function getRepoWorker()
    {
        return $this->app['repo.worker-running-job'];
    }
}
