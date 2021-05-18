<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\WorkerManager\Event\RecordEditInWorkerEvent;
use Alchemy\Phrasea\WorkerManager\Event\RecordsWriteMetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RecordEditWorker implements WorkerInterface
{
    use ApplicationBoxAware;
    use DataboxLoggerAware;

    private $repoWorker;
    private $dispatcher;

    public function __construct(WorkerRunningJobRepository $repoWorker, EventDispatcherInterface $dispatcher)
    {
        $this->repoWorker = $repoWorker;
        $this->dispatcher = $dispatcher;
    }

    public function process(array $payload)
    {
        try {
            $databox = $this->findDataboxById($payload['databoxId']);
        } catch(\Exception $e) {
            return;
        }

        $recordIds = [];

        $workerRunningJob = null;

        $em = $this->repoWorker->getEntityManager();
        $this->repoWorker->reconnect();

        $em->beginTransaction();

        try {
            $message = [
                'message_type'  => MessagePublisher::RECORD_EDIT_TYPE,
                'payload'       => $payload
            ];

            $date = new \DateTime();
            $workerRunningJob = new WorkerRunningJob();
            $workerRunningJob
                ->setDataboxId($payload['databoxId'])
                ->setWork(MessagePublisher::RECORD_EDIT_TYPE)
                ->setWorkOn("record")
                ->setPublished($date->setTimestamp($payload['published']))
                ->setStatus(WorkerRunningJob::RUNNING)
                ->setPayload($message)
            ;

            $em->persist($workerRunningJob);
            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
        }

        if ($payload['dataType'] === RecordEditInWorkerEvent::MDS_TYPE) {
            if (isset($payload['data']) && isset($payload['elementIds'])) {
                $recordIds = array_column($payload['data'], 'record_id');

                foreach ($payload['data'] as $rec) {
                    try {
                        /** @var \record_adapter $record */
                        $record = $databox->get_record($rec['record_id']);
                    } catch (\Exception $e) {
                        continue;
                    }

                    $key = $record->getId();

                    if (!in_array($key, $payload['elementIds'])) {
                        continue;
                    }

                    $statbits = $rec['status'];
                    $editDirty = $rec['edit'];

                    if ($editDirty == '0') {
                        $editDirty = false;
                    } else {
                        $editDirty = true;
                    }

                    if (isset($rec['metadatas']) && is_array($rec['metadatas'])) {
                        try {
                            $record->set_metadatas($rec['metadatas']);
                            $this->dispatcher->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($record));
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    if (isset($rec['technicalsdatas']) && is_array($rec['technicalsdatas'])) {
                        $record->insertOrUpdateTechnicalDatas($rec['technicalsdatas']);
                    }

                    $newstat = $record->getStatus();
                    $statbits = ltrim($statbits, 'x');
                    if (!in_array($statbits, ['', 'null'])) {
                        $mask_and = ltrim(str_replace(['x', '0', '1', 'z'], ['1', 'z', '0', '1'], $statbits), '0');
                        if ($mask_and != '') {
                            $newstat = \databox_status::operation_and_not($newstat, $mask_and);
                        }

                        $mask_or = ltrim(str_replace('x', '0', $statbits), '0');

                        if ($mask_or != '') {
                            $newstat = \databox_status::operation_or($newstat, $mask_or);
                        }

                        $record->setStatus($newstat);
                    }

                    $record->write_metas();

                    if ($statbits != '') {
                        $this->getDataboxLogger($databox)
                            ->log($record, \Session_Logger::EVENT_STATUS, '', '');
                    }
                    if ($editDirty) {
                        $this->getDataboxLogger($databox)
                            ->log($record, \Session_Logger::EVENT_EDIT, '', '');
                    }
                }
            }
        } else {
            $arg = json_decode($payload['data']);
            $recordIds = array_column($arg->records, 'record_id');

            // for now call record_adapter. no check, no acl, ...
            foreach($arg->records as $rec) {
                try {
                    /** @var \record_adapter $r */
                    $r = $databox->get_record($rec->record_id);
                    $r->setMetadatasByActions($arg->actions);
                } catch(\Exception $e) {
                    continue;
                }
            }
        }

        // order to write metas for those records
        $this->dispatcher->dispatch(WorkerEvents::RECORDS_WRITE_META,
            new RecordsWriteMetaEvent($recordIds, $payload['databoxId'])
        );

        // tell that we have finished to work on edit
        $this->repoWorker->reconnect();
        $em->getConnection()->beginTransaction();
        try {
            $workerRunningJob->setStatus(WorkerRunningJob::FINISHED);
            $workerRunningJob->setFinished(new \DateTime('now'));
            $em->persist($workerRunningJob);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
        }
    }
}
