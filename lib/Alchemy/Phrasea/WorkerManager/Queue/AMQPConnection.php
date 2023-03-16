<?php

namespace Alchemy\Phrasea\WorkerManager\Queue;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPSSLConnection;
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
    const DEFAULT_RETRY_DELAY_VALUE =  10000;

    // default message TTL in delayed queue in millisecond
    const DEFAULT_DELAYED_DELAY_VALUE = 5000;

    // max number of retry before a message goes in failed
    const DEFAULT_MAX_RETRY_VALUE = 3;

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

    // settings names
    const MAX_RETRY = 'max_retry';
    const TTL_RETRY = 'ttl_retry';
    const TTL_DELAYED = 'ttl_delayed';

    const MESSAGES = [
        MessagePublisher::ASSETS_INGEST_TYPE       => [
            'with'            => self::WITH_RETRY,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
            self::TTL_RETRY   => self::DEFAULT_RETRY_DELAY_VALUE,
        ],
        MessagePublisher::CREATE_RECORD_TYPE       => [
            'with'            => self::WITH_RETRY,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
            self::TTL_RETRY   => self::DEFAULT_RETRY_DELAY_VALUE,
        ],
        MessagePublisher::DELETE_RECORD_TYPE       => [
            'with'            => self::WITH_NOTHING,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
        ],
        MessagePublisher::EXPORT_MAIL_TYPE         => [
            'with'            => self::WITH_RETRY,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
            self::TTL_RETRY   => self::DEFAULT_RETRY_DELAY_VALUE,
        ],
        MessagePublisher::EXPOSE_UPLOAD_TYPE       => [
            'with'            => self::WITH_RETRY,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
            self::TTL_RETRY   => self::DEFAULT_RETRY_DELAY_VALUE,
        ],
        MessagePublisher::FTP_TYPE                 => [
            'with'            => self::WITH_RETRY,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
            self::TTL_RETRY   => 180 * 1000,
        ],
        MessagePublisher::MAIN_QUEUE_TYPE          => [
            'with'            => self::WITH_NOTHING,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
        ],
        MessagePublisher::POPULATE_INDEX_TYPE      => [
            'with'            => self::WITH_RETRY,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
            self::TTL_RETRY   => self::DEFAULT_RETRY_DELAY_VALUE,
        ],
        MessagePublisher::PULL_ASSETS_TYPE         => [
            'with'            => self::WITH_LOOP,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
            self::TTL_RETRY   => self::DEFAULT_RETRY_DELAY_VALUE,
        ],
        MessagePublisher::EDIT_RECORD_TYPE       => [
            'with'            => self::WITH_RETRY,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
            self::TTL_RETRY   => self::DEFAULT_RETRY_DELAY_VALUE,
        ],
        MessagePublisher::RECORDS_ACTIONS_TYPE     => [
            'with'           => self::WITH_LOOP,
            self::MAX_RETRY  => self::DEFAULT_MAX_RETRY_VALUE,
            self::TTL_RETRY  => self::DEFAULT_RETRY_DELAY_VALUE
        ],
        MessagePublisher::SUBDEF_CREATION_TYPE     => [
            'with'            => self::WITH_RETRY | self::WITH_DELAYED,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
            self::TTL_RETRY   => self::DEFAULT_RETRY_DELAY_VALUE,
            self::TTL_DELAYED => self::DEFAULT_DELAYED_DELAY_VALUE
        ],
        MessagePublisher::SUBTITLE_TYPE            => [
            'with'            => self::WITH_NOTHING,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
        ],
        MessagePublisher::VALIDATION_REMINDER_TYPE => [
            'with'            => self::WITH_LOOP,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
            self::TTL_RETRY   => 7200 * 1000,
        ],
        MessagePublisher::WEBHOOK_TYPE             => [
            'with'            => self::WITH_RETRY,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
            self::TTL_RETRY   => self::DEFAULT_RETRY_DELAY_VALUE,
        ],
        MessagePublisher::WRITE_METADATAS_TYPE     => [
            'with'            => self::WITH_RETRY | self::WITH_DELAYED,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
            self::TTL_RETRY   => self::DEFAULT_RETRY_DELAY_VALUE,
            self::TTL_DELAYED => self::DEFAULT_DELAYED_DELAY_VALUE
        ],
        MessagePublisher::SHARE_BASKET_TYPE             => [
            'with'            => self::WITH_RETRY,
            self::MAX_RETRY   => self::DEFAULT_MAX_RETRY_VALUE,
            self::TTL_RETRY   => self::DEFAULT_RETRY_DELAY_VALUE,
        ],
    ];

    private $queues = [];   // filled during construct, from msg list, default values and conf

    public function __construct(PropertyAccess $conf)
    {
        $defaultConfiguration = [
            'host'      => 'localhost',
            'port'      => 5672,
            'ssl'       => false,
            'user'      => 'guest',
            'password'  => 'guest',
            'vhost'     => '/',
            'heartbeat' => 60,
        ];

        $this->hostConfig = $conf->get(['workers', 'queue', 'worker-queue'], $defaultConfiguration);
        $this->conf       = $conf;

        // fill list of type attributes
        foreach (self::MESSAGES as $name => $attr) {
            $settings = $attr;
            unset($settings['with']);
            $this->queues[$name] = [
                'Name'             => $name,
                'QType'            => self::BASE_QUEUE,
                'default_settings' => $settings,   // to be displayed as placeholder
                'settings'         => $settings,   // settings belongs only to base_q
                'Exchange'         => self::ALCHEMY_EXCHANGE,
            ];

            // a q with retry can fail (after n retry) or loop
            if($attr['with'] & self::WITH_RETRY) {
                $this->queues[$name]['QType'] = self::BASE_QUEUE_WITH_RETRY;    // todo : avoid changing qtype ?

                // declare the retry q, cross-link with base q
                $retry_name = $name . '_retry';
                $this->queues[$name]['RetryQ'] = $retry_name;       // link baseq to retryq
                $this->queues[$retry_name] = [
                    'Name'     => $retry_name,
                    'QType'    => self::RETRY_QUEUE,
                    'Exchange' => self::RETRY_ALCHEMY_EXCHANGE,
                    'BaseQ'    => $name,        // link retryq back to baseq
                ];

                // declare the failed q, cross-link with base q
                $failed_name = $name . '_failed';
                $this->queues[$name]['FailedQ'] = $failed_name;       // link baseq to failedq
                $this->queues[$failed_name] = [
                    'Name'     => $failed_name,
                    'QType'    => self::FAILED_QUEUE,
                    'Exchange' => self::RETRY_ALCHEMY_EXCHANGE,
                    'BaseQ'    => $name,        // link failedq back to baseq
                ];
            }
            // a q can be "delayed" to solve "work in progress" lock on records
            if($attr['with'] & self::WITH_DELAYED) {
                // declare the delayed q, cross-link with base q
                $delayed_name = $name . '_delayed';
                $this->queues[$name]['DelayedQ'] = $delayed_name;       // link baseq to delayedq
                $this->queues[$delayed_name] = [
                    'Name'     => $delayed_name,
                    'QType'    => self::DELAYED_QUEUE,
                    'Exchange' => self::RETRY_ALCHEMY_EXCHANGE,
                    'BaseQ'    => $name,        // link delayedq back to baseq
                ];
            }
            if($attr['with'] & self::WITH_LOOP) {
                $this->queues[$name]['QType'] = self::BASE_QUEUE_WITH_LOOP;    // todo : avoid changing qtype ?

                // declare the loop q, cross-link with base q
                $loop_name = $name . '_loop';
                $this->queues[$name]['LoopQ'] = $loop_name;       // link baseq to loopq
                $this->queues[$loop_name] = [
                    'Name'     => $loop_name,
                    'QType'    => self::LOOP_QUEUE,
                    'Exchange' => self::RETRY_ALCHEMY_EXCHANGE,
                    'BaseQ'    => $name,        // link loopq back to baseq
                ];
            }
        }
        // inject conf values
        foreach($conf->get(['workers', 'queues'], []) as $name => $settings) {
            if(!isset($this->queues[$name])) {
                throw new Exception(sprintf('undefined queue "%s" in conf', $name));
            }
            $this->queues[$name]['settings'] = array_merge($this->queues[$name]['settings'], $settings);
        }
    }

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

    public function getSetting(string $baseQueueName, string $settingName)
    {
        $q = $this->getQueue($baseQueueName);
        return $q['settings'][$settingName];
    }

    public function getDefaultSetting(string $baseQueueName, string $settingName)
    {
        $q = $this->getQueue($baseQueueName);
        return $q['default_settings'][$settingName];
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
        if (empty($this->connection)) {
            try {
                $heartbeat = $this->hostConfig['heartbeat'] ?? 60;

                // if we are in ssl connection type
                if (isset($this->hostConfig['ssl']) && $this->hostConfig['ssl'] === true) {
                    $sslOptions = [
                        'verify_peer' => true,
                    ];

                    $this->connection =  new AMQPSSLConnection(
                        $this->hostConfig['host'],
                        $this->hostConfig['port'],
                        $this->hostConfig['user'],
                        $this->hostConfig['password'],
                        $this->hostConfig['vhost'],
                        $sslOptions,
                        [
                            'heartbeat' => $heartbeat,
                        ]
                    );
                } else {
                    $this->connection =  new AMQPStreamConnection(
                        $this->hostConfig['host'],
                        $this->hostConfig['port'],
                        $this->hostConfig['user'],
                        $this->hostConfig['password'],
                        $this->hostConfig['vhost'],
                        false,
                        'AMQPLAIN',
                        null,
                        'en_US',
                        3.0,
                        3.0,
                        null,
                        false,
                        $heartbeat
                    );
                }
            }
            catch (Exception $e) {
                // no-op
            }
        }

        return $this->connection;
    }

    public function getChannel()
    {
        if (empty($this->channel)) {
            $this->getConnection();
            if (!empty($this->connection)) {
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
     * @throws Exception
     */
    public function setQueue($queueName)
    {
        // first send heartbeat
        // catch if connection closed, and get a new one connection
        if (!empty($this->connection)) {
            try {
                $this->connection->checkHeartBeat();
            } catch(\Exception $e) {
                $this->connection = null;
                $this->channel = null;
                $this->getChannel();
            }
        } else {
            $this->connection = null;
            $this->channel = null;
            $this->getChannel();
        }

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
                    'x-message-ttl'             => (int)$this->queues[$queue['BaseQ']]['settings'][self::TTL_RETRY]
                ]);
                break;
            case self::DELAYED_QUEUE:
                $this->queue_declare_and_bind($queueName, self::RETRY_ALCHEMY_EXCHANGE, [
                    'x-dead-letter-exchange'    => self::ALCHEMY_EXCHANGE,
                    'x-dead-letter-routing-key' => $queue['BaseQ'],
                    'x-message-ttl'             => (int)$this->queues[$queue['BaseQ']]['settings'][self::TTL_DELAYED]
                ]);
                break;
            case self::FAILED_QUEUE:
                $this->queue_declare_and_bind($queueName, self::RETRY_ALCHEMY_EXCHANGE, [
                    'x-message-ttl' =>  604800*1000  //  message in failed_q to be dead after 7 days by default
                    ]);
                break;
            case self::BASE_QUEUE:
                $this->queue_declare_and_bind($queueName, self::ALCHEMY_EXCHANGE);
                break;
            default:
                throw new Exception(sprintf('undefined q type "%s', $queueName));
        }

        return $this->channel;
    }

    private function queue_declare_and_bind(string $name, string $exchange, array $arguments = null)
    {
        try {
            $this->channel->queue_declare(
                $name,
                false, true, false, false, false,
                $arguments ? new AMQPTable($arguments) : null
            );
            $this->channel->queue_bind($name, $exchange, $name);
        }
        catch (Exception $e) {
            // the q exists and arguments don't match, fallback.
            // Happens when we try to get the number of messages (getQueueStatus)
            // after the settings (e.g. ttl) was changed and the q was not yet re-created
            $this->getConnection();
            if (isset($this->connection)) {
                $this->channel = $this->connection->channel();
            }
            $this->channel->queue_declare($name,true);
        }
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
            if($this->isBaseQueue($queueName) && array_key_exists($queueName, $this->queues)) {
                $this->queues[$queueName]['settings'] = array_merge($this->queues[$queueName]['settings'], $settings);
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
        catch(Exception $e) {
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
        catch(Exception $e) {
            // no-op
        }
    }

    /**
     * Get queueName, messageCount, consumerCount  of queues
     * @return array
     * @throws Exception
     */
    public function getQueuesStatus($hideEmptyQ = true)
    {
        $this->getChannel();
        $queuesStatus = [];

        foreach($this->queues as $name => $queue) {

            $this->setQueue($name);     // todo : BASE_QUEUE_WITH_RETRY will set both BASE and RETRY Q, so we should skip one of 2

            $this->getConnection();
            if (isset($this->connection)) {
                $this->channel = $this->connection->channel();
            }
            try {
                list($queueName, $messageCount, $consumerCount) = $this->channel->queue_declare($name, true);
                if ($hideEmptyQ && $messageCount == 0) {
                    continue;
                }

                $queuesStatus[$queueName] = [
                    'queueName'     => $queueName,
                    'exists'        => true,
                    'messageCount'  => $messageCount,
                    'consumerCount' => $consumerCount
                ];
            }
            catch (Exception $e) {
                // should not happen since "setQueue()" was called
                $queuesStatus[$name] = [
                    'queueName'     => $name,
                    'exists'        => false,
                    'messageCount'  => -1,
                    'consumerCount' => -1
                ];
            }
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
