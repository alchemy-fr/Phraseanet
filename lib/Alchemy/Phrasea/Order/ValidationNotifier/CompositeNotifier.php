<?php

namespace Alchemy\Phrasea\Order\ValidationNotifier;

use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Order\OrderDelivery;
use Alchemy\Phrasea\Order\ValidationNotifier;
use Assert\Assertion;

class CompositeNotifier implements ValidationNotifier
{

    /**
     * @var ValidationNotifier[]
     */
    private $notifiers = [];

    public function __construct(array $notifiers)
    {
        Assertion::allIsInstanceOf($notifiers, ValidationNotifier::class);

        $this->notifiers = $notifiers;
    }

    /**
     * @param Order $order
     * @param User $recipient
     */
    public function notifyCreation(Order $order, User $recipient)
    {
        foreach ($this->notifiers as $notifier) {
            $notifier->notifyCreation($order, $recipient);
        }
    }

    /**
     * @param OrderDelivery $delivery
     */
    public function notifyDelivery(OrderDelivery $delivery)
    {
        foreach ($this->notifiers as $notifier) {
            $notifier->notifyDelivery($delivery);
        }
    }

    /**
     * @param OrderDelivery $delivery
     */
    public function notifyDenial(OrderDelivery $delivery)
    {
        foreach ($this->notifiers as $notifier) {
            $notifier->notifyDenial($delivery);
        }
    }
}
