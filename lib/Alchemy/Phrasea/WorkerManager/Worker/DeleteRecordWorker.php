<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;

class DeleteRecordWorker implements WorkerInterface
{
    use ApplicationBoxAware;

    /** @var  WorkerRunningJobRepository $repoWorker*/
    private $repoWorker;
    private $messagePublisher;

    public function __construct( WorkerRunningJobRepository $repoWorker, MessagePublisher $messagePublisher)
    {
        $this->repoWorker = $repoWorker;
        $this->messagePublisher = $messagePublisher;
    }

    public function process(array $payload)
    {
        $em = $this->repoWorker->getEntityManager();
        $em->beginTransaction();
        $date = new \DateTime();

        $message = [
            'message_type'  => MessagePublisher::DELETE_RECORD_TYPE,
            'payload'       => $payload
        ];

        try {
            $workerRunningJob = new WorkerRunningJob();
            $workerRunningJob
                ->setWork(MessagePublisher::DELETE_RECORD_TYPE)
                ->setDataboxId($payload['databoxId'])
                ->setRecordId($payload['recordId'])
                ->setPayload($message)
                ->setPublished($date->setTimestamp($payload['published']))
                ->setStatus(WorkerRunningJob::RUNNING)
            ;

            $em->persist($workerRunningJob);

            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
        }

        try {
            $databox = $this->findDataboxById($payload['databoxId']);
            $record = $databox->get_record($payload['recordId']);

            $record->delete();

            $this->messagePublisher->pushLog(sprintf("record deleted databoxname=%s databoxid=%d recordid=%d", $databox->get_viewname(), $payload['databoxId'], $payload['recordId']));
        } catch (\Exception $e) {
            $this->messagePublisher->pushLog(sprintf("%s (%s) : Error %s", __FILE__, __LINE__, $e->getMessage()), 'error');
            if ($workerRunningJob != null) {
                $workerRunningJob->setInfo('error : ' . $e->getMessage());
                $em->persist($workerRunningJob);
            }
        }

        // tell that the delete is finished
        if ($workerRunningJob != null) {
            $workerRunningJob
                ->setStatus(WorkerRunningJob::FINISHED)
                ->setFinished(new \DateTime('now'))
            ;

            $em->persist($workerRunningJob);

            $em->flush();
        }
    }
}
