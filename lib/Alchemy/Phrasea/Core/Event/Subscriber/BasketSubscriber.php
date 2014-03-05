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

use Alchemy\Phrasea\Core\Event\PushEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Mail\MailInfoPushReceived;
use Alchemy\Phrasea\Notification\Receiver;

class BasketSubscriber extends AbstractNotificationSubscriber
{
    public function onPush(PushEvent $event)
    {
        $params = [
            'from'    => $event->getBasket()->getPusher()->getId(),
            'to'      => $event->getBasket()->getUser()->getId(),
            'message' => $event->getMessage(),
            'ssel_id' => $event->getBasket()->getId(),
        ];

        $datas = json_encode($params);

        $mailed = false;

        if ($this->shouldSendNotificationFor($event->getBasket()->getUser(), 'eventsmanager_notify_push')) {
            $basket = $event->getBasket();

            $user_from = $event->getBasket()->getPusher();
            $user_to = $event->getBasket()->getUser();

            $receiver = Receiver::fromUser($user_to);
            $emitter = Emitter::fromUser($user_from);

            $mail = MailInfoPushReceived::create($this->app, $receiver, $emitter, $params['message'], $event->getUrl());
            $mail->setBasket($basket);
            $mail->setPusher($user_from);

            $this->app['notification.deliverer']->deliver($mail, $event->hasReceipt());

            $mailed = true;
        }

        return $this->app['events-manager']->notify($params['to'], 'eventsmanager_notify_push', $datas, $mailed);
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::BASKET_PUSH => 'onPush',
        ];
    }
}
