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

use Alchemy\Phrasea\Core\Event\FeedEntryEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailInfoNewPublication;

class FeedEntrySubscriber extends AbstractNotificationSubscriber
{
    public function onCreate(FeedEntryEvent $event)
    {
        $entry = $event->getFeedEntry();

        $params = [
            'entry_id' => $entry->getId(),
            'notify_email' => $event->hasEmailNotification(),
        ];

        $this->app['manipulator.webhook-event']->create(
            WebhookEvent::NEW_FEED_ENTRY,
            WebhookEvent::FEED_ENTRY_TYPE,
            array_merge(array('feed_id' => $entry->getFeed()->getId()), $params)
        );

        $datas = json_encode($params);

        $Query = $this->app['phraseanet.user-query'];

        $Query->include_phantoms(true)
            ->include_invite(false)
            ->include_templates(false)
            ->email_not_null(true);

        if ($entry->getFeed()->getCollection($this->app)) {
            $Query->on_base_ids([$entry->getFeed()->getCollection($this->app)->get_base_id()]);
        }

        $start = 0;
        $perLoop = 100;

        do {
            $results = $Query->limit($start, $perLoop)->execute()->get_results();

            foreach ($results as $user_to_notif) {
                $mailed = false;

                if ($params['notify_email'] && $this->shouldSendNotificationFor($user_to_notif, 'eventsmanager_notify_feed')) {
                    $readyToSend = false;
                    try {
                        $token = $this->app['manipulator.token']->createFeedEntryToken($user_to_notif, $entry);
                        $url = $this->app->url('lightbox', ['LOG' => $token->getValue()]);

                        $receiver = Receiver::fromUser($user_to_notif);
                        $readyToSend = true;
                    } catch (\Exception $e) {

                    }

                    if ($readyToSend) {
                        $mail = MailInfoNewPublication::create($this->app, $receiver);
                        $mail->setButtonUrl($url);
                        $mail->setAuthor($entry->getAuthorName());
                        $mail->setTitle($entry->getTitle());

                        $this->app['notification.deliverer']->deliver($mail);
                        $mailed = true;
                    }
                }

                $this->app['events-manager']->notify($user_to_notif->getId(), 'eventsmanager_notify_feed', $datas, $mailed);
            }
            $start += $perLoop;
        } while (count($results) > 0);
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::FEED_ENTRY_CREATE => 'onCreate',
        ];
    }
}
