<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ACL;

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\User;

class BasketACL
{
    public function hasAccess(Basket $basket, User $user)
    {
        if ($this->isOwner($basket, $user)) {
            return true;
        }

        //       if ($basket->isVoteBasket()) {
        foreach ($basket->getParticipants() as $participant) {
            if ($participant->getUser()->getId() === $user->getId()) {
                return true;
            }
        }
        //       }

        return false;
    }

    /**
     * returns true if the user can add or remove elements from the basket
     * - is owner
     * or
     * - is participant with cam_modify=1
     *
     * @param Basket $basket
     * @param User $user
     * @return bool
     */
    public function canModifyContent(Basket $basket, User $user)
    {
        if ($this->isOwner($basket, $user)) {
            return true;
        }

        foreach ($basket->getParticipants() as $participant) {
            if ($participant->getUser()->getId() === $user->getId() && $participant->getCanModify()) {
                return true;
            }
        }

        return false;
    }

    public function isOwner(Basket $basket, User $user)
    {
        return $basket->getUser()->getId() === $user->getId();
    }
}
