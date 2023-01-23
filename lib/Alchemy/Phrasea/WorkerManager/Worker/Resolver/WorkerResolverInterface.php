<?php

namespace Alchemy\Phrasea\WorkerManager\Worker\Resolver;

use Alchemy\Phrasea\WorkerManager\Worker\WorkerInterface;

interface WorkerResolverInterface
{
    /**
     * @param string $messageType
     * @param array $message
     * @return WorkerInterface
     */
    public function getWorker($messageType, array $message);
}
