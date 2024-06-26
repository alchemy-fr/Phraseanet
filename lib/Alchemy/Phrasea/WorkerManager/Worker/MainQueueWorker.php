<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\WorkerJob;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerJobRepository;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\WorkerManager\Event\RecordEditInWorkerEvent;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;

class MainQueueWorker implements WorkerInterface
{
    use ApplicationBoxAware;
    use DataboxLoggerAware;

    private $messagePublisher;

    private $repoWorkerJob;
    private $repoWorkerRunningJob;
    private $conf;

    public function __construct(
        MessagePublisher $messagePublisher,
        WorkerJobRepository $repoWorkerJob,
        WorkerRunningJobRepository $repoWorkerRunningJob,
        PropertyAccess $conf
    )
    {
        $this->messagePublisher = $messagePublisher;
        $this->repoWorkerJob    = $repoWorkerJob;
        $this->repoWorkerRunningJob    = $repoWorkerRunningJob;
        $this->conf = $conf;
    }

    public function process(array $payload)
    {
        $message = [
            'message_type'  => MessagePublisher::MAIN_QUEUE_TYPE,
            'payload'       => $payload
        ];

        $autoCancelingJob = $this->conf->get(['workers', 'auto-cancelingJob'], null);

        if (!empty($autoCancelingJob)) {
            $autoCancelingJob = intval($autoCancelingJob);

            // first get the workerRunningJobs for log_docs 'subdefCreation', 'writeMetadatas'
            $workerRunningJobs = $this->repoWorkerRunningJob->getRunningSinceCreated($autoCancelingJob, ['subdefCreation', 'writeMetadatas']);

            // update the status for table workerRunningJob
            $this->repoWorkerRunningJob->updateStatusRunningToCanceledSinceCreated($autoCancelingJob);

            // last, treat the log_docs
            $finishedDate = new \DateTime('now');
            /** @var WorkerRunningJob $workerRunningJob */
            foreach ($workerRunningJobs as $workerRunningJob) {
                $databox    = $this->findDataboxById($workerRunningJob->getDataboxId());
                $record     = $databox->get_record($workerRunningJob->getRecordId());
                $subdefName = $workerRunningJob->getWorkOn();
                $action     = $workerRunningJob->getWork();
                $status     = 'canceled';

                $this->getDataboxLogger($databox)->initOrUpdateLogDocsFromWorker($record, $databox, $workerRunningJob, $subdefName, $action, $finishedDate, $status);
            }
        }

        $em = $this->repoWorkerJob->getEntityManager();
        $em->getConnection()->beginTransaction();
        /** @var WorkerJob $workerJob */

        // Actually the main Q use the WorkerJob table to save the payload
        $workerJob = new WorkerJob();
        $workerJob
            ->setType($payload['type'])
            ->setData($message)
            ->setStatus(WorkerJob::RUNNING)
            ->setStarted(new \DateTime())
        ;

        $em->persist($workerJob);

        $em->flush();
        $em->commit();

        // if needed, do treatement here depending on the type
        $queue = null;
        $childMessageCount = 1;
        $payloadData = [];
        $messageType = $payload['type'];
        unset($payload['type']);

        switch ($messageType) {
            case MessagePublisher::EDIT_RECORD_TYPE:
                $queue = MessagePublisher::EDIT_RECORD_TYPE;
                if ($payload['dataType'] == RecordEditInWorkerEvent::MDS_TYPE) {
                    $payloadData = array_map(function($singleMessage) use ($payload) {
                        $singleMessage['databoxId']     = $payload['databoxId'];
                        $singleMessage['dataType']      = $payload['dataType'];
                        if (isset($payload['sessionLogId'])) {
                            $singleMessage['sessionLogId']  = $payload['sessionLogId'];
                        }

                        return $singleMessage;
                    }, $payload['data']);
                } else {
                    $data = json_decode($payload['data'], true);

                    $payloadData = array_map(function($singleMessage) use ($payload, $data) {
                        $singleMessage['databoxId']     = $payload['databoxId'];
                        $singleMessage['dataType']      = $payload['dataType'];
                        $singleMessage['actions']       = $data['actions'];
                        if (isset($payload['sessionLogId'])) {
                            $singleMessage['sessionLogId']  = $payload['sessionLogId'];
                        }
                        unset($singleMessage['sbas_id']);

                        return $singleMessage;
                    }, $data['records']);
                }

                $childMessageCount = count($payloadData);

                break;

            case MessagePublisher::SUBTITLE_TYPE:
                $queue = MessagePublisher::SUBTITLE_TYPE;
                $payloadData[0] = $payload;
                $childMessageCount = 1;

                break;

            case MessagePublisher::DELETE_RECORD_TYPE:
                $queue = MessagePublisher::DELETE_RECORD_TYPE;

                if (isset($payload['recordIdsList']) && isset($payload['databoxId'])) {
                    $recordIds = explode(',', $payload['recordIdsList']);
                    foreach ($recordIds as $recId) {
                        $payloadData[] = [
                            'recordId'  => $recId,
                            'databoxId' => $payload['databoxId']
                        ];
                    }
                }

                $childMessageCount = count($payloadData);

                break;
        }

        // publish the different messages to the corresponding Q
        for ($i = 0; $i < $childMessageCount; $i++) {
            if ($queue != null && isset($payloadData[$i])) {
                $message = [
                    'message_type'  => $messageType,
                    'payload'       => $payloadData[$i]
                ];

                $this->messagePublisher->publishMessage($message, $queue);
            }
        }

        $this->messagePublisher->pushLog("Message processed in mainQueue >> ". json_encode($payload));

        $this->repoWorkerJob->reconnect();
        $em->getConnection()->beginTransaction();
        $workerJob
            ->setStatus(WorkerJob::FINISHED)
            ->setFinished(new \DateTime())
        ;

        $em->persist($workerJob);

        $em->flush();
        $em->commit();
    }
}
