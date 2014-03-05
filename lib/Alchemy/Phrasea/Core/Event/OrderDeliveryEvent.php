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

use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\User;

class OrderDeliveryEvent extends OrderEvent
{
    private $admin;
    private $quantity;

    public function __construct(Order $order, User $admin, $quantity)
    {
        parent::__construct($order);
        $this->admin = $admin;
        $this->quantity = $quantity;
    }

    /**
     * @return User
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * @return mixed
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

}
