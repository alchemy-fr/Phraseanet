<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclRelatedEvent extends Event
{

    /**
     * @var int
     */
    private $userId;

    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $emailAddress;

    /**
     * @var array|null
     * supplemental parameters specific to an inherited event class
     */
    protected $parms;

    /**
     * @param int $userId
     * @param $login
     * @param string $emailAddress
     */
    public function __construct($userId, $login, $emailAddress, array $parms = null)
    {
        $this->userId = $userId;
        $this->login = $login;
        $this->emailAddress = $emailAddress;
        $this->parms = $parms;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }
}
