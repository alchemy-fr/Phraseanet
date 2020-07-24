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

    public function __construct( WorkerRunningJobRepository $repoWorker)
    {
        $this->repoWorker = $repoWorker;
    }

    public function process(array $payload)
    {
        $em = $this->repoWorker->getEntityManager();
        $em->beginTransaction();
        $date = new \DateTime();

        try {
            $workerRunningJob = new WorkerRunningJob();
            $workerRunningJob
                ->setWork(MessagePublisher::DELETE_RECORD_TYPE)
                ->setDataboxId($payload['databoxId'])
                ->setRecordId($payload['recordId'])
                ->setPublished($date->setTimestamp($payload['published']))
                ->setStatus(WorkerRunningJob::RUNNING)
            ;

            $em->persist($workerRunningJob);

            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
        }


        $record = $this->findDataboxById($payload['databoxId'])->get_record($payload['recordId']);

        $record->delete();


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
