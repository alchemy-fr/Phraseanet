<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order;

use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\User;

/**
 * Interface ValidationNotifier
 * @package Alchemy\Phrasea\Order
 */
interface ValidationNotifier
{
    /**
     * @param Order $order
     * @param User $recipient
     */
    public function notifyCreation(Order $order, User $recipient);

    /**
     * @param OrderDelivery $delivery
     */
    public function notifyDelivery(OrderDelivery $delivery);

    /**
     * @param OrderDelivery $delivery
     */
    public function notifyDenial(OrderDelivery $delivery);

}
