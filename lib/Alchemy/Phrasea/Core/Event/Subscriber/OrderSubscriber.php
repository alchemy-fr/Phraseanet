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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\OrderDeliveryEvent;
use Alchemy\Phrasea\Core\Event\OrderEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Order\ValidationNotifier\CompositeNotifier;
use Alchemy\Phrasea\Order\ValidationNotifierRegistry;

class OrderSubscriber extends AbstractNotificationSubscriber
{
    /**
     * @var ValidationNotifierRegistry
     */
    private $notifierRegistry;

    /**
     * @param Application $application
     * @param ValidationNotifierRegistry $notifierRegistry
     */
    public function __construct(Application $application, ValidationNotifierRegistry $notifierRegistry)
    {
        parent::__construct($application);

        $this->notifierRegistry = $notifierRegistry;
    }

    public function onCreate(OrderEvent $event)
    {
        $base_ids = array_unique(array_map(function (OrderElement $element) {
            return $element->getBaseId();
        }, iterator_to_array($event->getOrder()->getElements())));

        $query = $this->app['phraseanet.user-query'];
        /** @var User[] $users */
        $users = $query->on_base_ids($base_ids)
            ->who_have_right([\ACL::ORDER_MASTER])
            ->execute()->get_results();

        if (count($users) == 0) {
            return;
        }

        $notificationData = json_encode([
            'usr_id'   => $event->getOrder()->getUser()->getId(),
            'order_id' => $event->getOrder()->getId(),
        ]);

        $notifier = $this->notifierRegistry->getNotifier($event->getOrder()->getNotificationMethod());

        $notifier->notifyCreation($event->getOrder(), $event->getOrder()->getUser());

        $notifier = $this->notifierRegistry->getNotifier(Order::NOTIFY_MAIL);

        foreach ($users as $user) {
            $notified = false;

            if ($this->shouldSendNotificationFor($user, 'eventsmanager_notify_order')) {
                try {
                    $notifier->notifyCreation($event->getOrder(), $user);
                } catch (\Exception $e) {
                    continue;
                }

                $notified = true;
            }

            $this->app['events-manager']->notify($user->getId(), 'eventsmanager_notify_order', $notificationData, $notified);
        }
    }

    public function onDeliver(OrderDeliveryEvent $event)
    {
        $notified = false;
        $notifier = $this->notifierRegistry->getNotifier($event->getOrder()->getNotificationMethod());
        $notificationData = json_encode([
            'from'    => $event->getDelivery()->getAdmin()->getId(),
            'to'      => $event->getOrder()->getUser()->getId(),
            'ssel_id' => $event->getOrder()->getBasket()->getId(),
            'n'       => $event->getDelivery()->getQuantity()
        ]);

        if ($this->shouldSendNotificationFor($event->getOrder()->getUser(), 'eventsmanager_notify_orderdeliver')) {
            $notifier->notifyDelivery($event->getDelivery());
            $notified = true;
        }

        return $this->app['events-manager']->notify(
            $event->getOrder()->getUser()->getId(),
            'eventsmanager_notify_orderdeliver',
            $notificationData,
            $notified
        );
    }

    public function onDeny(OrderDeliveryEvent $event)
    {
        $notified = false;
        $notifier = $this->notifierRegistry->getNotifier($event->getOrder()->getNotificationMethod());
        $notificationData = json_encode([
            'from' => $event->getDelivery()->getAdmin()->getId(),
            'to'   => $event->getOrder()->getUser()->getId(),
            'n'    => $event->getDelivery()->getQuantity()
        ]);


        if ($this->shouldSendNotificationFor($event->getOrder()->getUser(), 'eventsmanager_notify_ordernotdelivered')) {
            $notifier->notifyDenial($event->getDelivery());
            $notified = true;
        }

        return $this->app['events-manager']->notify(
            $event->getOrder()->getUser()->getId(),
            'eventsmanager_notify_ordernotdelivered',
            $notificationData,
            $notified
        );
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
