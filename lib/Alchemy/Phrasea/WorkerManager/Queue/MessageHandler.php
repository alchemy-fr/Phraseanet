<?php

namespace Alchemy\Phrasea\WorkerManager\Queue;

use Alchemy\Phrasea\WorkerManager\Worker\ProcessPool;
use Alchemy\Phrasea\WorkerManager\Worker\WorkerInvoker;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Ramsey\Uuid\Uuid;

class MessageHandler
{
    private $messagePublisher;

    public function __construct(MessagePublisher $messagePublisher)
    {
        $this->messagePublisher = $messagePublisher;
    }

    /**
     * called by WorkerExecuteCommand cli
     *
     * @param AMQPChannel $channel
     * @param AMQPConnection $AMQPConnection
     * @param WorkerInvoker $workerInvoker
     * @param array|null $argQueueNames
     * @param $maxProcesses
     */
    public function consume(AMQPChannel $channel, AMQPConnection $AMQPConnection, WorkerInvoker $workerInvoker, $argQueueNames, $maxProcesses)
    {
        if ($channel == null) {
            // todo : if there is no channel, can we push ?
            $this->messagePublisher->pushLog("Can't connect to rabbit, check configuration!", "error");

            return ;
        }

        $AMQPConnection->declareExchange();
        
        // define consume callbacks
        $publisher = $this->messagePublisher;
        $callback = function (AMQPMessage $message) use ($AMQPConnection, $channel, $workerInvoker, $publisher) {

            $data = json_decode($message->getBody(), true);

            $count = 0;

            $headers = null;
            if ($message->has('application_headers')) {
                /** @var AMQPTable $headers */
                $headers = $message->get('application_headers');

                $headerData = $headers->getNativeData();
                if (isset($headerData['x-death'])) {
                    $xDeathHeader = $headerData['x-death'];

                    // todo : if there are more than 1 xdeath ? what is $count ?
                    foreach ($xDeathHeader as $xdeath) {
                        $queue = $xdeath['queue'];
                        if (!$AMQPConnection->isBaseQueue($queue)) {
                            continue;
                        }

                        if (isset($xdeath['count'])) {
                            $count = $xdeath['count'];
                            $data['payload']['count'] = $count;
                        }
                    }
                }
            }

            $msgType = $data['message_type'];

            if($count > $AMQPConnection->getSetting($msgType, AMQPConnection::MAX_RETRY) && !$AMQPConnection->hasLoopQueue($msgType)) {
                $publisher->publishFailedMessage($data['payload'], $headers, $data['message_type']);

                $logMessage = sprintf("Rabbit message executed %s times, it's to be saved in %s , payload >>> %s",
                    $count,
                    $AMQPConnection->getFailedQueueName($msgType),
                    json_encode($data['payload'])
                );
                $publisher->pushLog($logMessage);

                $channel->basic_ack($message->delivery_info['delivery_tag']);
            }
            else {
                try {
                    $workerInvoker->invokeWorker($msgType, json_encode($data['payload']), $channel);

                    if ($AMQPConnection->hasLoopQueue($msgType)) {
                        // make a loop for the loop type
                        $channel->basic_nack($message->delivery_info['delivery_tag']);
                    } else {
                        $channel->basic_ack($message->delivery_info['delivery_tag']);
                    }

                    $publisher->pushLog(
                        sprintf('"%s" to be consumed! >> Payload :: %s', $msgType, json_encode($data['payload']))
                    );
                }
                catch (Exception $e) {
                    $channel->basic_nack($message->delivery_info['delivery_tag']);
                }
            }
        };

        $prefetchCount = $maxProcesses ? $maxProcesses : ProcessPool::MAX_PROCESSES;
        foreach($AMQPConnection->getBaseQueueNames() as $queueName) {
            if (!$argQueueNames || in_array($queueName, $argQueueNames)) {
                $this->runConsumer($queueName, $AMQPConnection, $channel, $prefetchCount, $callback);
            }
        }
    }

    private function runConsumer($queueName, AMQPConnection $serverConnection, AMQPChannel $channel, $prefetchCount, $callback)
    {
        $serverConnection->setQueue($queueName);

        // todo : remove this if !!! move code to a generic place
        // initialize validation reminder when starting consumer
        if ($queueName == MessagePublisher::VALIDATION_REMINDER_TYPE) {
            $serverConnection->reinitializeQueue([MessagePublisher::VALIDATION_REMINDER_TYPE]);
            $this->messagePublisher->initializeLoopQueue(MessagePublisher::VALIDATION_REMINDER_TYPE);
        }

        //  give prefetch message to a worker consumer at a time
        $channel->basic_qos(null, $prefetchCount, null);
        $channel->basic_consume($queueName, Uuid::uuid4()->toString(), false, false, false, false, $callback);
    }
}
