<?php
declare(ticks = 5);

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
    private $recordId = '-';
    private $subdefName = '-';
    private $wec_upid = '-';
    private $wrsc_upid = '-';

    public function __construct(MessagePublisher $messagePublisher)
    {
        $this->messagePublisher = $messagePublisher;
        $this->log("construct");
    }

    private function log($s = '', $depth=0)
    {
        // return;
        static $t0 = null;
        $t = microtime(true);
        if($t0 === null) {
            $t0 = $t;
        }
        $dt = (int)(1000000.0*($t - $t0));
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth+1);
        $line = array_key_exists($depth, $bt) && array_key_exists('line', $bt[$depth]) ? $bt[$depth]['line'] : -1;
        $s = sprintf("%s , %s , %s , %s , %d , pid=%-5d ppid=%-5d line=%-4d , %s\n", $this->wec_upid, $this->wrsc_upid, $this->recordId, $this->subdefName, $dt, getmypid(), posix_getppid(), $line, var_export($s, true));
        file_put_contents("/var/alchemy/Phraseanet/logs/trace_messagehandler.txt", $s . "\n", FILE_APPEND);
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
    public function consume(AMQPChannel $channel, AMQPConnection $AMQPConnection, WorkerInvoker $workerInvoker, $argQueueNames, $maxProcesses, $wec_upid = '')
    {
        $this->wec_upid = $wec_upid;
        $this->recordId = -1;
        $this->subdefName = '-';

        $this->log();
        pcntl_signal_dispatch();
        if ($channel == null) {
            // todo : if there is no channel, can we push ?
            $this->messagePublisher->pushLog("Can't connect to rabbit, check configuration!", "error");

            return ;
        }

        $AMQPConnection->declareExchange();
        
        // define consume callbacks
        $publisher = $this->messagePublisher;
        $callback = function (AMQPMessage $message) use ($AMQPConnection, $channel, $workerInvoker, $publisher, $wec_upid) {

            pcntl_signal_dispatch();
            $data = json_decode($message->getBody(), true);


            if(array_key_exists('recordId', $data['payload'])) {
                $this->recordId = $data['payload']['recordId'] ?: '-';
            }
            if(array_key_exists('subdefName', $data['payload'])) {
                $this->subdefName = $data['payload']['subdefName'] ?: '-';
            }



            $count = 0;

            $headers = null;
            pcntl_signal_dispatch();
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
                        pcntl_signal_dispatch();
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
                    pcntl_signal_dispatch();
                    $this->wrsc_upid = $wrsc_upid = Uuid::uuid4()->toString();
                    $data['payload']['wec_upid'] = $wec_upid;
                    $data['payload']['wrsc_upid'] = $wrsc_upid;

                    $this->log("invokeWorker");
                    pcntl_signal_dispatch();
                    $workerInvoker->invokeWorker($msgType, json_encode($data['payload']), $channel, $wec_upid, $wrsc_upid);
                    pcntl_signal_dispatch();

                    if ($AMQPConnection->hasLoopQueue($msgType)) {
                        // make a loop for the loop type
                        $channel->basic_nack($message->delivery_info['delivery_tag']);
                    } else {
                        $channel->basic_ack($message->delivery_info['delivery_tag']);
                    }

                    pcntl_signal_dispatch();
                    $publisher->pushLog(
                        sprintf('"%s" to be consumed! >> Payload :: %s', $msgType, json_encode($data['payload']))
                    );
                    pcntl_signal_dispatch();

                }
                catch (Exception $e) {
                    pcntl_signal_dispatch();
                    $channel->basic_nack($message->delivery_info['delivery_tag']);
                }
            }
        };

        $prefetchCount = $maxProcesses ? $maxProcesses : ProcessPool::MAX_PROCESSES;
        foreach($AMQPConnection->getBaseQueueNames() as $queueName) {
            pcntl_signal_dispatch();
            if (!$argQueueNames || in_array($queueName, $argQueueNames)) {
                $this->runConsumer($queueName, $AMQPConnection, $channel, $prefetchCount, $callback);
            }
        }
    }

    private function runConsumer($queueName, AMQPConnection $serverConnection, AMQPChannel $channel, $prefetchCount, $callback)
    {
        $this->log();
        pcntl_signal_dispatch();

        $serverConnection->setQueue($queueName);

        // todo : remove this if !!! move code to a generic place
        // initialize validation reminder when starting consumer
        if ($queueName == MessagePublisher::VALIDATION_REMINDER_TYPE) {
            $serverConnection->reinitializeQueue([MessagePublisher::VALIDATION_REMINDER_TYPE]);
            $this->messagePublisher->initializeLoopQueue(MessagePublisher::VALIDATION_REMINDER_TYPE);
        }

        //  give prefetch message to a worker consumer at a time
        pcntl_signal_dispatch();
        $channel->basic_qos(null, $prefetchCount, null);
        pcntl_signal_dispatch();
        $channel->basic_consume($queueName, Uuid::uuid4()->toString(), false, false, false, false, $callback);
        pcntl_signal_dispatch();
    }
}
