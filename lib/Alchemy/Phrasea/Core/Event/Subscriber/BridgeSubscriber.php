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

use Alchemy\Phrasea\Core\Event\BridgeUploadFailureEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailInfoBridgeUploadFailed;

class BridgeSubscriber extends AbstractNotificationSubscriber
{
    public function onUploadFailure(BridgeUploadFailureEvent $event)
    {
        $account = $event->getElement()->get_account();
        $user = $account->get_user();
        $params = [
            'usr_id'     => $user->getId(),
            'reason'     => $event->getReason(),
            'account_id' => $account->get_id(),
            'sbas_id'    => $event->getElement()->get_record()->getDataboxId(),
            'record_id'  => $event->getElement()->get_record()->getRecordId(),
        ];

        $datas = json_encode($params);

        $mailed = false;

        if ($this->shouldSendNotificationFor($user, 'eventsmanager_notify_bridgeuploadfail')) {
            try {
                $receiver = Receiver::fromUser($user);
                $readyToSend = true;
            } catch (\Exception $e) {
                $readyToSend = false;
            }

            if ($readyToSend) {
                $mail = MailInfoBridgeUploadFailed::create($this->app, $receiver);
                $mail->setAdapter($account->get_api()->get_connector()->get_name());
                $mail->setReason($params['reason']);

                if (($locale = $user->getLocale()) != null) {
                    $mail->setlocale($locale);
                }

                $this->deliver($mail);
                $mailed = true;
            }
        }

        $this->app['events-manager']->notify($params['usr_id'], 'eventsmanager_notify_bridgeuploadfail', $datas, $mailed);
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::BRIDGE_UPLOAD_FAILURE => 'onUploadFailure',
        ];
    }
}
