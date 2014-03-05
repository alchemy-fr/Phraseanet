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

use Alchemy\Phrasea\Core\Event\ValidationEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Mail\MailInfoValidationDone;
use Alchemy\Phrasea\Notification\Mail\MailInfoValidationReminder;
use Alchemy\Phrasea\Notification\Mail\MailInfoValidationRequest;
use Alchemy\Phrasea\Notification\Receiver;

class ValidationSubscriber extends AbstractNotificationSubscriber
{
    public function onCreate(ValidationEvent $event)
    {
        $params = [
            'from'    => $event->getBasket()->getValidation()->getInitiator()->getId(),
            'to'      => $event->getParticipant()->getUser()->getId(),
            'message' => $event->getMessage(),
            'ssel_id' => $event->getBasket()->getId(),
        ];

        $datas = json_encode($params);

        $mailed = false;

        if ($this->shouldSendNotificationFor($event->getParticipant()->getUser(), 'eventsmanager_notify_validate')) {
            try {
                $user_from = $event->getBasket()->getValidation()->getInitiator();
                $user_to = $event->getParticipant()->getUser()->getId();

                $basket = $event->getBasket();
                $title = $basket->getName();

                $receiver = Receiver::fromUser($user_to);
                $emitter = Emitter::fromUser($user_from);

                $readyToSend = true;
            } catch (\Exception $e) {
                $readyToSend = false;
            }

            if ($readyToSend) {
                $mail = MailInfoValidationRequest::create($this->app, $receiver, $emitter, $params['message']);
                $mail->setButtonUrl($event->getUrl());
                $mail->setDuration($event->getDuration());
                $mail->setTitle($title);
                $mail->setUser($user_from);

                $this->app['notification.deliverer']->deliver($mail, $event->hasReceipt());
                $mailed = true;
            }
        }

        return $this->app['events-manager']->notify($params['to'], 'eventsmanager_notify_validate', $datas, $mailed);
    }

    public function onFinish(ValidationEvent $event)
    {
        $params = [
            'from'    => $event->getParticipant()->getUser()->getId(),
            'to'      => $event->getBasket()->getValidation()->getInitiator()->getId(),
            'ssel_id' => $event->getBasket()->getId(),
        ];

        $datas = json_encode($params);

        $mailed = false;

        if ($this->shouldSendNotificationFor($event->getBasket()->getValidation()->getInitiator(), 'eventsmanager_notify_validationdone')) {
            $readyToSend = false;
            try {
                $user_from = $event->getParticipant()->getUser();
                $user_to = $event->getBasket()->getValidation()->getInitiator();

                $basket = $event->getBasket();
                $title = $basket->getName();

                $receiver = Receiver::fromUser($user_to);
                $emitter = Emitter::fromUser($user_from);

                $readyToSend = true;
            } catch (\Exception $e) {

            }

            if ($readyToSend) {
                $mail = MailInfoValidationDone::create($this->app, $receiver, $emitter);
                $mail->setButtonUrl($event->getUrl());
                $mail->setTitle($title);
                $mail->setUser($user_from);

                $this->app['notification.deliverer']->deliver($mail);
                $mailed = true;
            }
        }

        return $this->app['events-manager']->notify($params['to'], 'eventsmanager_notify_validationdone', $datas, $mailed);
    }

    public function onRemind(ValidationEvent $event)
    {
        $params = [
            'from'    => $event->getBasket()->getValidation()->getInitiator()->getId(),
            'to'      => $event->getParticipant()->getUser()->getId(),
            'ssel_id' => $event->getBasket()->getId(),
            'url'     => $event->getUrl(),
        ];

        $datas = json_encode($params);

        $mailed = false;

        $user_from = $event->getBasket()->getValidation()->getInitiator();
        $user_to = $event->getParticipant()->getUser();

        if ($this->shouldSendNotificationFor($event->getParticipant()->getUser(), 'eventsmanager_notify_validationreminder')) {
            $readyToSend = false;
            try {
                $basket = $event->getBasket();
                $title = $basket->getName();

                $receiver = Receiver::fromUser($user_to);
                $emitter = Emitter::fromUser($user_from);

                $readyToSend = true;
            } catch (\Exception $e) {

            }

            if ($readyToSend) {
                $mail = MailInfoValidationReminder::create($this->app, $receiver, $emitter);
                $mail->setButtonUrl($params['url']);
                $mail->setTitle($title);

                $this->app['notification.deliverer']->deliver($mail);
                $mailed = true;
            }
        }

        return $this->app['events-manager']->notify($params['to'], 'eventsmanager_notify_validationreminder', $datas, $mailed);
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::VALIDATION_CREATE => 'onCreate',
            PhraseaEvents::VALIDATION_DONE => 'onFinish',
            PhraseaEvents::VALIDATION_REMINDER => 'onRemind',
        ];
    }
}
