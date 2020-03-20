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

interface ValidationNotifier
{
    /**
     * @param Order $order
     * @param User $recipient
     * @param array $baseIds
     * @return void
     */
    public function notifyCreation(Order $order, User $recipient, array $baseIds = array());

    /**
     * @param OrderDelivery $delivery
     * @param array $baseIds
     * @return void
     */
    public function notifyDelivery(OrderDelivery $delivery, array $baseIds = array());

    /**
     * @param OrderDelivery $delivery
     * @param array $baseIds
     * @return void
     */
    public function notifyDenial(OrderDelivery $delivery, array $baseIds = array());

}
