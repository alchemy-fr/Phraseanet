<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ACL;

use Alchemy\Phrasea\Model\Entities\Basket;
use User_Adapter;

class BasketACL
{
    public function hasAccess(Basket $basket, User_Adapter $user)
    {
        if ($this->isOwner($basket, $user)) {
            return true;
        }

        if ($basket->getValidation()) {
            foreach ($basket->getValidation()->getParticipants() as $participant) {
                if ($participant->getUsrId() === $user->get_id()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isOwner(Basket $basket, User_Adapter $user)
    {
        return $basket->getUsrId() === $user->get_id();
    }
}
