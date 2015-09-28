<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AccountDeletedEvent extends Event
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
     * @param int $userId
     * @param $login
     * @param string $emailAddress
     */
    public function __construct($userId, $login, $emailAddress)
    {
        $this->userId = $userId;
        $this->login = $login;
        $this->emailAddress = $emailAddress;
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
