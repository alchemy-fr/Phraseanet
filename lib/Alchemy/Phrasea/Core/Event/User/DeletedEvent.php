<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\User;

class DeletedEvent extends UserEvent
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

    /**
     * @return array
     */
    public function getGrantedBaseIds()
    {
        return $this->args['grantedBaseIds'];
    }
}
