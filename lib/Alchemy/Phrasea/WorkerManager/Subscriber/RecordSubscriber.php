<?php

namespace Alchemy\Phrasea\WorkerManager\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Record\DeletedEvent;
use Alchemy\Phrasea\Core\Event\Record\DeleteEvent;
use Alchemy\Phrasea\Core\Event\Record\MetadataChangedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\SubdefinitionCreateEvent;
use Alchemy\Phrasea\Core\PhraseaTokens;
use Alchemy\Phrasea\Databox\Subdef\MediaSubdefRepository;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\WorkerManager\Event\StoryCreateCoverEvent;
use Alchemy\Phrasea\WorkerManager\Event\SubdefinitionCreationFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\SubdefinitionWritemetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Alchemy\Phrasea\WorkerManager\Worker\CreateRecordWorker;
use Alchemy\Phrasea\WorkerManager\Worker\Factory\WorkerFactoryInterface;
use Alchemy\Phrasea\WorkerManager\Worker\Resolver\TypeBasedWorkerResolver;
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
                    $payload = [
                        'message_type' => MessagePublisher::SUBDEF_CREATION_TYPE,
                        'payload' => [
                            'recordId'      => $event->getRecord()->getRecordId(),
                            'databoxId'     => $event->getRecord()->getDataboxId(),
                            'subdefName'    => $subdef->get_name(),
                            'status'        => $event->isNewRecord() ? MessagePublisher::NEW_RECORD_MESSAGE : ''
                        ]
                    ];

                    $this->messagePublisher->publishMessage($payload, MessagePublisher::SUBDEF_QUEUE);
                }
            }
        }
    }

    public function onDelete(DeleteEvent $event)
    {
        //  first remove record from the grid answer, so first delete the record in the index elastic
        $this->app['dispatcher']->dispatch(RecordEvents::DELETED, new DeletedEvent($event->getRecord()));

        //  publish payload to queue
        $payload = [
            'message_type' => MessagePublisher::DELETE_RECORD_TYPE,
            'payload' => [
                'recordId'      => $event->getRecord()->getRecordId(),
                'databoxId'     => $event->getRecord()->getDataboxId(),
            ]
        ];

        $this->messagePublisher->publishMessage($payload, MessagePublisher::DELETE_RECORD_QUEUE);
    }

    public function onSubdefinitionCreationFailure(SubdefinitionCreationFailureEvent $event)
    {
        $payload = [
            'message_type' => MessagePublisher::SUBDEF_CREATION_TYPE,
            'payload' => [
                'recordId'      => $event->getRecord()->getRecordId(),
                'databoxId'     => $event->getRecord()->getDataboxId(),
                'subdefName'    => $event->getSubdefName(),
                'status'        => ''
            ]
        ];

        $repoWorker = $this->getRepoWorker();
        $em = $repoWorker->getEntityManager();
        // check connection an re-connect if needed
        $repoWorker->reconnect();

        $workerRunningJob = $repoWorker->findOneBy([
            'databoxId' => $event->getRecord()->getDataboxId(),
            'recordId'  => $event->getRecord()->getRecordId(),
            'work'       => PhraseaTokens::MAKE_SUBDEF,
            'workOn'    => $event->getSubdefName()
        ]);

        if ($workerRunningJob) {
            $em->beginTransaction();
            try {
                $em->remove($workerRunningJob);
                $em->flush();
                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
            }
        }

        $this->messagePublisher->publishMessage(
            $payload,
            MessagePublisher::RETRY_SUBDEF_QUEUE,
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

    public function onMetadataChanged(MetadataChangedEvent $event)
    {
        $databoxId = $event->getRecord()->getDataboxId();
        $recordId = $event->getRecord()->getRecordId();

        $mediaSubdefRepository = $this->getMediaSubdefRepository($databoxId);
        $mediaSubdefs = $mediaSubdefRepository->findByRecordIdsAndNames([$recordId]);

        $databox = $this->getApplicationBox()->get_databox($databoxId);
        $record  = $databox->get_record($recordId);
        $type    = $record->getType();

        foreach ($mediaSubdefs as $subdef) {
            // check subdefmetadatarequired  from the subview setup in admin
            if ( $subdef->get_name() == 'document' || $this->isSubdefMetadataUpdateRequired($databox, $type, $subdef->get_name())) {
                if ($subdef->is_physically_present()) {
                    $payload = [
                        'message_type' => MessagePublisher::WRITE_METADATAS_TYPE,
                        'payload' => [
                            'recordId'      => $recordId,
                            'databoxId'     => $databoxId,
                            'subdefName'    => $subdef->get_name()
                        ]
                    ];

                    $this->messagePublisher->publishMessage($payload, MessagePublisher::METADATAS_QUEUE);
                } else {
                    $payload = [
                        'message_type' => MessagePublisher::WRITE_METADATAS_TYPE,
                        'payload' => [
                            'recordId'      => $recordId,
                            'databoxId'     => $databoxId,
                            'subdefName'    => $subdef->get_name()
                        ]
                    ];

                    $logMessage = sprintf("Subdef %s is not physically present! to be passed in the %s !  payload  >>> %s",
                        $subdef->get_name(),
                        MessagePublisher::RETRY_METADATAS_QUEUE,
                            json_encode($payload)
                    );
                    $this->messagePublisher->pushLog($logMessage);

                    $this->messagePublisher->publishMessage(
                        $payload,
                        MessagePublisher::RETRY_METADATAS_QUEUE,
                        2,
                        'Subdef is not physically present!'
                    );
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
                    'subdefName'    => $event->getSubdefName()
                ]
            ];

            $logMessage = sprintf("Subdef %s write meta failed, error : %s ! to be passed in the %s !  payload  >>> %s",
                $event->getSubdefName(),
                $event->getWorkerMessage(),
                MessagePublisher::RETRY_METADATAS_QUEUE,
                json_encode($payload)
            );
            $this->messagePublisher->pushLog($logMessage);

            $jeton = ($event->getSubdefName() == "document") ? PhraseaTokens::WRITE_META_DOC : PhraseaTokens::WRITE_META_SUBDEF;

            $repoWorker = $this->getRepoWorker();
            $em = $repoWorker->getEntityManager();
            // check connection an re-connect if needed
            $repoWorker->reconnect();

            $workerRunningJob = $repoWorker->findOneBy([
                'databoxId' => $event->getRecord()->getDataboxId(),
                'recordId'  => $event->getRecord()->getRecordId(),
                'work'       => $jeton,
                'workOn'    => $event->getSubdefName()
            ]);

            if ($workerRunningJob) {
                $em->beginTransaction();
                try {
                    $em->remove($workerRunningJob);
                    $em->flush();
                    $em->commit();
                } catch (\Exception $e) {
                    $em->rollback();
                }
            }

            $this->messagePublisher->publishMessage(
                $payload,
                MessagePublisher::RETRY_METADATAS_QUEUE,
                $event->getCount(),
                $event->getWorkerMessage()
            );

        } else {
            $databoxId = $event->getRecord()->getDataboxId();
            $recordId = $event->getRecord()->getRecordId();

            $databox = $this->getApplicationBox()->get_databox($databoxId);
            $record  = $databox->get_record($recordId);
            $type    = $record->getType();

            $subdef = $record->get_subdef($event->getSubdefName());

            //  only the required writemetadata from admin > subview setup is to be writing
            if ($subdef->get_name() == 'document' || $this->isSubdefMetadataUpdateRequired($databox, $type, $subdef->get_name())) {
                $payload = [
                    'message_type' => MessagePublisher::WRITE_METADATAS_TYPE,
                    'payload' => [
                        'recordId'      => $recordId,
                        'databoxId'     => $databoxId,
                        'subdefName'    => $event->getSubdefName()
                    ]
                ];

                $this->messagePublisher->publishMessage($payload, MessagePublisher::METADATAS_QUEUE);
            }
        }

    }

    public static function getSubscribedEvents()
    {
        return [
            RecordEvents::CREATED                             => 'onRecordCreated',
            RecordEvents::SUBDEFINITION_CREATE                => 'onSubdefinitionCreate',
            RecordEvents::DELETE                              => 'onDelete',
            WorkerEvents::SUBDEFINITION_CREATION_FAILURE      => 'onSubdefinitionCreationFailure',
            RecordEvents::METADATA_CHANGED                    => 'onMetadataChanged',
            WorkerEvents::STORY_CREATE_COVER                  => 'onStoryCreateCover',
            WorkerEvents::SUBDEFINITION_WRITE_META            => 'onSubdefinitionWritemeta'
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
     * @param \databox $databox
     * @param string $subdefType
     * @param string $subdefName
     * @return bool
     */
    private function isSubdefMetadataUpdateRequired(\databox $databox, $subdefType, $subdefName)
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
