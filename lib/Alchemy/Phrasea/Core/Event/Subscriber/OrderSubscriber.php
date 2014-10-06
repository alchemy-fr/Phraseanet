<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\OrderDeliveryEvent;
use Alchemy\Phrasea\Core\Event\OrderEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Mail\MailInfoNewOrder;
use Alchemy\Phrasea\Notification\Mail\MailInfoOrderCancelled;
use Alchemy\Phrasea\Notification\Mail\MailInfoOrderDelivered;
use Alchemy\Phrasea\Notification\Receiver;

class OrderSubscriber extends AbstractNotificationSubscriber
{
    public function onCreate(OrderEvent $event)
    {
        $params = [
            'usr_id'   => $event->getOrder()->getUser()->getId(),
            'order_id' => $event->getOrder()->getId(),
        ];

        $base_ids = array_unique(array_map(function (OrderElement $element) {
            return $element->getBaseId();
        }, iterator_to_array($event->getOrder()->getElements())));

        $query = $this->app['phraseanet.user-query'];
        $users = $query->on_base_ids($base_ids)
            ->who_have_right(['order_master'])
            ->execute()->get_results();

        if (count($users) == 0) {
            return;
        }

        $datas = json_encode($params);

        $orderInitiator = $event->getOrder()->getUser();

        foreach ($users as $user) {
            $mailed = false;

            if ($this->shouldSendNotificationFor($user, 'eventsmanager_notify_order')) {
                try {
                    $receiver = Receiver::fromUser($user);
                } catch (\Exception $e) {
                    continue;
                }

                $mail = MailInfoNewOrder::create($this->app, $receiver);
                $mail->setUser($orderInitiator);

                $this->app['notification.deliverer']->deliver($mail);
                $mailed = true;
            }

            $this->app['events-manager']->notify($user->getId(), 'eventsmanager_notify_order', $datas, $mailed);
        }
    }

    public function onDeliver(OrderDeliveryEvent $event)
    {
        $params = [
            'from'    => $event->getAdmin()->getId(),
            'to'      => $event->getOrder()->getUser()->getId(),
            'ssel_id' => $event->getOrder()->getBasket()->getId(),
            'n'       => $event->getQuantity(),
        ];

        $datas = json_encode($params);

        $mailed = false;

        if ($this->shouldSendNotificationFor($event->getOrder()->getUser(), 'eventsmanager_notify_orderdeliver')) {
            $user_from = $event->getAdmin();
            $user_to = $event->getOrder()->getUser();

            $receiver = Receiver::fromUser($event->getOrder()->getUser());
            $emitter = Emitter::fromUser($event->getAdmin());

            $basket = $event->getOrder()->getBasket();

            $url = $this->app->url('lightbox_compare', [
                'basket' => $basket->getId(),
                'LOG' => $this->app['manipulator.token']->createBasketAccessToken($basket, $user_to)->getValue(),
            ]);

            $mail = MailInfoOrderDelivered::create($this->app, $receiver, $emitter, null);
            $mail->setButtonUrl($url);
            $mail->setBasket($basket);
            $mail->setDeliverer($user_from);

            $this->app['notification.deliverer']->deliver($mail);
            $mailed = true;
        }

        return $this->app['events-manager']->notify($params['to'], 'eventsmanager_notify_orderdeliver', $datas, $mailed);
    }

    public function onDeny(OrderDeliveryEvent $event)
    {
        $params = [
            'from' => $event->getAdmin()->getId(),
            'to'   => $event->getOrder()->getUser()->getId(),
            'n'    => $event->getQuantity(),
        ];

        $datas = json_encode($params);

        $mailed = false;

        if ($this->shouldSendNotificationFor($event->getOrder()->getUser(), 'eventsmanager_notify_ordernotdelivered')) {
            $user_from = $event->getAdmin();
            $user_to = $event->getOrder()->getUser();

            $receiver = Receiver::fromUser($user_to);
            $emitter = Emitter::fromUser($user_from);

            $mail = MailInfoOrderCancelled::create($this->app, $receiver, $emitter);
            $mail->setQuantity($params['n']);
            $mail->setDeliverer($user_from);

            $this->app['notification.deliverer']->deliver($mail);

            $mailed = true;
        }

        return $this->app['events-manager']->notify($params['to'], 'eventsmanager_notify_ordernotdelivered', $datas, $mailed);
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::ORDER_CREATE => 'onCreate',
            PhraseaEvents::ORDER_DELIVER => 'onDeliver',
            PhraseaEvents::ORDER_DENY => 'onDeny',
        ];
    }
}
