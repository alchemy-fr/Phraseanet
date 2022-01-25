<?php

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Record\CollectionChangedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\StatusChangedEvent;
use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\RecordInterface;
use Assert\Assertion;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WebhookRecordEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var callable
     */
    private $appboxLocator;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->app = $application;
        $this->appboxLocator = new LazyLocator($this->app, 'phraseanet.appbox');
    }

    public function onRecordCreated(RecordEvent $event)
    {
        $this->createWebhookEvent($event, WebhookEvent::RECORD_CREATED);
    }

    public function onRecordEdit(RecordEdit $event)
    {
        $record = $this->convertToRecordAdapter($event->getRecord());

        if ($record !== null) {
            $eventData = [
                'databox_id'        => $event->getRecord()->getDataboxId(),
                'record_id'         => $event->getRecord()->getRecordId(),
                'collection_name'   => $record->getCollection()->get_name(),
                'base_id'           => $record->getBaseId(),
                'record_type'       => $event->getRecord()->isStory() ? "story" : "record",
                'description'       => [
                    'before'    =>  $event->getPrevousDescription(),
                    'after'     =>  $record->getRecordDescriptionAsArray()
                ]
            ];

            $this->app['manipulator.webhook-event']->create(
                WebhookEvent::RECORD_EDITED,
                WebhookEvent::RECORD_TYPE,
                $eventData,
                [$event->getRecord()->getBaseId()]
            );
        } else {
            $this->app['logger']->error("Record not found when wanting to create webhook data!");
        }
    }

    public function onRecordDeleted(RecordEvent $event)
    {
        $this->createWebhookEvent($event, WebhookEvent::RECORD_DELETED);
    }

    public function onRecordMediaSubstituted(RecordEvent $event)
    {
        // event only from record_type = record
        $this->createWebhookEvent($event, WebhookEvent::RECORD_MEDIA_SUBSTITUTED);
    }

    public function onRecordCollectionChanged(CollectionChangedEvent $event)
    {
        $record = $this->convertToRecordAdapter($event->getRecord());

        if ($record !== null) {
            $eventData = [
                'databox_id'        => $event->getRecord()->getDataboxId(),
                'record_id'         => $event->getRecord()->getRecordId(),
                'collection_name'   => $record->getCollection()->get_name(),
                'base_id'           => $record->getBaseId(),
                'record_type'       => $event->getRecord()->isStory() ? "story" : "record",
                'before'            => $event->getBeforeCollection(),
                'after'             => $event->getAfterCollection()
            ];

            $this->app['manipulator.webhook-event']->create(
                WebhookEvent::RECORD_COLLECTION_CHANGED,
                WebhookEvent::RECORD_TYPE,
                $eventData,
                [$event->getRecord()->getBaseId()]
            );
        } else {
            $this->app['logger']->error("Record not found when wanting to create webhook data!");
        }
    }

    public function onRecordStatusChanged(StatusChangedEvent $event)
    {
        $record = $this->convertToRecordAdapter($event->getRecord());

        if ($record !== null) {
            $eventData = [
                'databox_id'        => $event->getRecord()->getDataboxId(),
                'record_id'         => $event->getRecord()->getRecordId(),
                'collection_name'   => $record->getCollection()->get_name(),
                'base_id'           => $record->getBaseId(),
                'record_type'       => $event->getRecord()->isStory() ? "story" : "record",
                'before'            => $event->getStatusBefore(),
                'after'             => $event->getStatusAfter()
            ];

            $this->app['manipulator.webhook-event']->create(
                WebhookEvent::RECORD_STATUS_CHANGED,
                WebhookEvent::RECORD_TYPE,
                $eventData,
                [$event->getRecord()->getBaseId()]
            );
        } else {
            $this->app['logger']->error("Record not found when wanting to create webhook data!");
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            RecordEvents::CREATED               =>       'onRecordCreated',             /** @uses onRecordCreated */
            PhraseaEvents::RECORD_EDIT          =>       'onRecordEdit',                /** @uses onRecordEdit */
            RecordEvents::DELETED               =>       'onRecordDeleted',             /** @uses onRecordDeleted */
            RecordEvents::MEDIA_SUBSTITUTED     =>       'onRecordMediaSubstituted',    /** @uses onRecordMediaSubstituted */
            RecordEvents::COLLECTION_CHANGED    =>       'onRecordCollectionChanged',   /** @uses onRecordCollectionChanged */
            RecordEvents::STATUS_CHANGED        =>       'onRecordStatusChanged',       /** @uses onRecordStatusChanged */
        ];
    }

    private function createWebhookEvent(RecordEvent $event, $webhookEventName)
    {
        $record = $this->convertToRecordAdapter($event->getRecord());

        if ($record !== null) {
            $eventData = [
                'databox_id'        => $event->getRecord()->getDataboxId(),
                'record_id'         => $event->getRecord()->getRecordId(),
                'collection_name'   => $record->getCollection()->get_name(),
                'base_id'           => $record->getBaseId(),
                'record_type'       => $event->getRecord()->isStory() ? "story" : "record"
            ];

            $this->app['manipulator.webhook-event']->create(
                $webhookEventName,
                WebhookEvent::RECORD_TYPE,
                $eventData,
                [$event->getRecord()->getBaseId()]
            );
        } else {
            $this->app['logger']->error("Record not found when wanting to create webhook data!");
        }
    }

    private function convertToRecordAdapter(RecordInterface $record)
    {
        if ($record instanceof \record_adapter) {
            return $record;
        }

        $databox = $this->getApplicationBox()->get_databox($record->getDataboxId());

        $recordAdapter = $databox->getRecordRepository()->find($record->getRecordId());

        return ($recordAdapter !== null) ? $recordAdapter : null;
    }

    /**
     * @return \appbox
     */
    private function getApplicationBox()
    {
        $callable = $this->appboxLocator;

        return $callable();
    }
}