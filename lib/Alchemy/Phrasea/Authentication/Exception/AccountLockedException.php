<?php

namespace Alchemy\Phrasea\Authentication\Exception;

use Alchemy\Phrasea\Exception\RuntimeException;

class AccountLockedException extends RuntimeException
{
    private $usr_id;

    public function __construct($message, $usr_id, $code = 0, $previous = null)
    {
        $this->usr_id = $usr_id;
        parent::__construct($message, $code, $previous);
    }

    public function getUsrId()
    {
        return $this->usr_id;
    }
}
