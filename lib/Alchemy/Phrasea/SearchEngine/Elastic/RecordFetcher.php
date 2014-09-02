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

use databox;
use Doctrine\DBAL\Driver\Connection;
use PDO;

class RecordFetcher
{
    private $connection;
    private $statement;
    private $helper;

    private $offset = 0;
    private $batchSize = 1;
    private $needsFetch = true;
    private $currentRow;

    private $databoxId;

    public function __construct(databox $databox, RecordHelper $helper)
    {
        $this->connection = $databox->get_connection();
        $this->databoxId  = $databox->get_sbas_id();
        $this->helper     = $helper;
    }

    public function fetch()
    {
        $statement = $this->statement();

        // Start of a batch
        if ($this->needsFetch) {
            $statement->execute();
            $this->needsFetch = false;
            printf("Query %d/%d -> %d rows\n", $this->offset, $this->batchSize, $statement->rowCount());
        }

        $record = null;

        while (true) {
            // Get a row
            if ($this->currentRow) {
                $row = $this->currentRow;
                $this->currentRow = null;
            } else {
                $row = $statement->fetch();
            }
            // End of data
            if (!$row) {
                break;
            }
            if ($record) {
                // This row belongs to the next record, keep row for next call
                if ($row['record_id'] !== $record['record_id']) {
                    $this->currentRow = $row;
                    break;
                }
            } else {
                // Keep this row as record data
                $record = $row;
                // Cleanup query metadata
                unset($record['metadata_key']);
                unset($record['metadata_value']);
                unset($record['metadata_type']);
                unset($record['metadata_private']);
            }

            // Store metadata value
            $key = $row['metadata_key'];
            $type = $row['metadata_type'];
            if ($row['metadata_private']) {
                $type = 'private_'.$type;
            }
            // Metadata can be multi-valued
            if (!isset($record[$type][$key])) {
                $record[$type][$key] = $row['metadata_value'];
            } elseif (is_array($record[$type][$key])) {
                $record[$type][$key][] = $row['metadata_value'];
            } else {
                $record[$type][$key] = array($record[$type][$key], $row['metadata_value']);
            }
        }

        if ($record) {
            printf("Record found (#%d)\n", $record['record_id']);
            $record = $this->hydrate($record);
            $this->offset++;
        } else {
            printf("End of records\n");
        }

        // If we exausted the last result set
        if ($this->offset % $this->batchSize === 0 || !$record) {
            $statement->closeCursor();
            $this->needsFetch = true;
        }

        return $record;
    }

    public function setBatchSize($size)
    {
        if ($size < 1) {
            throw new LogicException("Batch size must be greater than or equal to 1");
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

        return $record;
    }

    private function statement()
    {
        if (!$this->statement) {
            $sql = 'SELECT r.record_id
                         , r.coll_id as collection_id
                         , r.uuid
                         , r.sha256 -- TODO rename in "hash"
                         , r.originalname as original_name
                         , r.mime
                         , r.type
                         , r.credate as created_at
                         , r.moddate as updated_at
                         , m.metadata_key
                         , m.metadata_value
                         , m.metadata_type
                         , m.metadata_private
                    FROM (
                        SELECT * FROM record r
                        WHERE r.parent_record_id = 0 -- Only records, not stories
                        LIMIT :offset, :limit
                    ) AS r
                    LEFT JOIN (
                        SELECT record_id, ms.name AS metadata_key, m.value AS metadata_value, \'caption\' AS metadata_type, ms.business AS metadata_private
                        FROM metadatas AS m
                        INNER JOIN metadatas_structure AS ms ON (ms.id=m.meta_struct_id)
                        UNION
                        SELECT record_id, t.name AS metadata_key, t.value AS metadata_value, \'exif\' AS metadata_type, 0 AS metadata_private
                        FROM technical_datas AS t
                    ) AS m USING(record_id)
                    ORDER BY r.record_id ASC';

            $statement = $this->connection->prepare($sql);
            $statement->bindParam(':offset', $this->offset, PDO::PARAM_INT);
            $statement->bindParam(':limit', $this->batchSize, PDO::PARAM_INT);
            $this->statement = $statement;
        }

        return $this->statement;
    }
}
