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
use Alchemy\Phrasea\Order\OrderDelivery;

class OrderDeliveryEvent extends OrderEvent
{
    /**
     * @var OrderDelivery
     */
    private $orderDelivery;

    /**
     * @param OrderDelivery $delivery
     */
    public function __construct(OrderDelivery $delivery)
    {
        parent::__construct($delivery->getOrder());

        $this->orderDelivery = $delivery;
    }

    /**
     * @return OrderDelivery
     */
    public function getDelivery()
    {
        return $this->orderDelivery;
    }

    /**
     * @return User
     * @deprecated Use OrderDeliveryEvent::getDelivery() to retrieve admin user.
     */
    public function getAdmin()
    {
        return $this->orderDelivery->getAdmin();
    }

    /**
     * @return int
     * @deprecated Use OrderDeliveryEvent::getDelivery() to read quantity.
     */
    public function getQuantity()
    {
        return $this->orderDelivery->getQuantity();
    }

}
