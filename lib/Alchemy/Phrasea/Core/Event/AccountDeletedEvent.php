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
    private $emailAddress;

    /**
     * @param int $userId
     * @param string $emailAddress
     */
    public function __construct($userId, $emailAddress)
    {
        $this->userId = $userId;
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
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }
}
