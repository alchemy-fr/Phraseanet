<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AccountDeleted extends AccountRelated
{
    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->args['user_id'];
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->args['login'];
    }

    /**
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->args['email'];
    }
}
