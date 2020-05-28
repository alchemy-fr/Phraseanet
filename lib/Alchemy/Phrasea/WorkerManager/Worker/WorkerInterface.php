<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

interface WorkerInterface
{
    /**
     * @param  array $payload
     * @return mixed
     */
    public function process(array $payload);
}