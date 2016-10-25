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

use Alchemy\Phrasea\Core\Event\LazaretEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\LazaretCheck;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Notification\Mail\MailInfoRecordQuarantined;
use Alchemy\Phrasea\Notification\Receiver;

class LazaretSubscriber extends AbstractNotificationSubscriber
{
    public function onCreate(LazaretEvent $event)
    {
        $lazaretFile = $event->getFile();
        $params = [
            'filename' => $lazaretFile->getOriginalName(),
            'reasons'   => array_map(function (LazaretCheck $check) {
                return $check->getCheckClassname();
            }, iterator_to_array($lazaretFile->getChecks())),
        ];

        if (null !== $user = $lazaretFile->getSession()->getUser()) {
            $params['sender'] = $user->getDisplayName();

            $this->notifyUser($user, json_encode($params));
        } else { //No lazaretSession user, fil is uploaded via automated tasks etc ..
            $query = $this->app['phraseanet.user-query'];
            $users = $query
                ->on_base_ids([$lazaretFile->getBaseId()])
                ->who_have_right([\ACL::CANADDRECORD])
                ->execute()
                ->get_results();

            foreach ($users as $user) {
                $this->notifyUser($user, json_encode($params));
            }
        }
    }

    private function notifyUser(User $user, $datas)
    {
        $mailed = false;

        if ($this->shouldSendNotificationFor($user, 'eventsmanager_notify_uploadquarantine')) {
            $readyToSend = false;
            try {
                $receiver = Receiver::fromUser($user);
                $readyToSend = true;
            } catch (\Exception $e) {

            }

            if ($readyToSend) {
                $mail = MailInfoRecordQuarantined::create($this->app, $receiver);
                $this->deliver($mail);
                $mailed = true;
            }
        }

        $this->app['events-manager']->notify($user->getId(), 'eventsmanager_notify_uploadquarantine', $datas, $mailed);
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::LAZARET_CREATE => 'onCreate',
        ];
    }
}
