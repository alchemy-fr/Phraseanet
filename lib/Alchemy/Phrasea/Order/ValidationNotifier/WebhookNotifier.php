<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2018 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order\ValidationNotifier;

use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Manipulator\WebhookEventManipulator;
use Alchemy\Phrasea\Order\OrderDelivery;
use Alchemy\Phrasea\Order\ValidationNotifier;

class WebhookNotifier implements ValidationNotifier
{

    /**
     * @var callable
     */
    private $webhookManipulatorLocator;

    /**
     * @param callable $webhookEventManipulatorLocator
     */
    public function __construct($webhookEventManipulatorLocator)
    {
        $this->webhookManipulatorLocator = $webhookEventManipulatorLocator;
    }

    /**
     * @return WebhookEventManipulator
     */
    private function getManipulator()
    {
        $factory = $this->webhookManipulatorLocator;

        return $factory();
    }

    /**
     * @param Order $order
     * @param User $recipient
     */
    public function notifyCreation(Order $order, User $recipient)
    {
        $eventData = [
            'order_id' => $order->getId(),
            'user_id' => $recipient->getId(),
        ];

        $this->getManipulator()->create(WebhookEvent::ORDER_CREATED, WebhookEvent::ORDER_TYPE, $eventData);
    }

    /**
     * @param OrderDelivery $delivery
     */
    public function notifyDelivery(OrderDelivery $delivery)
    {
        $eventData = [
            'order_id' => $delivery->getOrder()->getId(),
            'admin_id' => $delivery->getAdmin()->getId(),
            'quantity' => $delivery->getQuantity()
        ];

        $this->getManipulator()->create(WebhookEvent::ORDER_DELIVERED, WebhookEvent::ORDER_TYPE, $eventData);
    }

    /**
     * @param OrderDelivery $delivery
     */
    public function notifyDenial(OrderDelivery $delivery)
    {
        $eventData = [
            'order_id' => $delivery->getOrder()->getId(),
            'admin_id' => $delivery->getAdmin()->getId(),
            'quantity' => $delivery->getQuantity()
        ];

        $this->getManipulator()->create(WebhookEvent::ORDER_DENIED, WebhookEvent::ORDER_TYPE, $eventData);
    }
}
