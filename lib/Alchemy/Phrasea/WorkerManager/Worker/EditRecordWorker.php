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

class EditRecordWorker implements WorkerInterface
{
    use ApplicationBoxAware;

    private $repoWorker;
    private $dispatcher;
    private $messagePublisher;

    public function __construct(WorkerRunningJobRepository $repoWorker, EventDispatcherInterface $dispatcher, MessagePublisher $messagePublisher)
    {
        $this->repoWorker = $repoWorker;
        $this->dispatcher = $dispatcher;
        $this->messagePublisher = $messagePublisher;
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

        if (isset($payload['workerJobId'])) {
            /** @var WorkerRunningJob $workerRunningJob */
            $workerRunningJob = $this->repoWorker->find($payload['workerJobId']);

            if ($workerRunningJob == null) {
                $this->messagePublisher->pushLog("Given workerJobId not found !", 'error');

                return ;
            }

            $workerRunningJob
                ->setInfo(WorkerRunningJob::ATTEMPT . $payload['count'])
                ->setStatus(WorkerRunningJob::RUNNING);

            $em->persist($workerRunningJob);

            $em->flush();
        } else {
            $this->repoWorker->reconnect();

            $em->beginTransaction();

            try {
                $message = [
                    'message_type'  => MessagePublisher::EDIT_RECORD_TYPE,
                    'payload'       => $payload
                ];

                $date = new \DateTime();
                $workerRunningJob = new WorkerRunningJob();
                $workerRunningJob
                    ->setDataboxId($payload['databoxId'])
                    ->setRecordId($payload['record_id'])
                    ->setWork(MessagePublisher::EDIT_RECORD_TYPE)
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
        }

        try {
            if ($payload['dataType'] === RecordEditInWorkerEvent::MDS_TYPE) {
                $recordIds = [$payload['record_id']];

                /** @var \record_adapter $record */
                $record = $databox->get_record($payload['record_id']);
                $previousDescription = $record->getRecordDescriptionAsArray();

                $statbits = $payload['status'];
                $editDirty = $payload['edit'];

                if ($editDirty == '0') {
                    $editDirty = false;
                } else {
                    $editDirty = true;
                }

                if (isset($payload['metadatas']) && is_array($payload['metadatas'])) {
                    $record->set_metadatas($payload['metadatas']);
                    $this->dispatcher->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($record, $previousDescription));
                }

                if (isset($payload['technicalsdatas']) && is_array($payload['technicalsdatas'])) {
                    $record->insertOrUpdateTechnicalDatas($payload['technicalsdatas']);
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

                if ($statbits != '' && isset($payload['sessionLogId'])) {
                    \Session_Logger::loadById($databox, $payload['sessionLogId'])
                        ->log($record, \Session_Logger::EVENT_STATUS, '', '');
                }
                if ($editDirty && isset($payload['sessionLogId'])) {
                    \Session_Logger::loadById($databox, $payload['sessionLogId'])
                        ->log($record, \Session_Logger::EVENT_EDIT, '', '');
                }
            } else {
                $recordIds = [$payload['record_id']];

                // for now call record_adapter. no check, no acl, ...
                /** @var \record_adapter $r */
                $r = $databox->get_record($payload['record_id']);
                // param actions is stdclass type
                $r->setMetadatasByActions(json_decode(json_encode($payload['actions'])));
            }
        } catch (\Exception $e) {
            $workerMessage = "An error occurred when editing record!: ". $e->getMessage();

            $this->messagePublisher->pushLog($workerMessage);

            // if payload count doesn't exist
            // count is 2 because it's to be the second time the message will be treated
            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;

            $this->repoWorker->reconnect();
            $em->beginTransaction();
            try {
                $workerRunningJob
                    ->setInfo(WorkerRunningJob::ATTEMPT. ($count - 1))
                    ->setStatus(WorkerRunningJob::ERROR)
                ;

                $em->persist($workerRunningJob);
                $em->flush();
                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
            }

            $payload['workerJobId'] = $workerRunningJob->getId();
            $fullPayload = [
                'message_type'  => MessagePublisher::EDIT_RECORD_TYPE,
                'payload'       => $payload
            ];

            $this->messagePublisher->publishRetryMessage(
                $fullPayload,
                MessagePublisher::EDIT_RECORD_TYPE,
                $count,
                $workerMessage
            );

            return;
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

        $this->messagePublisher->pushLog(sprintf("record edited databoxname=%s databoxid=%d recordid=%d", $databox->get_viewname(), $payload['databoxId'], $payload['record_id']));
    }
}
