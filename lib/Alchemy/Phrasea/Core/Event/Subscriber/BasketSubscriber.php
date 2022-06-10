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

use ACL;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Core\Event\PushEvent;
use Alchemy\Phrasea\Core\Event\ShareEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Mail\MailInfoPushReceived;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use eventsmanager_broker;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

            try {
                $receiver = Receiver::fromUser($user_to);
                $emitter = Emitter::fromUser($user_from);

                $mail = MailInfoPushReceived::create($this->app, $receiver, $emitter, $params['message'], $event->getUrl());
                $mail->setBasket($basket);
                $mail->setPusher($user_from);

                if (($locale = $user_to->getLocale()) != null) {
                    $mail->setLocale($locale);
                }
                elseif (($locale1 = $user_from->getLocale()) != null) {
                    $mail->setLocale($locale1);
                }

                $this->deliver($mail, $event->hasReceipt());

                $mailed = true;
            }
            catch (Exception $e) {
                // ignore bad emails
            }
        }

        return $this->getEventsManager()->notify($params['to'], 'eventsmanager_notify_push', $datas, $mailed);
    }

    public function onShare(ShareEvent $event)
    {
        $request = $event->getRequest();

        $payload = [
            'message_type' => MessagePublisher::SHARE_BASKET_TYPE,
            'payload' => [
                'isFeedback'            => $request->request->get('isFeedback') == "1",
                'participants'          => $request->request->get('participants'),
                'feedbackAction'        => $request->request->get('feedbackAction'),
                'shareExpires'          => $request->request->get('shareExpires'),
                'voteExpires'           => $request->request->get('voteExpires'),
                'authenticatedUserId'   => $event->getAuthenticatedUser()->getId(),
                'basketId'              => $event->getBasket()->getId(),
                'force_authentication'  => $request->get('force_authentication'),
                'send_reminder'         => $request->request->get('send_reminder'),
                'recept'                => $request->request->get('recept'),
                'notify'                => $request->request->get('notify'),
                'message'               => $request->request->get('message'),
                'duration'              => $request->request->get('duration')
            ]
        ];

        $this->getMessagePublisher()->publishMessage($payload, MessagePublisher::SHARE_BASKET_TYPE);
    }

    public static function getSubscribedEvents()
    {
        return [
            /** @uses onPush */
            PhraseaEvents::BASKET_PUSH => 'onPush',
            /** @uses onShare */
            PhraseaEvents::BASKET_SHARE => 'onShare',
        ];
    }

    /**
     * @param User $user
     * @return ACL
     */
    public function getAclForUser(User $user)
    {
        $aclProvider = $this->getAclProvider();

        return $aclProvider->get($user);
    }

    /**
     * @return ACLProvider
     */
    public function getAclProvider()
    {
        return $this->app['acl'];
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->app['dispatcher'];
    }

    /**
     * @return eventsmanager_broker
     */
    private function getEventsManager()
    {
        return $this->app['events-manager'];
    }

    /**
     * @return MessagePublisher
     */
    private function getMessagePublisher()
    {
        return $this->app['alchemy_worker.message.publisher'];
    }
}
