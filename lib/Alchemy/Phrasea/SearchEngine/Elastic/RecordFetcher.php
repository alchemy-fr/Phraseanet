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

use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Doctrine\DBAL\Connection;
use databox;
use PDO;

class RecordFetcher
{
    private $connection;
    private $statementRecords;
    private $helper;

    private $offset = 0;
    private $batchSize = 1;

    private $databoxId;

    public function __construct(databox $databox, RecordHelper $helper)
    {
        $this->connection = $databox->get_connection();
        $this->databoxId  = $databox->get_sbas_id();
        $this->helper     = $helper;
    }

    public function fetch()
    {
        $statementRecords = $this->statementRecords();

        // Fetch records rows
        $statementRecords->execute();
        printf("Query %d/%d -> %d rows\n", $this->offset, $this->batchSize, $statementRecords->rowCount());
        $records = [];

        while ($record = $statementRecords->fetch()) {
            $records[$record['record_id']] = $record;
            printf("Record found (#%d)\n", $record['record_id']);
            $this->offset++;
        }

        if (count($records) < 1) {
            printf("End of records\n");

            return false; // End
        }

        // Fetch metadata
        $records = $this->addMetadataToRecords($records);

        // Hydrate records
        foreach ($records as $key => $record) {
            $records[$key] = $this->hydrate($record);
        }

        return $records;
    }

    public function fetchOne(\record_adapter $record_adapter)
    {
        $stmt = $this->statementRecord($record_adapter->get_record_id());
        $stmt->execute();
        $record = $stmt->fetchAll();
        $records = $this->addMetadataToRecords($record);
        foreach ($records as $key => $record) {
            $records[$key] = $this->hydrate($record);
        }

        return array_pop($records);
    }

    public function setBatchSize($size)
    {
        if ($size < 1) {
            throw new \LogicException("Batch size must be greater than or equal to 1");
        }
        $this->batchSize = (int) $size;
    }

    private function hydrate(array $record)
    {
        // Some casting
        $record['record_id']     = (int) $record['record_id'];
        $record['collection_id'] = (int) $record['collection_id'];
        // Some identifiers
        $record['id'] = $this->helper->getUniqueRecordId($this->databoxId, $record['record_id']);
        $record['base_id'] = $this->helper->getUniqueCollectionId($this->databoxId, $record['collection_id']);
        $record['databox_id'] = $this->databoxId;

        if ((int) $record['parent_record_id'] === 1) {
            $record['record_type'] = SearchEngineInterface::GEM_TYPE_STORY;
        } else {
            $record['record_type'] = SearchEngineInterface::GEM_TYPE_RECORD;
        }

        unset($record['parent_record_id']);

        return $record;
    }

    private function statementRecords()
    {
        if (!$this->statementRecords) {
            $sql = <<<SQL
            SELECT r.record_id
                 , r.coll_id as collection_id
                 , r.uuid
                 , BIN(r.status) as bin_status
                 , r.sha256 -- TODO rename in "hash"
                 , r.originalname as original_name
                 , r.mime
                 , r.type
                 , r.parent_record_id
                 , r.credate as created_on
                 , r.moddate as updated_on
                    FROM record r
                    ORDER BY r.record_id ASC
                    LIMIT :offset, :limit
SQL;

            $statement = $this->connection->prepare($sql);
            $statement->bindParam(':offset', $this->offset, PDO::PARAM_INT);
            $statement->bindParam(':limit', $this->batchSize, PDO::PARAM_INT);
            $this->statementRecords = $statement;
        }

        return $this->statementRecords;
    }

    private function statementRecord($id)
    {
        $sql = <<<SQL
        SELECT r.record_id
             , r.coll_id as collection_id
             , r.uuid
             , BIN(r.status) as bin_status
             , r.sha256 -- TODO rename in "hash"
             , r.originalname as original_name
             , r.mime
             , r.type
             , r.parent_record_id
             , r.credate as created_on
             , r.moddate as updated_on
                FROM record r
                WHERE r.record_id = :id
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':id', $id, PDO::PARAM_INT);

        return $statement;
    }

    private function execStatementMetadata($ids)
    {
        $sql = <<<SQL
            (SELECT record_id, ms.name AS metadata_key, m.value AS metadata_value, 'caption' AS metadata_type, ms.business AS metadata_private
            FROM metadatas AS m
            INNER JOIN metadatas_structure AS ms ON (ms.id = m.meta_struct_id)
            WHERE record_id IN (?))

            UNION

            (SELECT record_id, t.name AS metadata_key, t.value AS metadata_value, 'exif' AS metadata_type, 0 AS metadata_private
            FROM technical_datas AS t
            WHERE record_id IN (?))
SQL;

        return $this->connection->executeQuery($sql, array($ids, $ids), array(Connection::PARAM_INT_ARRAY, Connection::PARAM_INT_ARRAY));
    }

    private function addMetadataToRecords($records)
    {
        $statementMetadata = $this->execStatementMetadata(array_keys($records));

        while ($metadata = $statementMetadata->fetch()) {
            // Store metadata value
            $value = $metadata['metadata_value'];
            $key = $metadata['metadata_key'];
            $type = $metadata['metadata_type'];

            // Do not keep empty values
            if (empty($value)) {
                continue;
            }

            if ($metadata['metadata_private']) {
                $type = 'private_'.$type;
            }

            // Metadata can be multi-valued
            if (!isset($records[$metadata['record_id']] [$type][$key])) {
                $records[$metadata['record_id']] [$type][$key] = $value;
            } elseif (is_array($records[$metadata['record_id']] [$type][$key])) {
                $records[$metadata['record_id']] [$type][$key][] = $value;
            } else {
                $records[$metadata['record_id']] [$type][$key] = array($records[$metadata['record_id']] [$type][$key], $value);
            }
        }

        return $records;
    }
}
