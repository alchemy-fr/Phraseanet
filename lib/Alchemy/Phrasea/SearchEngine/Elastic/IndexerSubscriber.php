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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to events changing index.
 * Be careful, this does not flush queue on its own, and flushQueue should be added as listener separately.
 */
class IndexerSubscriber implements EventSubscriberInterface
{
    /** @var callable|Indexer */
    private $indexer;

    /**
     * @param callable|Indexer $indexer The indexer locator
     */
    public function __construct($indexer)
    {
        if (!$indexer instanceof Indexer && !is_callable($indexer)) {
            throw new \InvalidArgumentException(sprintf(
                'Expects $indexer to be a callable or %s, got %s.',
                Indexer::class,
                is_object($indexer) ? get_class($indexer) : gettype($indexer)
            ));
        }

        $this->indexer = $indexer;
    }

    /** @return Indexer */
    public function getIndexer()
    {
        if ($this->indexer instanceof Indexer) {
            return $this->indexer;
        }

        $indexer = call_user_func($this->indexer);

        if (!$indexer instanceof Indexer) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of %s, got %s.',
                Indexer::class,
                is_object($indexer) ? get_class($indexer) : gettype($indexer)
            ));
        }

        $this->indexer = $indexer;

        return $this->indexer;
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
        ];
    }

    public function onStructureChange(RecordStructureEvent $event)
    {
        $databox = $event->getDatabox();
        $this->getIndexer()->migrateMappingForDatabox($databox);
    }

    public function onCollectionChange(CollectionEvent $event)
    {
        $collection = $event->getCollection();
        $this->getIndexer()->scheduleRecordsFromCollectionForIndexing($collection);
    }

    public function onRecordChange(RecordEvent $event)
    {
        if ($event instanceof RecordSubDefinitionCreatedEvent && $event->getSubDefinitionName() !== 'thumbnail') {
            return;
        }
        $record = $event->getRecord();
        $this->getIndexer()->indexRecord($record);
    }

    public function onRecordDelete(RecordDeletedEvent $event)
    {
        $record = $event->getRecord();
        $this->getIndexer()->deleteRecord($record);
    }

    public function flushQueue()
    {
        $this->getIndexer()->flushQueue();
    }
}
