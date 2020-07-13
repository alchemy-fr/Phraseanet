<?php

namespace Alchemy\Phrasea\WorkerManager\Queue;

use Monolog\Logger;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;

class MessagePublisher
{
    const EXPORT_MAIL_TYPE     = 'exportMail';
    const SUBDEF_CREATION_TYPE = 'subdefCreation';
    const WRITE_METADATAS_TYPE = 'writeMetadatas';
    const ASSETS_INGEST_TYPE   = 'assetsIngest';
    const CREATE_RECORD_TYPE   = 'createRecord';
    const DELETE_RECORD_TYPE   = 'deleteRecord';
    const WEBHOOK_TYPE         = 'webhook';
    const POPULATE_INDEX_TYPE  = 'populateIndex';
    const PULL_ASSETS_TYPE     = 'pullAssets';
    const SUBTITLE_TYPE        = 'subtitle';
    const MAIN_QUEUE_TYPE      = 'mainQueue';


    const MAIN_QUEUE           = 'main-queue';
    const SUBTITLE_QUEUE       = 'subtitle-queue';

    // worker queue  to be consumed, when no ack , it is requeued to the retry queue
    const EXPORT_QUEUE         = 'export-queue';
    const SUBDEF_QUEUE         = 'subdef-queue';
    const METADATAS_QUEUE      = 'metadatas-queue';
    const WEBHOOK_QUEUE        = 'webhook-queue';
    const ASSETS_INGEST_QUEUE  = 'ingest-queue';
    const CREATE_RECORD_QUEUE  = 'createrecord-queue';
    const DELETE_RECORD_QUEUE  = 'deleterecord-queue';
    const POPULATE_INDEX_QUEUE = 'populateindex-queue';
    const PULL_QUEUE           = 'pull-queue';

    // retry queue
    // we can use these retry queue with TTL, so when message expires it is requeued to the corresponding worker queue
    const RETRY_EXPORT_QUEUE         = 'retry-export-queue';
    const RETRY_SUBDEF_QUEUE         = 'retry-subdef-queue';
    const RETRY_METADATAS_QUEUE      = 'retry-metadatas-queue';
    const RETRY_WEBHOOK_QUEUE        = 'retry-webhook-queue';
    const RETRY_ASSETS_INGEST_QUEUE  = 'retry-ingest-queue';
    const RETRY_CREATE_RECORD_QUEUE  = 'retry-createrecord-queue';
    const RETRY_POPULATE_INDEX_QUEUE = 'retry-populateindex-queue';
    // use this queue to make a loop on a consumer
    const LOOP_PULL_QUEUE            = 'loop-pull-queue';

    // all failed queue, if message is treated over 3 times it goes to the failed queue
    const FAILED_EXPORT_QUEUE         = 'failed-export-queue';
    const FAILED_SUBDEF_QUEUE         = 'failed-subdef-queue';
    const FAILED_METADATAS_QUEUE      = 'failed-metadatas-queue';
    const FAILED_WEBHOOK_QUEUE        = 'failed-webhook-queue';
    const FAILED_ASSETS_INGEST_QUEUE  = 'failed-ingest-queue';
    const FAILED_CREATE_RECORD_QUEUE  = 'failed-createrecord-queue';
    const FAILED_POPULATE_INDEX_QUEUE = 'failed-populateindex-queue';

    // delayed queue when record is locked
    const DELAYED_SUBDEF_QUEUE    = 'delayed-subdef-queue';
    const DELAYED_METADATAS_QUEUE = 'delayed-metadatas-queue';

    const NEW_RECORD_MESSAGE   = 'newrecord';


    /** @var AMQPConnection $serverConnection */
    private $serverConnection;

    /** @var  Logger */
    private $logger;

    public function __construct(AMQPConnection $serverConnection, LoggerInterface $logger)
    {
        $this->serverConnection = $serverConnection;
        $this->logger           = $logger;
    }

    public function publishMessage(array $payload, $queueName, $retryCount = null, $workerMessage = '')
    {
        // add published timestamp to all message payload
        $payload['payload']['published'] = time();
        $msg = new AMQPMessage(json_encode($payload));
        $routing = array_search($queueName, AMQPConnection::$defaultRetryQueues);

        if (count($retryCount) && $routing != false) {
            // add a message header information
            $headers = new AMQPTable([
                'x-death' => [
                    [
                        'count'         => $retryCount,
                        'exchange'      => AMQPConnection::ALCHEMY_EXCHANGE,
                        'queue'         => $routing,
                        'routing-keys'  => $routing,
                        'reason'        => 'rejected',   // rejected is sended like nack
                        'time'          => new \DateTime('now', new \DateTimeZone('UTC'))
                    ]
                ],
                'worker-message' => $workerMessage
            ]);

            $msg->set('application_headers', $headers);
        }

        $channel = $this->serverConnection->setQueue($queueName);

        if ($channel == null) {
            $this->pushLog("Can't connect to rabbit, check configuration!", "error");

            return true;
        }

        $exchange = in_array($queueName, AMQPConnection::$defaultQueues) ? AMQPConnection::ALCHEMY_EXCHANGE : AMQPConnection::RETRY_ALCHEMY_EXCHANGE;
        $channel->basic_publish($msg, $exchange, $queueName);

        return true;
    }

    public function initializePullAssets()
    {
        $payload = [
            'message_type' => self::PULL_ASSETS_TYPE,
            'payload' => [
                'initTimestamp' => new \DateTime('now', new \DateTimeZone('UTC'))
            ]
        ];

        $this->publishMessage($payload, self::PULL_QUEUE);
    }

    public function connectionClose()
    {
        $this->serverConnection->connectionClose();
    }

    /**
     * @param $message
     * @param string $method
     * @param array $context
     */
    public function pushLog($message, $method = 'info', $context = [])
    {
        // write logs directly in file

        call_user_func(array($this->logger, $method), $message, $context);
    }

    public function publishFailedMessage(array $payload, AMQPTable $headers, $queueName)
    {
        $msg = new AMQPMessage(json_encode($payload));
        $msg->set('application_headers', $headers);

        $channel = $this->serverConnection->setQueue($queueName);
        if ($channel == null) {
            $this->pushLog("Can't connect to rabbit, check configuration!", "error");

            return ;
        }

        $channel->basic_publish($msg, AMQPConnection::RETRY_ALCHEMY_EXCHANGE, $queueName);
    }
}
