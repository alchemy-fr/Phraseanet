<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order\ValidationNotifier;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Notification\Deliverer;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Mail\MailInfoNewOrder;
use Alchemy\Phrasea\Notification\Mail\MailInfoOrderCancelled;
use Alchemy\Phrasea\Notification\Mail\MailInfoOrderDelivered;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Order\OrderDelivery;
use Alchemy\Phrasea\Order\ValidationNotifier;

class MailNotifier implements ValidationNotifier
{
    /**
     * @var Application
     */
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @return Deliverer
     */
    private function getDeliverer()
    {
        return $this->application['notification.deliverer'];
    }

    /**
     * @param Order $order
     * @param User $recipient
     */
    public function notifyCreation(Order $order, User $recipient)
    {
        $mail = MailInfoNewOrder::create($this->application, Receiver::fromUser($recipient));

        $mail->setUser($order->getUser());

        $this->getDeliverer()->deliver($mail);
    }

    /**
     * @param OrderDelivery $delivery
     */
    public function notifyDelivery(OrderDelivery $delivery)
    {
        $order = $delivery->getOrder();

        $recipient = Receiver::fromUser($order->getUser());
        $sender = Emitter::fromUser($delivery->getAdmin());

        $basket = $order->getBasket();
        $token = $this->application['manipulator.token']->createBasketAccessToken($basket, $order->getUser());

        $url = $this->application->url('lightbox_compare', [
            'basket' => $basket->getId(),
            'LOG' => $token->getValue(),
        ]);

        $mail = MailInfoOrderDelivered::create($this->application, $recipient, $sender, null);

        $mail->setButtonUrl($url);
        $mail->setBasket($basket);
        $mail->setDeliverer($delivery->getAdmin());

        $this->getDeliverer()->deliver($mail);
    }

    /**
     * @param OrderDelivery $delivery
     */
    public function notifyDenial(OrderDelivery $delivery)
    {
        $sender = Emitter::fromUser($delivery->getAdmin());
        $recipient = Receiver::fromUser($delivery->getOrder()->getUser());

        $mail = MailInfoOrderCancelled::create($this->application, $recipient, $sender);

        $mail->setQuantity($delivery->getQuantity());
        $mail->setDeliverer($delivery->getAdmin());

        $this->getDeliverer()->deliver($mail);
    }
}
