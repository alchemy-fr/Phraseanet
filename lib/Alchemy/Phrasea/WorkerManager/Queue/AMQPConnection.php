<?php

namespace Alchemy\Phrasea\WorkerManager\Queue;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

class AMQPConnection
{
    const ALCHEMY_EXCHANGE          = 'alchemy-exchange';
    const RETRY_ALCHEMY_EXCHANGE    = 'retry-alchemy-exchange';

    /** @var  AMQPStreamConnection */
    private $connection;
    /** @var  AMQPChannel */
    private $channel;

    private $hostConfig;
    private $conf;

    // default message TTL in retry queue in millisecond
    const RETRY_DELAY =  10000;

    // default message TTL for some retry queue , 3 minute
    const RETRY_LARGE_DELAY = 180000;

    // default message TTL in delayed queue in millisecond
    const DELAY = 5000;

    // max number of retry before a message goes in failed
    const MAX_RETRY = 3;

    const WITH_NOTHING = 0;
    const WITH_RETRY   = 1;
    const WITH_DELAYED = 2;
    const WITH_LOOP    = 4;

    const BASE_QUEUE             = 'base_q';
    const BASE_QUEUE_WITH_RETRY  = 'base_q_with_retry';
    const BASE_QUEUE_WITH_LOOP   = 'base_q_with_loop';
    const RETRY_QUEUE            = 'retry_q';
    const LOOP_QUEUE             = 'loop_q';
    const FAILED_QUEUE           = 'failed_q';
    const DELAYED_QUEUE          = 'delayed_q';

   const MESSAGES = [
        MessagePublisher::WRITE_METADATAS_TYPE     => [
            'with'        => self::WITH_RETRY | self::WITH_DELAYED,
            'max_retry'   => self::MAX_RETRY,
            'ttl_retry'   => self::RETRY_DELAY,
            'ttl_delayed' => self::DELAY
        ],
        MessagePublisher::SUBDEF_CREATION_TYPE     => [
            'with'        => self::WITH_RETRY | self::WITH_DELAYED,
            'max_retry'   => self::MAX_RETRY,
            'ttl_retry'   => self::RETRY_DELAY,
            'ttl_delayed' => self::DELAY
        ],
        MessagePublisher::EXPORT_MAIL_TYPE         => [
            'with'        => self::WITH_RETRY,
            'max_retry'   => self::MAX_RETRY,
            'ttl_retry'   => self::RETRY_DELAY,
        ],
        MessagePublisher::WEBHOOK_TYPE             => [
            'with'        => self::WITH_RETRY,
            'max_retry'   => self::MAX_RETRY,
            'ttl_retry'   => self::RETRY_DELAY,
        ],
        MessagePublisher::ASSETS_INGEST_TYPE       => [
            'with'        => self::WITH_RETRY,
            'max_retry'   => self::MAX_RETRY,
            'ttl_retry'   => self::RETRY_DELAY,
        ],
        MessagePublisher::CREATE_RECORD_TYPE       => [
            'with'        => self::WITH_RETRY,
            'max_retry'   => self::MAX_RETRY,
            'ttl_retry'   => self::RETRY_DELAY,
        ],
        MessagePublisher::PULL_ASSETS_TYPE         => [
            'with'        => self::WITH_LOOP,
            'max_retry'   => self::MAX_RETRY,
            'ttl_retry'   => self::RETRY_DELAY,
        ],
        MessagePublisher::POPULATE_INDEX_TYPE      => [
            'with'        => self::WITH_RETRY,
            'max_retry'   => self::MAX_RETRY,
            'ttl_retry'   => self::RETRY_DELAY,
        ],
        MessagePublisher::DELETE_RECORD_TYPE       => [
            'with'        => self::WITH_NOTHING,
        ],
        MessagePublisher::MAIN_QUEUE_TYPE          => [
            'with'        => self::WITH_NOTHING,
        ],
        MessagePublisher::SUBTITLE_TYPE            => [
            'with'        => self::WITH_NOTHING,
        ],
        MessagePublisher::EXPOSE_UPLOAD_TYPE       => [
            'with'        => self::WITH_NOTHING,
        ],
        MessagePublisher::FTP_TYPE                 => [
            'with'        => self::WITH_RETRY,
            'max_retry'   => self::MAX_RETRY,
            'ttl_retry'   => 180 * 1000,
        ],
        MessagePublisher::VALIDATION_REMINDER_TYPE => [
            'with'        => self::WITH_LOOP,
            'max_retry'   => self::MAX_RETRY,
            'ttl_retry'   => 7200 * 1000,
        ],
    ];

    private $queues = [];   // filled during construct, from msg list, default values and conf

    public function __construct(PropertyAccess $conf)
    {
        $defaultConfiguration = [
            'host'      => 'localhost',
            'port'      => 5672,
            'user'      => 'guest',
            'password'  => 'guest',
            'vhost'     => '/'
        ];

        $this->hostConfig = $conf->get(['workers', 'queue', 'worker-queue'], $defaultConfiguration);
        $this->conf       = $conf;

        // fill list of type attributes
        foreach (self::MESSAGES as $name => $attr) {
            $this->queues[$name] = $attr;   // default q
            $this->queues[$name]['Name']     = $name;
            $this->queues[$name]['QType']    = self::BASE_QUEUE;
            $this->queues[$name]['Exchange'] = self::ALCHEMY_EXCHANGE;

            // a q with retry can fail (after n retry) or loop
            if($attr['with'] & self::WITH_RETRY) {
                $this->queues[$name]['QType'] = self::BASE_QUEUE_WITH_RETRY;    // todo : avoid changing qtype ?

                // declare the retry q, cross-link with base q
                $retry_name = 'retry_' . $name;
                $this->queues[$name]['RetryQ'] = $retry_name;       // link baseq to retryq
                $this->queues[$retry_name] = $attr;
                $this->queues[$retry_name]['Name']     = $retry_name;
                $this->queues[$retry_name]['QType']    = self::RETRY_QUEUE;
                $this->queues[$retry_name]['Exchange'] = self::RETRY_ALCHEMY_EXCHANGE;
                $this->queues[$retry_name]['BaseQ']    = $name;        // link retryq back to baseq

                // declare the failed q, cross-link with base q
                $failed_name = 'failed_' . $name;
                $this->queues[$name]['FailedQ'] = $failed_name;       // link baseq to failedq
                $this->queues[$failed_name] = $attr;
                $this->queues[$failed_name]['Name']     = $failed_name;
                $this->queues[$failed_name]['QType']    = self::FAILED_QUEUE;
                $this->queues[$failed_name]['Exchange'] = self::RETRY_ALCHEMY_EXCHANGE;
                $this->queues[$failed_name]['BaseQ']    = $name;        // link failedq back to baseq
            }
            // a q can be "delayed" to solve "work in progress" lock on records
            if($attr['with'] & self::WITH_DELAYED) {
                // declare the delayed q, cross-link with base q
                $delayed_name = 'delayed_' . $name;
                $this->queues[$name]['DelayedQ'] = $delayed_name;       // link baseq to delayedq
                $this->queues[$delayed_name] = $attr;
                $this->queues[$delayed_name]['Name']     = $delayed_name;
                $this->queues[$delayed_name]['QType']    = self::DELAYED_QUEUE;
                $this->queues[$delayed_name]['Exchange'] = self::RETRY_ALCHEMY_EXCHANGE;
                $this->queues[$delayed_name]['BaseQ']    = $name;        // link delayedq back to baseq
            }
            if($attr['with'] & self::WITH_LOOP) {
                $this->queues[$name]['QType'] = self::BASE_QUEUE_WITH_LOOP;    // todo : avoid changing qtype ?

                // declare the loop q, cross-link with base q
                $loop_name = 'loop_' . $name;
                $this->queues[$name]['LoopQ'] = $loop_name;       // link baseq to loopq
                $this->queues[$loop_name] = $attr;
                $this->queues[$loop_name]['Name']     = $loop_name;
                $this->queues[$loop_name]['QType']    = self::LOOP_QUEUE;
                $this->queues[$loop_name]['Exchange'] = self::RETRY_ALCHEMY_EXCHANGE;
                $this->queues[$loop_name]['BaseQ']    = $name;        // link loopq back to baseq
            }
        }
        // inject conf values
        foreach($conf->get(['workers', 'queues'], []) as $name => $settings) {
            if(!isset($this->queues[$name])) {
                throw new Exception(sprintf('undefined queue "%s" in conf', $name));
            }
            $this->queues[$name] = array_merge($this->queues[$name], $settings);
        }
    }

//    private function addQueue(string $name, string $Qtype, string $exchange, string $baseq=null)
//    {
//        $this->queues[$name]['Name']     = $name;
//        $this->queues[$name]['QType']    = $Qtype;
//        $this->queues[$name]['Exchange'] = $exchange;
//        $this->queues[$name]['BaseQ']    = $baseq;        // link  back to baseq
//    }

//    public function getQueueNames()
//    {
//        return array_keys($this->queues);
//    }

    public function getBaseQueueNames()
    {
        $keys = array_keys(self::MESSAGES);
        asort($keys);
        return $keys;
    }

    public function isBaseQueue(string $queueName)
    {
        return array_key_exists($queueName, self::MESSAGES);
    }

    public function getBaseQueueName(string $baseQueueName)
    {
        $q = $this->getQueue($baseQueueName);
        return $q['Name'];
    }

    public function hasRetryQueue(string $baseQueueName)
    {
        $q = $this->getQueue($baseQueueName);
        return array_key_exists('RetryQ', $q);
    }

    public function getRetryQueueName(string $baseQueueName)
    {
        $q = $this->getQueue($baseQueueName, 'RetryQ');
        return $q['Name'];
    }

    public function getMaxRetry(string $baseQueueName)
    {
        $q = $this->getQueue($baseQueueName);
        return $q['max_retry'];
    }

    public function getTTLRetry(string $baseQueueName)
    {
        $q = $this->getQueue($baseQueueName);
        return $q['ttl_retry'];
    }

    public function hasDelayedQueue(string $baseQueueName)
    {
        $q = $this->getQueue($baseQueueName);
        return array_key_exists('DelayedQ', $q);
    }

    public function getDelayedQueueName(string $baseQueueName)
    {
        $q = $this->getQueue($baseQueueName, 'DelayedQ');
        return $q['Name'];
    }

    public function getTTLDelayed(string $baseQueueName)
    {
        $q = $this->getQueue($baseQueueName);
        return $q['ttl_delayed'];
    }

    public function getFailedQueueName(string $baseQueueName)
    {
        $q = $this->getQueue($baseQueueName, 'FailedQ');
        return $q['Name'];
    }

    public function hasLoopQueue(string $baseQueueName)
    {
        $q = $this->getQueue($baseQueueName);
        return array_key_exists('LoopQ', $q);
    }

    public function getLoopQueueName(string $baseQueueName)
    {
        $q = $this->getQueue($baseQueueName, 'LoopQ');
        return $q['Name'];
    }

    public function getExchange(string $queueName)
    {
        $q = $this->getQueue($queueName);
        return $q['Exchange'];
    }

    private function getQueue(string $queueName, string $subQueueKey = null)
    {
        if(!array_key_exists($queueName, $this->queues)) {
            throw new Exception(sprintf('undefined queue "%s"', $queueName));
        }
        if($subQueueKey && !array_key_exists($subQueueKey, $this->queues[$queueName])) {
            throw new Exception(sprintf('base queue "%s" has no "%s"', $queueName, $subQueueKey));
        }
        return $subQueueKey ? $this->queues[$this->queues[$queueName][$subQueueKey]] : $this->queues[$queueName];
    }

    public function getConnection()
    {
        if (!isset($this->connection)) {
            try{
                $this->connection =  new AMQPStreamConnection(
                    $this->hostConfig['host'],
                    $this->hostConfig['port'],
                    $this->hostConfig['user'],
                    $this->hostConfig['password'],
                    $this->hostConfig['vhost']
                );

            }
            catch (Exception $e) {
                // no-op
            }
        }

        return $this->connection;
    }

    public function getChannel()
    {
        if (!isset($this->channel)) {
            $this->getConnection();
            if (isset($this->connection)) {
                $this->channel = $this->connection->channel();

                return $this->channel;
            }

            return null;
        }
        else {
            return $this->channel;
        }
    }

    public function declareExchange()
    {
        if (isset($this->channel)) {
            $this->channel->exchange_declare(self::ALCHEMY_EXCHANGE, 'direct', false, true, false);
            $this->channel->exchange_declare(self::RETRY_ALCHEMY_EXCHANGE, 'direct', false, true, false);
        }
    }

    /**
     * @param $queueName
     * @return AMQPChannel|null
     */
    public function setQueue($queueName)
    {
        if (!isset($this->channel)) {
            $this->getChannel();
            if (!isset($this->channel)) {
                // can't connect to rabbit
                return null;
            }

            $this->declareExchange();
        }

        $queue = $this->queues[$queueName];
        switch($queue['QType']) {
            case self::BASE_QUEUE_WITH_RETRY:
                $this->queue_declare_and_bind($queueName, self::ALCHEMY_EXCHANGE, [
                    'x-dead-letter-exchange'    => self::RETRY_ALCHEMY_EXCHANGE,            // the exchange to which republish a 'dead' message
                    'x-dead-letter-routing-key' => $queue['RetryQ']    // the routing key to apply to this 'dead' message
                ]);
                $this->setQueue($queue['RetryQ']);
                break;
            case self::BASE_QUEUE_WITH_LOOP:
                $this->queue_declare_and_bind($queueName, self::ALCHEMY_EXCHANGE, [
                    'x-dead-letter-exchange'    => self::RETRY_ALCHEMY_EXCHANGE,            // the exchange to which republish a 'dead' message
                    'x-dead-letter-routing-key' => $queue['LoopQ']    // the routing key to apply to this 'dead' message
                ]);
                $this->setQueue($queue['LoopQ']);
                break;
            case self::LOOP_QUEUE:
            case self::RETRY_QUEUE:
                $this->queue_declare_and_bind($queueName, self::RETRY_ALCHEMY_EXCHANGE, [
                    'x-dead-letter-exchange'    => self::ALCHEMY_EXCHANGE,
                    'x-dead-letter-routing-key' => $queue['BaseQ'],
                    'x-message-ttl'             => $this->queues[$queue['BaseQ']]['ttl_retry']
                ]);
                break;
            case self::DELAYED_QUEUE:
                $this->queue_declare_and_bind($queueName, self::RETRY_ALCHEMY_EXCHANGE, [
                    'x-dead-letter-exchange'    => self::ALCHEMY_EXCHANGE,
                    'x-dead-letter-routing-key' => $queue['BaseQ'],
                    'x-message-ttl'             => $this->queues[$queue['BaseQ']]['ttl_delayed']
                ]);
                break;
            case self::FAILED_QUEUE:
                $this->queue_declare_and_bind($queueName, self::RETRY_ALCHEMY_EXCHANGE);
                break;
            case self::BASE_QUEUE:
                $this->queue_declare_and_bind($queueName, self::ALCHEMY_EXCHANGE);
                break;
            default:
                throw new \Exception(sprintf('undefined q type "%s', $queueName));
                break;
        }

        return $this->channel;
    }

    private function queue_declare_and_bind(string $name, string $exchange, array $arguments = null)
    {
        $this->channel->queue_declare(
            $name,
            false, true, false, false, false,
            $arguments ? new AMQPTable($arguments) : null
        );
        $this->channel->queue_bind($name, $exchange, $name);
    }

    /**
     * purge some queues, delete related retry-q
     * nb: called by admin/purgeQueuAction, so a q may be __any kind__ - not only base-types !
     *
     * @param array $queueNames
     * @throws Exception
     */
    public function reinitializeQueue(array $queueNames)
    {
        if (!isset($this->channel)) {
            $this->getChannel();
            $this->declareExchange();
        }

        foreach ($queueNames as $queueName) {
            // re-inject conf values (some may have changed)
            $settings = $this->conf->get(['workers', 'queues', $queueName], []);
            if(array_key_exists($queueName, $this->queues)) {
                $this->queues[$queueName] = array_merge($this->queues[$queueName], $settings);
            }

            if(array_key_exists($queueName, self::MESSAGES)) {
                // base-q
                $this->purgeQueue($queueName);

                if($this->hasRetryQueue($queueName)) {
                    $this->deleteQueue($this->getRetryQueueName($queueName));
                }
                if($this->hasLoopQueue($queueName)) {
                    $this->deleteQueue($this->getLoopQueueName($queueName));
                }
            }
            else {
                // retry, delayed, loop, ... q
                $this->deleteQueue($queueName);
            }

            $this->setQueue($queueName);
        }
    }

    /**
     *  delete a queue, fails silently if the q does not exists
     *
     * @param $queueName
     */
    public function deleteQueue($queueName)
    {
        if (!isset($this->channel)) {
            $this->getChannel();
            $this->declareExchange();
        }
        try {
            $this->channel->queue_delete($queueName);
        }
        catch(\Exception $e) {
            // no-op
        }
    }

    /**
     *  purge a queue, fails silently if the q does not exists
     *
     * @param $queueName
     */
    public function purgeQueue($queueName)
    {
        if (!isset($this->channel)) {
            $this->getChannel();
            $this->declareExchange();
        }
        try {
            $this->channel->queue_purge($queueName);
        }
        catch(\Exception $e) {
            // no-op
        }
    }

    /**
     * Get queueName, messageCount, consumerCount  of queues
     * @return array
     * @throws Exception
     */
    public function getQueuesStatus()
    {
        $this->getChannel();
        $queuesStatus = [];

        foreach($this->queues as $name => $queue) {
            $this->setQueue($name);     // todo : BASE_QUEUE_WITH_RETRY will set both BASE and RETRY Q, so we should skip one of 2

            list($queueName, $messageCount, $consumerCount) = $this->channel->queue_declare($name, true);
            $queuesStatus[$queueName] = [
                'queueName'     => $queueName,
                'messageCount'  => $messageCount,
                'consumerCount' => $consumerCount
            ];
        }

        ksort($queuesStatus);

        return $queuesStatus;
    }

    public function connectionClose()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
