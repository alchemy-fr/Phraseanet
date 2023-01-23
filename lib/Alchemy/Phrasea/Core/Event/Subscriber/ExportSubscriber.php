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
                $mailed = true;
        }

        $this->app['events-manager']->notify($params['usr_id'], 'eventsmanager_notify_downloadmailfail', $datas, $mailed);
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::EXPORT_MAIL_FAILURE => 'onMailExportFailure',
        ];
    }
}
