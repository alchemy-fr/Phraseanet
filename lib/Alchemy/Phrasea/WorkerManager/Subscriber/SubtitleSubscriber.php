<?php

namespace Alchemy\Phrasea\WorkerManager\Subscriber;

use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Core\Event\Record\RecordAutoSubtitleEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\WorkerJob;
use Alchemy\Phrasea\Model\Repositories\WorkerJobRepository;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SubtitleSubscriber implements EventSubscriberInterface
{
    private $messagePublisher;

    /** @var WorkerJobRepository  $repoWorkerJob*/
    private $repoWorkerJob;

    public function __construct(WorkerJobRepository $repoWorkerJob, MessagePublisher $messagePublisher)
    {
        $this->repoWorkerJob    = $repoWorkerJob;
        $this->messagePublisher = $messagePublisher;
    }

    public function onRecordAutoSubtitle(RecordAutoSubtitleEvent $event)
    {
        $em = $this->repoWorkerJob->getEntityManager();

        $data = [
            "databoxId"         => $event->getRecord()->getDataboxId(),
            "recordId"          => $event->getRecord()->getRecordId(),
            "permalinkUrl"      => $event->getPermalinkUrl(),
            "langageSource"     => $event->getLanguageSource(),
            "metaStructureId"   => $event->getMetaStructId()
        ];

        $this->repoWorkerJob->reconnect();
        $em->beginTransaction();

        try {
            $workerJob = new WorkerJob();
            $workerJob
                ->setType(MessagePublisher::SUBTITLE_TYPE)
                ->setData($data)
                ->setStatus(WorkerJob::WAITING)
            ;

            $em->persist($workerJob);
            $em->flush();

            $em->commit();

            $data['workerId'] = $workerJob->getId();
            $data['type'] =  MessagePublisher::SUBTITLE_TYPE;

            $payload = [
                'message_type' => MessagePublisher::MAIN_QUEUE_TYPE,
                'payload' => $data
            ];

            $this->messagePublisher->publishMessage($payload, MessagePublisher::MAIN_QUEUE);
        } catch (\Exception $e) {
            $em->rollback();
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::RECORD_AUTO_SUBTITLE  => 'onRecordAutoSubtitle',
        ];
    }

}
