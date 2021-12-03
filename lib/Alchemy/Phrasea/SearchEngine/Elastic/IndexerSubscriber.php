<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\Core\Event\Collection\CollectionEvent;
use Alchemy\Phrasea\Core\Event\Collection\CollectionEvents;
use Alchemy\Phrasea\Core\Event\Record\DeletedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\Structure\RecordStructureEvent;
use Alchemy\Phrasea\Core\Event\Record\Structure\RecordStructureEvents;
use Alchemy\Phrasea\Core\Event\Thesaurus\ReindexRequiredEvent;
use Alchemy\Phrasea\Core\Event\Thesaurus\ThesaurusEvent;
use Alchemy\Phrasea\Core\Event\Thesaurus\ThesaurusEvents;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
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

    /**
     * @return Indexer
     */
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
            WorkerEvents::RECORD_DELETE_INDEX   => 'onRecordDelete',
            RecordEvents::COLLECTION_CHANGED => 'onRecordChange',
            RecordEvents::METADATA_CHANGED => 'onRecordChange',
            RecordEvents::ORIGINAL_NAME_CHANGED => 'onRecordChange',
            RecordEvents::STATUS_CHANGED => 'onRecordChange',
            RecordEvents::SUB_DEFINITIONS_CREATED => 'onRecordChange',
            RecordEvents::MEDIA_SUBSTITUTED => 'onRecordChange',
            RecordEvents::ROTATE => 'onRecordChange',
            ThesaurusEvents::IMPORTED => 'onThesaurusChange',
            ThesaurusEvents::FIELD_LINKED => 'onThesaurusChange',
            ThesaurusEvents::CANDIDATE_ACCEPTED_AS_CONCEPT => 'onThesaurusChange',
            ThesaurusEvents::CANDIDATE_ACCEPTED_AS_SYNONYM => 'onThesaurusChange',
            ThesaurusEvents::SYNONYM_LNG_CHANGED => 'onThesaurusChange',
            ThesaurusEvents::SYNONYM_POSITION_CHANGED => 'onThesaurusChange',
            ThesaurusEvents::SYNONYM_TRASHED => 'onThesaurusChange',
            ThesaurusEvents::CONCEPT_TRASHED => 'onThesaurusChange',
            ThesaurusEvents::CONCEPT_DELETED => 'onThesaurusChange',
            ThesaurusEvents::SYNONYM_ADDED => 'onThesaurusChange',
            ThesaurusEvents::CONCEPT_ADDED => 'onThesaurusChange',
            ThesaurusEvents::REINDEX_REQUIRED => 'onReindexRequired'
        ];
    }

    public function onStructureChange(RecordStructureEvent $event)
    {
        $databox = $event->getDatabox();
        $this->getIndexer()->migrateMappingForDatabox($databox);
    }

    public function onThesaurusChange(ThesaurusEvent $event)
    {
        $databox = $event->getDatabox();
        $databox->delete_data_from_cache(\databox::CACHE_THESAURUS);
    }

    public function onCollectionChange(CollectionEvent $event)
    {
        $collection = $event->getCollection();
        $this->getIndexer()->scheduleRecordsFromCollectionForIndexing($collection);
    }

    public function onRecordChange(RecordEvent $event)
    {
        $record = $event->getRecord();
        $this->getIndexer()->indexRecord($record);
    }

    public function onRecordDelete(DeletedEvent $event)
    {
        $record = $event->getRecord();
        $this->getIndexer()->deleteRecord($record);
    }

    public function flushQueue()
    {
        // Only flush queue if indexer is initialized.
        if ($this->indexer instanceof Indexer) {
            $this->getIndexer()->flushQueue();
        }
    }

    public function onReindexRequired(ReindexRequiredEvent $event)
    {
        $this->getIndexer()->populateIndex(Indexer::THESAURUS, $event->getDatabox());
    }
}
