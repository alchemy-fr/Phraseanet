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

use Alchemy\Phrasea\Core\Event\CollectionEvent\CollectionIndexEvent;
use Alchemy\Phrasea\Core\Event\DataboxEvent\DataboxIndexEvent;
use Alchemy\Phrasea\Core\Event\DataboxEvent\IndexEvent;
use Alchemy\Phrasea\Core\Event\RecordEvent\RecordIndexEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Core\PhraseaTokens;
use Alchemy\Phrasea\SearchEngine\Elastic\BulkOperation;
use Alchemy\Phrasea\SearchEngine\Elastic\Fetcher\RecordFetcher;
use Alchemy\Phrasea\SearchEngine\Elastic\Fetcher\RecordPoolFetcher;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordIndexer;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\TermIndexer;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Elasticsearch\Client;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ElasticSearchSubscriber implements EventSubscriberInterface
{
    /** Pool of records to index or remove from ElasticSearch */
    private static $recordsToAdd;
    private static $recordsToRemove;
    private static $recordsToUpdate;

    private $recordIndexer;
    private $termIndexer;
    private $client;
    private $appbox;
    private $indexName;

    public function __construct(RecordIndexer $recordIndexer, TermIndexer $termIndexer, Client $client, \appbox $appbox, $indexName)
    {
        $this->recordIndexer = $recordIndexer;
        $this->termIndexer = $termIndexer;
        $this->client = $client;
        $this->appbox = $appbox;
        $this->indexName = $indexName;

        self::$recordsToAdd = new \SplObjectStorage();
        self::$recordsToRemove = new \SplObjectStorage();
        self::$recordsToUpdate = new \SplObjectStorage();
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::INDEX_DATABOX => ['onIndexDatabox', -12],
            PhraseaEvents::INDEX_COLLECTION => ['onIndexCollection', -24],
            PhraseaEvents::INDEX_NEW_RECORD => ['onIndexNewRecord', -48],
            PhraseaEvents::INDEX_UPDATE_RECORD => ['onIndexUpdateRecord', -48],
            PhraseaEvents::INDEX_REMOVE_RECORD => ['onIndexRemoveRecord', -48],
            KernelEvents::FINISH_REQUEST => 'doFlushIndex',
        ];
    }

    public function onIndexDatabox(DataboxIndexEvent $event)
    {
        $databox = $event->getDatabox();
        $connection = $databox->get_connection();

        // set 'to_index' fag where 'to_index' flag is not already set
        // for given databox
        // @todo sql query this should not be done right here
        $sql = <<<SQL
        UPDATE record
        SET jeton = (jeton | :token)
        WHERE (jeton & :token) = 0
SQL;

        $connection->executeUpdate($sql, [
            ':token' => PhraseaTokens::TOKEN_INDEX
        ], [
            \PDO::PARAM_INT
        ]);
    }

    public function onIndexCollection(CollectionIndexEvent $event)
    {
        $collection = $event->getCollection();
        $connection = $collection->get_connection();

        // set 'to_index' fag where 'to_index' flag is not already set
        // for given collection
        // @todo sql query this should not be done right here
        $sql = <<<SQL
        UPDATE record
        SET jeton = (jeton | :token)
        WHERE coll_id = :coll_id
        AND (jeton & :token) = 0
SQL;

        $connection->executeUpdate($sql, [
            ':coll_id' => $collection->get_coll_id(),
            ':token' => PhraseaTokens::TOKEN_INDEX
        ], [
            \PDO::PARAM_INT,
            \PDO::PARAM_INT
        ]);
    }

    public function onIndexUpdateRecord(RecordIndexEvent $event)
    {
        $record = $event->getRecord();
        if (self::$recordsToUpdate->contains($record)) {
            return;
        }
        self::$recordsToUpdate->attach($record);
    }

    public function onIndexNewRecord(RecordIndexEvent $event)
    {
        $record = $event->getRecord();
        if (self::$recordsToAdd->contains($record)) {
            return;
        }
        self::$recordsToAdd->attach($record);
    }

    public function onIndexRemoveRecord(RecordIndexEvent $event)
    {
        $record = $event->getRecord();
        if (self::$recordsToRemove->contains($record)) {
            return;
        }
        self::$recordsToRemove->attach($record);
    }

    public function doFlushIndex()
    {
        if (self::$recordsToUpdate->count() > 0) {
            $this->doUpdateIndex();
        }

        if (self::$recordsToAdd->count() > 0) {
            $this->doInsertIndex();
        }

        if (self::$recordsToRemove->count() > 0) {
            $this->doDeleteIndex();
        }
    }

    private function doUpdateIndex()
    {
        $this->doRecordAction(self::$recordsToUpdate, 'update');
    }

    private function doInsertIndex()
    {
        $this->doRecordAction(self::$recordsToAdd, 'index');
    }

    private function doDeleteIndex()
    {
        $this->doRecordAction(self::$recordsToRemove, 'delete');
    }

    private function doRecordAction(\SplObjectStorage $poolOfRecords, $action)
    {
        // filter by databox
        $toIndex = [];
        foreach ($poolOfRecords as $record) {
            $toIndex[$record->get_sbas_id()][] = $record;
        }

        $bulk = new BulkOperation($this->client);
        $bulk->setDefaultIndex($this->indexName);
        $bulk->setAutoFlushLimit(200);

        $recordHelper = new RecordHelper($this->appbox);

        foreach($toIndex as $databoxId => $records) {
            $databox = $this->appbox->get_databox($databoxId);
            $fetcher = new RecordPoolFetcher($databox, $recordHelper, $records);

            call_user_func_array([$this->recordIndexer, $action], [$bulk, $fetcher]);
        }

        // should we refresh ?
        $this->client->indices()->refresh(['index' => $this->indexName]);
    }
}
