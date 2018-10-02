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

class ExportFailureEvent extends SfEvent
{
    private $user;
    private $basketId;
    private $list;
    private $reason;
    private $target;

    public function __construct(User $user, $basketId, $list, $reason, $target)
    {
        $this->user = $user;
        $this->basketId = $basketId;
        $this->list = $list;
        $this->reason = $reason;
        $this->target = $target;
    }

    /**
     * @return mixed
     */
    public function getBasketId()
    {
        return $this->basketId;
    }

    /**
     * @return mixed
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
