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
     * @param array $baseIds
     */
    public function notifyCreation(Order $order, User $recipient, array $baseIds = array())
    {
        foreach ($this->notifiers as $notifier) {
            $notifier->notifyCreation($order, $recipient);
        }
    }

    /**
     * @param OrderDelivery $delivery
     * @param array $baseIds
     */
    public function notifyDelivery(OrderDelivery $delivery, array $baseIds = array())
    {
        foreach ($this->notifiers as $notifier) {
            $notifier->notifyDelivery($delivery, $baseIds);
        }
    }

    /**
     * @param OrderDelivery $delivery
     * @param array $baseIds
     */
    public function notifyDenial(OrderDelivery $delivery, array $baseIds = array())
    {
        foreach ($this->notifiers as $notifier) {
            $notifier->notifyDenial($delivery, $baseIds);
        }
    }
}
