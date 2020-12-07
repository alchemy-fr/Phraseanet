<?php

namespace Alchemy\Phrasea\WorkerManager\Queue;

use Alchemy\Phrasea\WorkerManager\Worker\ProcessPool;
use Alchemy\Phrasea\WorkerManager\Worker\WorkerInvoker;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Ramsey\Uuid\Uuid;

class MessageHandler
{
    const MAX_OF_TRY = 3;

    private $messagePublisher;

    public function __construct(MessagePublisher $messagePublisher)
    {
        $this->messagePublisher = $messagePublisher;
    }

    public function consume(AMQPConnection $serverConnection, WorkerInvoker $workerInvoker, $argQueueName, $maxProcesses)
    {
        $publisher = $this->messagePublisher;

        $channel = $serverConnection->getChannel();

        if ($channel == null) {
            $this->messagePublisher->pushLog("Can't connect to rabbit, check configuration!", "error");

            return ;
        }

        $serverConnection->declareExchange();
        
        // define consume callbacks
        $callback = function (AMQPMessage $message) use ($channel, $workerInvoker, $publisher) {

            $data = json_decode($message->getBody(), true);

            $count = 0;

            if ($message->has('application_headers')) {
                /** @var AMQPTable $headers */
                $headers = $message->get('application_headers');

                $headerData = $headers->getNativeData();
                if (isset($headerData['x-death'])) {
                    $xDeathHeader = $headerData['x-death'];

                    foreach ($xDeathHeader as $xdeath) {
                        $queue = $xdeath['queue'];
                        if (!in_array($queue, AMQPConnection::$defaultQueues)) {
                            continue;
                        }

                        $count = $xdeath['count'];
                        $data['payload']['count'] = $count;
                    }
                }
            }

            // if message is yet executed 3 times, save the unprocessed message in the corresponding failed queues
            if ($count > self::MAX_OF_TRY  && !in_array($data['message_type'], AMQPConnection::$defaultLoopTypes)) {
                $this->messagePublisher->publishFailedMessage($data['payload'], $headers, AMQPConnection::$defaultFailedQueues[$data['message_type']]);

                $logMessage = sprintf("Rabbit message executed 3 times, it's to be saved in %s , payload >>> %s",
                    AMQPConnection::$defaultFailedQueues[$data['message_type']],
                    json_encode($data['payload'])
                );
                $this->messagePublisher->pushLog($logMessage);

                $channel->basic_ack($message->delivery_info['delivery_tag']);
            } else {
                try {
                    $workerInvoker->invokeWorker($data['message_type'], json_encode($data['payload']));

                    if (in_array($data['message_type'], AMQPConnection::$defaultLoopTypes)) {
                        // make a loop for the loop type
                        $channel->basic_nack($message->delivery_info['delivery_tag']);
                    } else {
                        $channel->basic_ack($message->delivery_info['delivery_tag']);
                    }

                    $oldPayload = $data['payload'];
                    $message = $data['message_type'].' to be consumed! >> Payload ::'. json_encode($oldPayload);

                    $publisher->pushLog($message);
                } catch (\Exception $e) {
                    $channel->basic_nack($message->delivery_info['delivery_tag']);
                }
            }
        };

        $prefetchCount = ProcessPool::MAX_PROCESSES;

        if ($maxProcesses) {
            $prefetchCount = $maxProcesses;
        }

        foreach (AMQPConnection::$defaultQueues as $queueName) {
            if ($argQueueName ) {
                if (in_array($queueName, $argQueueName)) {
                    $this->runConsumer($queueName, $serverConnection, $channel, $prefetchCount, $callback);
                }
            } else {
                $this->runConsumer($queueName, $serverConnection, $channel, $prefetchCount, $callback);
            }
        }
    }

    private function runConsumer($queueName, AMQPConnection $serverConnection, AMQPChannel $channel, $prefetchCount, $callback)
    {
        $serverConnection->setQueue($queueName);

        // initialize validation reminder when starting consumer
        if ($queueName == MessagePublisher::VALIDATION_REMINDER_QUEUE) {
            $serverConnection->reinitializeQueue([MessagePublisher::VALIDATION_REMINDER_QUEUE]);
            $this->messagePublisher->initializeLoopQueue(MessagePublisher::VALIDATION_REMINDER_TYPE);
        }

        //  give prefetch message to a worker consumer at a time
        $channel->basic_qos(null, $prefetchCount, null);
        $channel->basic_consume($queueName, Uuid::uuid4(), false, false, false, false, $callback);
    }
}
