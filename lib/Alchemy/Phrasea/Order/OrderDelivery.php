<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order;

use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\User;

class OrderDelivery
{
    /**
     * @var User
     */
    private $admin;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var int
     */
    private $quantity;

    /**
     * @var PartialOrder
     */
    private $partialOrder;

    private $expireOn;

    /**
     * @param Order $deliveredOrder
     * @param User $manager
     * @param int $quantity
     * @param \DateTime $expireOn
     * @param PartialOrder $partialOrder
     */
    public function __construct(Order $deliveredOrder, User $manager, $quantity, PartialOrder $partialOrder, \DateTime $expireOn = null)
    {
        $this->order        = $deliveredOrder;
        $this->admin        = $manager;
        $this->quantity     = $quantity;
        $this->partialOrder = $partialOrder;
        $this->expireOn     = $expireOn;
    }

    /**
     * @return User
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return PartialOrder
     */
    public function getPartialOrder()
    {
        return $this->partialOrder;
    }

    public function getExpireOn()
    {
        return $this->expireOn;
    }
}
