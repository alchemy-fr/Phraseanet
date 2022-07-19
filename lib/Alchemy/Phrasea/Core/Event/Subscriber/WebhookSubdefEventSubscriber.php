<?php

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionCreatedEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionCreationFailedEvent;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\RecordInterface;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WebhookSubdefEventSubscriber implements EventSubscriberInterface
{
    private $app;

    /**
     * @var callable
     */
    private $appboxLocator;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->appboxLocator = new LazyLocator($this->app, 'phraseanet.appbox');
    }

    public function onSubdefCreated(SubDefinitionCreatedEvent $event)
    {
        $record = $this->convertToRecordAdapter($event->getRecord());

        if ($record->has_subdef($event->getSubDefinitionName())) {
            $subdef = $record->get_subdef($event->getSubDefinitionName());

            try {
                $url = $subdef->get_permalink()->get_url()->__toString();
            } catch (\Exception $e) {
                $url = '';
            } catch (\Throwable $e) {
                $url = '';
            }

            $size = $subdef->get_size();
            $type = $subdef->get_mime();
        } else {
            $url  = '';
            $size = 0;
            $type = '';
        }

        $eventData = [
            'databox_id'    => $record->getDataboxId(),
            'record_id'     => $record->getRecordId(),
            'collection_name'   => $record->getCollection()->get_name(),
            'base_id'           => $record->getBaseId(),
            'subdef_name'   => $event->getSubDefinitionName(),
            'permalink'     => $url,
            'original_name' => $record->getOriginalName(),
            'size'          => $size,
            'type'          => $type,
        ];

        $this->app['manipulator.webhook-event']->create(
            WebhookEvent::RECORD_SUBDEF_CREATED,
            WebhookEvent::RECORD_SUBDEF_TYPE,
            $eventData,
            [$event->getRecord()->getBaseId()]
        );
    }

    public function onSubdefCreationFailed(SubDefinitionCreationFailedEvent $event)
    {
        $record = $this->convertToRecordAdapter($event->getRecord());

        $eventData = [
            'databox_id'    => $record->getDataboxId(),
            'record_id'     => $record->getRecordId(),
            'collection_name'   => $record->getCollection()->get_name(),
            'base_id'           => $record->getBaseId(),
            'subdef_name'   => $event->getSubDefinitionName()
        ];

        $this->app['manipulator.webhook-event']->create(
            WebhookEvent::RECORD_SUBDEF_FAILED,
            WebhookEvent::RECORD_SUBDEF_TYPE,
            $eventData,
            [$event->getRecord()->getBaseId()]
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            RecordEvents::SUB_DEFINITION_CREATED            => 'onSubdefCreated',
            RecordEvents::SUB_DEFINITION_CREATION_FAILED    => 'onSubdefCreationFailed'
        ];
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
