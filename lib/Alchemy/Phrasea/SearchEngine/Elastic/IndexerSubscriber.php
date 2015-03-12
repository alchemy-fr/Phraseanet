<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\Core\Event\Collection\CollectionEvent;
use Alchemy\Phrasea\Core\Event\Collection\CollectionEvents;
use Alchemy\Phrasea\Core\Event\Record\RecordDeletedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\RecordSubDefinitionCreatedEvent;
use Alchemy\Phrasea\Core\Event\Record\Structure\RecordStructureEvent;
use Alchemy\Phrasea\Core\Event\Record\Structure\RecordStructureEvents;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordQueuer;
use Elasticsearch\Client;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class IndexerSubscriber implements EventSubscriberInterface
{
    private $indexer;

    public function __construct(Indexer $indexer)
    {
        $this->indexer = $indexer;
    }

    public static function getSubscribedEvents()
    {
        return [
            RecordStructureEvents::FIELD_UPDATED => 'onStructureChange',
            RecordStructureEvents::FIELD_DELETED => 'onStructureChange',
            RecordStructureEvents::STATUS_BIT_UPDATED => 'onStructureChange',
            RecordStructureEvents::STATUS_BIT_DELETED => 'onStructureChange',
            CollectionEvents::NAME_CHANGED => 'onCollectionChange',
            RecordEvents::CREATED => 'onRecordChange',
            RecordEvents::DELETED => 'onRecordDelete',
            RecordEvents::COLLECTION_CHANGED => 'onRecordChange',
            RecordEvents::METADATA_CHANGED => 'onRecordChange',
            RecordEvents::ORIGINAL_NAME_CHANGED => 'onRecordChange',
            RecordEvents::STATUS_CHANGED => 'onRecordChange',
            RecordEvents::SUB_DEFINITION_CREATED => 'onRecordChange',
            RecordEvents::MEDIA_SUBSTITUTED => 'onRecordChange',
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onStructureChange(RecordStructureEvent $event)
    {
        $databox = $event->getDatabox();
        $this->indexer->migrateMappingForDatabox($databox);
    }

    public function onCollectionChange(CollectionEvent $event)
    {
        $collection = $event->getCollection();
        $this->indexer->scheduleRecordsFromCollectionForIndexing($collection);
    }

    public function onRecordChange(RecordEvent $event)
    {
        if ($event instanceof RecordSubDefinitionCreatedEvent && $event->getSubDefinitionName() !== 'thumbnail') {
            return;
        }
        $record = $event->getRecord();
        $this->indexer->indexRecord($record);
    }

    public function onRecordDelete(RecordDeletedEvent $event)
    {
        $record = $event->getRecord();
        $this->indexer->deleteRecord($record);
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        // TODO flush queue synchronously in CLI (think task manager)
        $this->indexer->flushQueue();
    }
}
