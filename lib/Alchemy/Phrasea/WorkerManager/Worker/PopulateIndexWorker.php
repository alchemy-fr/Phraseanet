<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Alchemy\Phrasea\WorkerManager\Event\PopulateIndexFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;

class PopulateIndexWorker implements WorkerInterface
{
    use ApplicationBoxAware;
    use DispatcherAware;

    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    /** @var Indexer $indexer */
    private $indexer;

    /** @var  WorkerRunningJobRepository $repoWorker*/
    private $repoWorker;

    public function __construct(MessagePublisher $messagePublisher, Indexer $indexer, WorkerRunningJobRepository $repoWorker)
    {
        $this->indexer              = $indexer;
        $this->messagePublisher     = $messagePublisher;
        $this->repoWorker           = $repoWorker;
    }

    public function process(array $payload)
    {
        $em = $this->repoWorker->getEntityManager();
        $this->repoWorker->reconnect();

        if (isset($payload['workerJobId'])) {
            /** @var WorkerRunningJob $workerRunningJob */
            $workerRunningJob = $this->repoWorker->find($payload['workerJobId']);

            if ($workerRunningJob == null) {
                $this->messagePublisher->pushLog("Given workerJobId not found !", "error");

                return ;
            }

            $workerRunningJob
                ->setInfo(WorkerRunningJob::ATTEMPT . $payload['count'])
                ->setStatus(WorkerRunningJob::RUNNING)
            ;

            $em->persist($workerRunningJob);

            $em->flush();
        } else {
            $em->beginTransaction();
            $date = new \DateTime();
            $message = [
                'message_type'  => MessagePublisher::POPULATE_INDEX_TYPE,
                'payload'       => $payload
            ];

            try {
                $workerRunningJob = new WorkerRunningJob();
                $workerRunningJob
                    ->setWork(MessagePublisher::POPULATE_INDEX_TYPE)
                    ->setWorkOn($payload['indexName'])
                    ->setPayload($message)
                    ->setDataboxId($payload['databoxId'])
                    ->setPublished($date->setTimestamp($payload['published']))
                    ->setStatus(WorkerRunningJob::RUNNING)
                ;

                $em->persist($workerRunningJob);

                $em->flush();

                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
            }
        }

        /** @var ElasticsearchOptions $options */
        $options = $this->indexer->getIndex()->getOptions();

        $options->setIndexName($payload['indexName']);
        $options->setHost($payload['host']);
        $options->setPort($payload['port']);

        $databoxId = $payload['databoxId'];

        $indexExists = $this->indexer->indexExists();

        if (!$indexExists) {
            $workerMessage = sprintf("Index %s don't exist!", $payload['indexName']);
            $this->messagePublisher->pushLog($workerMessage);

            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;

            // send to retry queue
            $this->dispatch(WorkerEvents::POPULATE_INDEX_FAILURE, new PopulateIndexFailureEvent(
                $payload['host'],
                $payload['port'],
                $payload['indexName'],
                $payload['databoxId'],
                $workerMessage,
                $count,
                $workerRunningJob->getId()
            ));
        } else {
            $databox = $this->findDataboxById($databoxId);

            try {
                $r = $this->indexer->populateIndex(Indexer::THESAURUS | Indexer::RECORDS, $databox); // , $temporary);

                $this->messagePublisher->pushLog(sprintf(
                    "Indexation of databox \"%s\" finished in %0.2f sec (Mem. %0.2f Mo)",
                    $databox->get_dbname(),
                    $r['duration']/1000,
                    $r['memory']/1048576
                ));
            } catch(\Exception $e) {
                $workerMessage = sprintf("Error on indexing : %s ", $e->getMessage());
                $this->messagePublisher->pushLog($workerMessage);

                $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;

                // notify to send a retry
                $this->dispatch(WorkerEvents::POPULATE_INDEX_FAILURE, new PopulateIndexFailureEvent(
                    $payload['host'],
                    $payload['port'],
                    $payload['indexName'],
                    $payload['databoxId'],
                    $workerMessage,
                    $count,
                    $workerRunningJob->getId()
                ));
            }
        }

        // tell that the populate is finished
        if ($workerRunningJob != null) {
            $this->repoWorker->reconnect();
            $workerRunningJob
                ->setStatus(WorkerRunningJob::FINISHED)
                ->setFinished(new \DateTime('now'))
            ;

            $em->persist($workerRunningJob);

            $em->flush();
        }
    }

}
