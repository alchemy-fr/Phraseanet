<?php

namespace Alchemy\Phrasea\Core\Event;

use User_Adapter as User;
use Symfony\Component\EventDispatcher\Event;

class AccountRelated extends Event
{
    /** @var User|null $user */
    private $user;

    /** @var  array|null $args */
    protected $args;

    /**
     * @param User $user
     * @param array|null $args
     */
    public function __construct(User $user, Array $args = null)
    {
        $this->user = $user;
        $this->args = $args;
    }
}
