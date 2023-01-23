<?php

declare(strict_types=1);

namespace Alchemy\Phrasea\WorkerManager\Queue;

use PhpAmqpLib\Connection\AbstractConnection;

class HeartbeatHandler
{
    /**
     * @var AbstractConnection
     */
    private $connection;

    public function __construct(AbstractConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param int $interval
     */
    public function run($interval)
    {
        while (true) {
            if (!$this->connection->isConnected()) {
                return;
            }

            sleep((int) $interval / 2);

            $this->connection->checkHeartBeat();
        }
    }
}
