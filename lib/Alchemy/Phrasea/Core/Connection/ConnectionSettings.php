<?php

namespace Alchemy\Phrasea\Core\Connection;

class ConnectionSettings
{

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
     * @param string $databaseName
     * @param string $user
     * @param string $password
     */
    public function __construct($host, $port, $databaseName, $user, $password)
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

    public function toArray()
    {
        return [
            'dbname' => $this->databaseName,
            'host' => $this->host,
            'port' => $this->port,
            'user' => $this->user,
            'password' => $this->password
        ];
    }

}
