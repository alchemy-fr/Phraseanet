<?php

namespace Alchemy\Queue;

class MessageHandlerResolver
{
    /**
     * @var MessageHandler[]
     */
    private $handlers = [];

    /**
     * @var MessageHandler
     */
    private $fallbackHandler;

    public function __construct(?MessageHandler $defaultHandler = null)
    {
        $this->fallbackHandler = $defaultHandler ?: new NullMessageHandler();
    }

    /**
     * @param MessageHandler $handler
     */
    public function addHandler(MessageHandler $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * @return MessageHandler[]
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * @param Message $message
     * @return MessageHandler
     */
    public function resolveHandler(Message $message)
    {
        foreach ($this->handlers as $handler) {
            if ($handler->accepts($message)) {
                return $handler;
            }
        }

        return $this->fallbackHandler;
    }
}
