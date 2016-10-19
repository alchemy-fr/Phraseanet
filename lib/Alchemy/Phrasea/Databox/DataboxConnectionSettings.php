<?php

namespace Alchemy\Phrasea\Databox;

class DataboxConnectionSettings
{
    /**
     * @param array $configuration
     * @return DataboxConnectionSettings
     */
    public static function fromArray(array $configuration)
    {
        return new self(
            $configuration['host'],
            $configuration['port'],
            $configuration['user'],
            $configuration['password']
        );
    }

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $password
     */
    public function __construct($host, $port, $user, $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
}
