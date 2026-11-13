<?php

namespace Alchemy\Queue;

interface MessageQueue
{

    public function publish(Message $message);

    public function handle(MessageHandlerResolver $resolver);
}
