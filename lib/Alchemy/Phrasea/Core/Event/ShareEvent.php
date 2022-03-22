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

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\EventDispatcher\Event as SfEvent;
use Symfony\Component\HttpFoundation\Request;

class ShareEvent extends SfEvent
{
    private $request;
    private $basket;

    /**
     * @var User
     */
    private $authenticatedUser;

    public function __construct(Request $request, Basket $basket, User $authenticatedUser)
    {
        $this->request = $request;
        $this->basket = $basket;
        $this->authenticatedUser = $authenticatedUser;
    }

    /**
     * @return Basket
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return User
     */
    public function getAuthenticatedUser()
    {
        return $this->authenticatedUser;
    }

}
