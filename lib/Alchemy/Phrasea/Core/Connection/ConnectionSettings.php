<?php

namespace Alchemy\Phrasea\Core\Connection;

class ConnectionSettings
{

    /**
     * @param array $configuration
     * @return self
     */
    public static function fromArray(array $configuration)
    {
        return new self(
            $configuration['host'],
            $configuration['port'],
            $configuration['user'],
            $configuration['password'],
            isset($configuration['dbname']) ? $configuration['dbname'] : ''
        );
    }

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $databaseName;

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $password;

    /**
     * @param string $host
     * @param int|null $port
     * @param string $user
     * @param string $password
     * @param string $databaseName
     */
    public function __construct($host, $port, $user, $password, $databaseName)
    {
        $this->host = (string) $host;
        $this->port = ((int) $port) > 0 ? (int) $port : null;
        $this->databaseName = (string) $databaseName;
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
    public function getDatabaseName()
    {
        return $this->databaseName;
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

    /**
     * @return array
     */
    public function toArray()
    {
        $settings = [
            'host' => $this->host,
            'port' => $this->port,
            'user' => $this->user,
            'password' => $this->password
        ];

        if ($this->databaseName) {
            $settings['dbname'] = $this->databaseName;
        }

        return $settings;
    }

}
