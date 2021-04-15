<?php

namespace Alchemy\Phrasea\WorkerManager\Subscriber;

use Alchemy\Phrasea\Core\Event\ExportMailEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\WorkerManager\Event\ExportFtpEvent;
use Alchemy\Phrasea\WorkerManager\Event\ExportMailFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExportSubscriber implements EventSubscriberInterface
{
    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    public function __construct(MessagePublisher $messagePublisher)
    {
        $this->messagePublisher = $messagePublisher;
    }

    public function onExportMailCreate(ExportMailEvent $event)
    {
        $payload = [
            'message_type' => MessagePublisher::EXPORT_MAIL_TYPE,
            'payload' => [
                'emitterUserId'     => $event->getEmitterUserId(),
                'tokenValue'        => $event->getTokenValue(),
                'destinationMails'  => serialize($event->getDestinationMails()),
                'params'            => serialize($event->getParams())
            ]
        ];

        $this->messagePublisher->publishMessage($payload, MessagePublisher::EXPORT_MAIL_TYPE);
    }

    public function onExportMailFailure(ExportMailFailureEvent $event)
    {
        $payload = [
            'message_type' => MessagePublisher::EXPORT_MAIL_TYPE,
            'payload' => [
                'emitterUserId'     => $event->getEmitterUserId(),
                'tokenValue'        => $event->getTokenValue(),
                'destinationMails'  => serialize($event->getDestinationMails()),
                'params'            => serialize($event->getParams())
            ]
        ];

        $this->messagePublisher->publishRetryMessage(
            $payload,
            MessagePublisher::EXPORT_MAIL_TYPE,
            $event->getCount(),
            $event->getWorkerMessage()
        );
    }

    public function onExportFtp(ExportFtpEvent $event)
    {
        $payload = [
            'message_type' => MessagePublisher::FTP_TYPE,
            'payload' => [
                'ftpExportId'  => $event->getFtpExportId(),
            ]
        ];

        $this->messagePublisher->publishMessage(
            $payload,
            MessagePublisher::FTP_TYPE
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::EXPORT_MAIL_CREATE   => 'onExportMailCreate',
            WorkerEvents::EXPORT_MAIL_FAILURE   => 'onExportMailFailure',
            WorkerEvents::EXPORT_FTP            => 'onExportFtp'
        ];
    }
}
