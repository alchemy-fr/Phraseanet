<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Model\Repositories\WorkerJobRepository;
use Alchemy\Phrasea\WorkerManager\Event\RecordEditInWorkerEvent;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;

class MainQueueWorker implements WorkerInterface
{
    private $messagePublisher;

    private $repoWorkerJob;

    public function __construct(
        MessagePublisher $messagePublisher,
        WorkerJobRepository $repoWorkerJob
    )
    {
        $this->messagePublisher = $messagePublisher;
        $this->repoWorkerJob    = $repoWorkerJob;
    }

    public function process(array $payload)
    {
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
                        $singleMessage['sessionLogId']  = $payload['sessionLogId'];

                        return $singleMessage;
                    }, $payload['data']);
                } else {
                    $data = json_decode($payload['data'], true);

                    $payloadData = array_map(function($singleMessage) use ($payload, $data) {
                        $singleMessage['databoxId']     = $payload['databoxId'];
                        $singleMessage['sessionLogId']  = $payload['sessionLogId'];
                        $singleMessage['dataType']      = $payload['dataType'];
                        $singleMessage['actions']       = $data['actions'];
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
    }
}
