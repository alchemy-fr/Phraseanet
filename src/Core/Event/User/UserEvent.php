<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Core\Event\User;

use App\Entity\User as UserEntity;
use Symfony\Component\EventDispatcher\Event;
use App\Utils\ACL;

abstract class UserEvent extends Event
{
    /** @var UserEntity |null $user */
    private $user;

    /** @var  array|null $args */
    protected $args;

    /**
     * @param UserEntity|null $user
     * @param array|null $args
     */
    public function __construct($user, Array $args = null)
    {
        $this->user = $user;
        $this->args = $args;
    }

    public function getUser()
    {
        return $this->user;
    }
}
