<?php

namespace Alchemy\Queue;

interface MessageQueueFactory 
{
    /**
     * @param $name
     * @return MessageQueue
     */
    public function getNamedQueue($name);
}
