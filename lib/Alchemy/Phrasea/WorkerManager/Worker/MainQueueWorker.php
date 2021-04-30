<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Model\Repositories\WorkerJobRepository;
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

        // if needed do treatement here depending on the type
        $queue = null;
        $messageType = '';

        switch ($payload['type']) {
            case MessagePublisher::SUBTITLE_TYPE:
                $queue = MessagePublisher::SUBTITLE_TYPE;
                $messageType = $payload['type'];
                unset($payload['type']);

                break;

        }

        $data = [
            'message_type' => $messageType,
            'payload' => $payload
        ];

        if ($queue != null) {
            $this->messagePublisher->publishMessage($data, $queue);
        }
    }
}
