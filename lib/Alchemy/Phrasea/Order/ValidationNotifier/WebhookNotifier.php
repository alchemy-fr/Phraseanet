<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order\ValidationNotifier;

use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Order\OrderDelivery;
use Alchemy\Phrasea\Order\ValidationNotifier;

class WebhookNotifier implements ValidationNotifier
{

    /**
     * @param Order $order
     * @param User $recipient
     */
    public function notifyCreation(Order $order, User $recipient)
    {
        // TODO: Implement notifyCreation() method.
    }

    /**
     * @param OrderDelivery $delivery
     */
    public function notifyDelivery(OrderDelivery $delivery)
    {
        // TODO: Implement notifyDelivery() method.
    }

    /**
     * @param OrderDelivery $delivery
     */
    public function notifyDenial(OrderDelivery $delivery)
    {
        // TODO: Implement notifyDenial() method.
    }
}
