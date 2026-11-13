<?php

namespace Alchemy\Queue;

use Ramsey\Uuid\Uuid;

class Message
{
    /**
     * @param array $data
     * @param null $correlationId
     * @return Message
     */
    public static function fromArray(array $data, $correlationId = null)
    {
        return new self(json_encode($data), $correlationId);
    }

    /**
     * @var string
     */
    private $body;

    /**
     * @var string
     */
    private $correlationId;

    /**
     * @param string $body
     * @param null|string $correlationId
     */
    public function __construct($body, $correlationId = null)
    {
        $this->body = (string) $body;
        $this->correlationId = (string) ($correlationId ?: Uuid::uuid4()->toString());
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function getCorrelationId()
    {
        return $this->correlationId;
    }
}
