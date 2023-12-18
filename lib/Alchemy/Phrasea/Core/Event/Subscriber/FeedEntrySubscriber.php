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
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailInfoNewPublication;

class FeedEntrySubscriber extends AbstractNotificationSubscriber
{
    public function onCreate(FeedEntryEvent $event)
    {
        $params = [
            'entry_id' => $event->getFeedEntry()->getId(),
            'notify_email' => $event->hasEmailNotification(),
        ];

        $this->app['manipulator.webhook-event']->create(
            WebhookEvent::NEW_FEED_ENTRY,
            WebhookEvent::FEED_ENTRY_TYPE,
            array_merge(array('feed_id' => $event->getFeedEntry()->getFeed()->getId()), $params),
            []
        );

        $this->sendEmailNotification($event);
    }

    public function onUpdate(FeedEntryEvent $event)
    {
        $this->sendEmailNotification($event);
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::FEED_ENTRY_CREATE => 'onCreate',
            PhraseaEvents::FEED_ENTRY_UPDATE => 'onUpdate'
        ];
    }

    /**
     * @return TokenManipulator
     */
    private function getTokenManipulator()
    {
        return $this->app['manipulator.token'];
    }

    private function sendEmailNotification(FeedEntryEvent $event)
    {
        $entry = $event->getFeedEntry();

        $params = [
            'entry_id' => $entry->getId(),
            'notify_email' => $event->hasEmailNotification(),
        ];

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

            $users_emailed = [];     // for all users
            $users_to_email = [];    // list only users who must be emailed (=create tokens)

            /** @var User $user */
            foreach ($results as $user) {
                $users_emailed[$user->getId()] = false;
                if ($params['notify_email'] && $this->shouldSendNotificationFor($user, 'eventsmanager_notify_feed')) {
                    $users_to_email[$user->getId()] = $user;
                }
            }

            // get many tokens in one shot
            $tokens = $this->getTokenManipulator()->createFeedEntryTokens($users_to_email, $entry);
            foreach($tokens as $token) {
                try {
                    $url = $this->app->url('lightbox', ['LOG' => $token->getValue()]);
                    $receiver = Receiver::fromUser($token->getUser());

                    $mail = MailInfoNewPublication::create($this->app, $receiver);
                    $mail->setButtonUrl($url);
                    $mail->setAuthor($entry->getAuthorName());
                    $mail->setTitle($entry->getTitle());

                    if (($locale = $token->getUser()->getLocale()) != null) {
                        $mail->setLocale($locale);
                    }

                    $this->deliver($mail);
                    $users_emailed[$token->getUser()->getId()] = true;
                }
                catch (\Exception $e) {
                    // no-op
                }
            }
            foreach($users_emailed as $id => $emailed) {
                $this->app['events-manager']->notify($id, 'eventsmanager_notify_feed', $datas, $emailed);
            }

            $start += $perLoop;
        }
        while (count($results) > 0);
    }
}
