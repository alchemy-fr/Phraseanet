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
use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
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
            CollectionEvents::NAME_CHANGED => 'onCollectionChange'
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
        $this->indexer->queueCollectionRecordsForIndexing($collection);
    }

    public function onRecordChange(RecordEvent $event)
    {
        $record = $event->getRecord();
        $this->indexer->queueRecordForIndexing($record);
    }

    public function onRecordDelete(RecordDeletedEvent $event)
    {
        $record = $event->getRecord();
        $this->indexer->queueRecordForDeletion($record);
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this->indexer->flushQueue();
    }

    /////////////////////////////////////////////////////

    // private function doRecordAction(\SplObjectStorage $poolOfRecords, $action)
    // {
    //     // filter by databox
    //     $toIndex = [];
    //     foreach ($poolOfRecords as $record) {
    //         $toIndex[$record->get_sbas_id()][] = $record;
    //     }

    //     $bulk = new BulkOperation($this->client);
    //     $bulk->setDefaultIndex($this->indexName);
    //     $bulk->setAutoFlushLimit(200);

    //     $recordHelper = new RecordHelper($this->appbox);

    //     foreach($toIndex as $databoxId => $records) {
    //         $databox = $this->appbox->get_databox($databoxId);
    //         $fetcher = new RecordPoolFetcher($databox, $recordHelper, $records);

    //         call_user_func_array([$this->recordIndexer, $action], [$bulk, $fetcher]);
    //     }

    //     // should we refresh ?
    //     $this->client->indices()->refresh(['index' => $this->indexName]);
    // }
}
