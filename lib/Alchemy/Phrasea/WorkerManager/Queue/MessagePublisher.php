<?php

namespace Alchemy\Phrasea\WorkerManager\Queue;

use DateTime;
use DateTimeZone;
use Monolog\Logger;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;

class MessagePublisher
{
    const ASSETS_INGEST_TYPE   = 'assetsIngest';
    const CREATE_RECORD_TYPE   = 'createRecord';
    const DELETE_RECORD_TYPE   = 'deleteRecord';
    const EXPORT_MAIL_TYPE     = 'exportMail';
    const EXPOSE_UPLOAD_TYPE   = 'exposeUpload';
    const FTP_TYPE             = 'ftp';
    const POPULATE_INDEX_TYPE  = 'populateIndex';
    const PULL_ASSETS_TYPE     = 'pullAssets';
    const EDIT_RECORD_TYPE     = 'editRecord';
    const RECORDS_ACTIONS_TYPE = 'recordsActions';
    const SUBDEF_CREATION_TYPE = 'subdefCreation';
    const VALIDATION_REMINDER_TYPE  = 'validationReminder';
    const WRITE_METADATAS_TYPE = 'writeMetadatas';
    const WEBHOOK_TYPE         = 'webhook';
    const SHARE_BASKET_TYPE    = 'shareBasket';

    // *** by main queue *** \\
    const SUBTITLE_TYPE        = 'subtitle';
    const MAIN_QUEUE_TYPE      = 'mainQueue';


    const NEW_RECORD_MESSAGE   = 'newrecord';


    /** @var AMQPConnection $AMQPConnection */
    private $AMQPConnection;

    /** @var  Logger */
    private $logger;

    public function __construct(AMQPConnection $AMQPConnection, LoggerInterface $logger)
    {
        $this->AMQPConnection = $AMQPConnection;
        $this->logger         = $logger;
    }

    public function publishMessage(array $payload, $queueName)
    {
        $this->AMQPConnection->getBaseQueueName($queueName);    // just to throw an exception if q is undefined

        $this->_publishMessage($payload, $queueName);
    }

    public function publishRetryMessage(array $payload, string $baseQueueName, $retryCount, $workerMessage)
    {
        $retryQ = $this->AMQPConnection->getRetryQueueName($baseQueueName);

        $headers = null;
        if(!is_null($retryCount)) {
            // add a message header information
            $headers = new AMQPTable([
                'x-death' => [
                    [
                        'count'         => $retryCount,
                        'exchange'      => AMQPConnection::ALCHEMY_EXCHANGE,
                        'queue'         => $baseQueueName,
                        'routing-keys'  => $baseQueueName,
                        'reason'        => 'rejected',   // rejected is sended like nack
                        'time'          => new DateTime('now', new DateTimeZone('UTC'))
                    ]
                ],
                'worker-message' => $workerMessage
            ]);
        }
        $this->_publishMessage($payload, $retryQ, $headers);
    }

    public function publishDelayedMessage(array $payload, string $baseQueueName)
    {
        $delayedQ = $this->AMQPConnection->getDelayedQueueName($baseQueueName);

        $this->_publishMessage($payload, $delayedQ);
    }

    public function publishFailedMessage(array $payload, AMQPTable $headers, $baseQueueName)
    {
        $FailedQ = $this->AMQPConnection->getFailedQueueName($baseQueueName);

        $msg = new AMQPMessage(json_encode($payload));
        $msg->set('application_headers', $headers);

        $channel = $this->AMQPConnection->setQueue($FailedQ);
        if ($channel == null) {
            $this->pushLog("Can't connect to rabbit, check configuration!", "error");

            return ;
        }

//        $channel->basic_publish($msg, AMQPConnection::RETRY_ALCHEMY_EXCHANGE, $FailedQ);

        $this->_publishMessage($payload, $FailedQ, $headers);
    }

    private function _publishMessage(array $payload, $queueName, $headers = null)
    {
        // add published timestamp to all message payload
        $payload['payload']['published'] = time();

        $msg = new AMQPMessage(json_encode($payload), [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'content_type'  => 'application/json'
        ]);

        if (!is_null($headers)) {
            // add a message header information
            $msg->set('application_headers', $headers);
        }

        if (is_null( ($channel = $this->AMQPConnection->setQueue($queueName)) )) {
            $this->pushLog("Can't connect to rabbit, check configuration!", "error");

            return true;
        }

        $exchange = $this->AMQPConnection->getExchange($queueName); //  in_array($queueName, AMQPConnection::$defaultQueues) ? AMQPConnection::ALCHEMY_EXCHANGE : AMQPConnection::RETRY_ALCHEMY_EXCHANGE;
        $channel->basic_publish($msg, $exchange, $queueName);

        return true;
    }

    public function initializeLoopQueue($type)
    {
        $payload = [
            'message_type' => $type,
            'payload' => [
                'initTimestamp' => new DateTime('now', new DateTimeZone('UTC'))
            ]
        ];

        $this->publishMessage($payload, $type);
    }

    public function connectionClose()
    {
        $this->AMQPConnection->connectionClose();
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

}
