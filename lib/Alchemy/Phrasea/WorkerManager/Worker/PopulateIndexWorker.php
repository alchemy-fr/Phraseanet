<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Model\Entities\WorkerRunningPopulate;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningPopulateRepository;
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

    /** @var  WorkerRunningPopulateRepository  $repoWorkerPopulate*/
    private $repoWorkerPopulate;

    public function __construct(MessagePublisher $messagePublisher, Indexer $indexer, WorkerRunningPopulateRepository $repoWorkerPopulate)
    {
        $this->indexer              = $indexer;
        $this->messagePublisher     = $messagePublisher;
        $this->repoWorkerPopulate   = $repoWorkerPopulate;
    }

    public function process(array $payload)
    {
        $em = $this->repoWorkerPopulate->getEntityManager();
        $em->beginTransaction();
        $date = new \DateTime();

        try {
            $workerRunningPopulate = new WorkerRunningPopulate();
            $workerRunningPopulate
                ->setHost($payload['host'])
                ->setPort($payload['port'])
                ->setIndexName($payload['indexName'])
                ->setDataboxId($payload['databoxId'])
                ->setPublished($date->setTimestamp($payload['published']))
                ->setStatus(WorkerRunningPopulate::RUNNING)
            ;

            $em->persist($workerRunningPopulate);

            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
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
                $count
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
                if ($workerRunningPopulate != null) {

                    $em->remove($workerRunningPopulate);

                    $em->flush();
                }

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
                    $count
                ));
            }
        }

        // tell that the populate is finished
        if ($workerRunningPopulate != null) {
            $workerRunningPopulate
                ->setStatus(WorkerRunningPopulate::FINISHED)
                ->setFinished(new \DateTime('now'))
            ;

            $em->persist($workerRunningPopulate);

            $em->flush();
        }
    }

}
