<?php

namespace Alchemy\Queue;

interface MessageHandler 
{

    /**
     * @param Message $message
     * @return bool
     */
    public function accepts(Message $message);

    /**
     * @param Message $message
     * @throws MessageHandlingException when the message cannot be processed
     */
    public function handle(Message $message);
}
