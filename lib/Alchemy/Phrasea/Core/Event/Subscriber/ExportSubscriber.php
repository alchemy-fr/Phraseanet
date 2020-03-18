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

use Alchemy\Phrasea\Core\Event\ExportFailureEvent;
use Alchemy\Phrasea\Core\Event\ExportMailEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Mail\MailRecordsExport;
use Alchemy\Phrasea\Notification\Receiver;

class ExportSubscriber extends AbstractNotificationSubscriber
{
    public function onMailExportFailure(ExportFailureEvent $event)
    {
        $params = [
            'usr_id' => $event->getUser()->getId(),
            'lst'    => $event->getList(),
            'ssttid' => $event->getBasketId(),
            'dest'   => $event->getTarget(),
            'reason' => $event->getReason(),
        ];

        $datas = json_encode($params);

        $mailed = false;

        if ($this->shouldSendNotificationFor($event->getUser(), 'eventsmanager_notify_downloadmailfail')) {
            if (parent::email()) {
                $mailed = true;
            }
        }

        $this->app['event-manager']->notify($params['usr_id'], 'eventsmanager_notify_downloadmailfail', $datas, $mailed);
    }

    public function onCreateExportMail(ExportMailEvent $event)
    {
        $destMails = $event->getDestinationMails();

        $params = $event->getParams();

        /** @var UserRepository $userRepository */
        $userRepository = $this->app['repo.users'];

        $user = $userRepository->find($event->getEmitterUserId());

        /** @var TokenRepository $tokenRepository */
        $tokenRepository = $this->app['repo.tokens'];

        /** @var Token $token */
        $token = $tokenRepository->findValidToken($event->getTokenValue());

        $list = unserialize($token->getData());

        //zip documents
        \set_export::build_zip(
            $this->app,
            $token,
            $list,
            $this->app['tmp.download.path'].'/'. $token->getValue() . '.zip'
        );

        $remaingEmails = $destMails;

        $emitter = new Emitter($user->getDisplayName(), $user->getEmail());

        foreach ($destMails as $key => $mail) {
            try {
                $receiver = new Receiver(null, trim($mail));
            } catch (InvalidArgumentException $e) {
                continue;
            }

            $mail = MailRecordsExport::create($this->app, $receiver, $emitter, $params['textmail']);
            $mail->setButtonUrl($params['url']);
            $mail->setExpiration($token->getExpiration());

            $this->deliver($mail, $params['reading_confirm']);
            unset($remaingEmails[$key]);
        }

        //some mails failed
        if (count($remaingEmails) > 0) {
            foreach ($remaingEmails as $mail) {
                $this->app['dispatcher']->dispatch(PhraseaEvents::EXPORT_MAIL_FAILURE, new ExportFailureEvent($user, $params['ssttid'], $params['lst'], \eventsmanager_notify_downloadmailfail::MAIL_FAIL, $mail));
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::EXPORT_MAIL_FAILURE => 'onMailExportFailure',
            PhraseaEvents::EXPORT_MAIL_CREATE  => 'onCreateExportMail',
        ];
    }
}
