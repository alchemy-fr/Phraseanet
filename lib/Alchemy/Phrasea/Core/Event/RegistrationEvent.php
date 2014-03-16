<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\EventDispatcher\Event as SfEvent;

class RegistrationEvent extends SfEvent
{
    /** @var \collection[] */
    private $collections;
    private $user;

    public function __construct(User $user, array $collections)
    {
        $this->collections = $collections;
        $this->user = $user;
    }

    /**
     * @return \collection[]
     */
    public function getCollections()
    {
        return $this->collections;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
