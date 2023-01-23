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

use Alchemy\Phrasea\Core\Event\BasketParticipantVoteEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Mail\MailInfoBasketShared;
use Alchemy\Phrasea\Notification\Mail\MailInfoValidationDone;
use Alchemy\Phrasea\Notification\Mail\MailInfoValidationRequest;
use Alchemy\Phrasea\Notification\Receiver;

class ValidationSubscriber extends AbstractNotificationSubscriber
{
    public function onCreate(BasketParticipantVoteEvent $event)
    {
        $basket = $event->getBasket();
        $user_from = $basket->isVoteBasket() ? $basket->getVoteInitiator() : $basket->getUser();

        $params = [
            'from'    => $user_from->getId(),
            'to'      => $event->getParticipant()->getUser()->getId(),
            'message' => $event->getMessage(),
            'ssel_id' => $basket->getId(),
            'isVoteBasket'  => $basket->isVoteBasket()
        ];

        $datas = json_encode($params);

        $mailed = false;

        if ($this->shouldSendNotificationFor($event->getParticipant()->getUser(), 'eventsmanager_notify_validate')) {
            $user_to = $receiver = $emitter = null;
            try {
                $user_to = $event->getParticipant()->getUser();

                $receiver = Receiver::fromUser($user_to);
                $emitter = Emitter::fromUser($user_from);

                $readyToSend = true;
            }
            catch (\Exception $e) {
                $readyToSend = false;
            }

            if ($readyToSend) {
                if($event->getIsVote() && $event->getParticipant()->getCanAgree()) {
                    // vote request
                    $mail = MailInfoValidationRequest::create($this->app, $receiver, $emitter, $params['message']);
                }
                else {
                    // simple share information
                    $mail = MailInfoBasketShared::create($this->app, $receiver, $emitter, $params['message']);
                }
                $mail->setButtonUrl($event->getUrl());
                $mail->setIsVote($event->getIsVote());
                $mail->setShareExpires($event->getShareExpires());
                $mail->setVoteExpires($event->getVoteExpires());
                $mail->setTitle($basket->getName());
                $mail->setUser($user_from);
                $mail->setParticipant($event->getParticipant());

                if (($locale = $user_to->getLocale()) != null) {
                    $mail->setLocale($locale);
                }
                elseif (($locale1 = $user_from->getLocale()) != null) {
                    $mail->setLocale($locale1);
                }

                $this->deliver($mail, $event->hasReceipt());
                $mailed = true;
            }
        }

        return $this->app['events-manager']->notify($params['to'], 'eventsmanager_notify_validate', $datas, $mailed);
    }

    public function onFinish(BasketParticipantVoteEvent $event)
    {
        $params = [
            'from'    => $event->getParticipant()->getUser()->getId(),
            'to'      => $event->getBasket()->getVoteInitiator()->getId(),
            'ssel_id' => $event->getBasket()->getId(),
        ];

        $datas = json_encode($params);

        $mailed = false;

        if ($this->shouldSendNotificationFor($event->getBasket()->getVoteInitiator(), 'eventsmanager_notify_validationdone')) {
            $readyToSend = false;
            try {
                $user_from = $event->getParticipant()->getUser();
                $user_to = $event->getBasket()->getVoteInitiator();

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

                if (($locale = $user_to->getLocale()) != null) {
                    $mail->setLocale($locale);
                } elseif (($locale1 = $user_from->getLocale()) != null) {
                    $mail->setLocale($locale1);
                }

                $this->deliver($mail);
                $mailed = true;
            }
        }

        return $this->app['events-manager']->notify($params['to'], 'eventsmanager_notify_validationdone', $datas, $mailed);
    }

    /*
     * PHRAS-3214_validation-tokens-refacto : This code is moved to console command "SendValidationRemindersCommand.php"
     *
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

                $this->deliver($mail);
                $mailed = true;
            }
        }

        return $this->app['events-manager']->notify($params['to'], 'eventsmanager_notify_validationreminder', $datas, $mailed);
    }
    */

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::VALIDATION_CREATE => 'onCreate',
            PhraseaEvents::VALIDATION_DONE => 'onFinish',
            // PHRAS-3214_validation-tokens-refacto : This code is moved to console command "SendValidationRemindersCommand.php"
            // PhraseaEvents::VALIDATION_REMINDER => 'onRemind',
        ];
    }
}
