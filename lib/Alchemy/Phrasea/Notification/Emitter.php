<?php

namespace Alchemy\Phrasea\Notification;

class Emitter
{
    private $name;
    private $email;

    public function __construct($name, $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    public function name()
    {
        return $this->name;
    }

    public function email()
    {
        return $this->email;
    }

    public static function fromUser(\User_Adapter $user)
    {
        return new static($user->get_display_name(), $user->get_email());
    }
}