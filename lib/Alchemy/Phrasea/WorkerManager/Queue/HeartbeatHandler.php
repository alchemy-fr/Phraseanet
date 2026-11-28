<?php

declare(strict_types=1);

namespace Alchemy\Phrasea\WorkerManager\Queue;

use PhpAmqpLib\Connection\AbstractConnection;

class HeartbeatHandler
{
    private $messagePublisher;

    public function __construct(MessagePublisher $messagePublisher)
    {
        $this->messagePublisher = $messagePublisher;
    }

    /**
     * @param int $interval
     */
    public function run($interval)
    {
        $fileDir = $_SERVER['PWD'] .'/tmp/watchdog';

        while (true) {
            $filename = $_SERVER['PWD'] .'/tmp/watchdog/edit.watchdog';
            if (!file_exists($filename)) {
                if (!is_dir($fileDir)) {
                    mkdir($fileDir, 0775, true);
                }

                file_put_contents($filename, 'watchdog_edit_ping');

                $payload = [
                    'message_type' => MessagePublisher::MAIN_QUEUE_TYPE,
                    'payload' => [
                        'type'           => MessagePublisher::EDIT_RECORD_TYPE, // used to specify the final Q to publish message
                        'dataType'       => 'watchdog',
                        'data'           => 'watchdog_edit_ping'
                    ]
                ];

                $this->messagePublisher->publishMessage($payload, MessagePublisher::MAIN_QUEUE_TYPE);
            } else {
                $this->messagePublisher->pushLog("Edit record worker do not consume message! check if consumer is running ,busy , or to be restart!", "warning");

                @unlink($_SERVER['PWD'] . '/tmp/watchdog/edit.watchdog'); // unlink, so to be able to re-check after
            }

            // each 5 minutes
            sleep(300);
        }
    }
}
