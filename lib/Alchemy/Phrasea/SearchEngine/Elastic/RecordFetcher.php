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

    private $offset = 0;
    private $batchSize = 1;
    private $needsFetch = true;

    private $databoxId;

    public function __construct(databox $databox)
    {
        $this->connection = $databox->get_connection();
        $this->databoxId  = $databox->get_sbas_id();
    }

    public function fetch()
    {
        $statement = $this->statement();

        // Start of a batch
        if ($this->needsFetch) {
            $statement->execute();
            $this->needsFetch = false;
            printf("Query %d/%d -> %d results\n", $this->offset, $this->batchSize, $statement->rowCount());
        }

        if ($record = $statement->fetch()) {
            // printf("Record found (#%d)\n", $record['id']);
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
        $record['databox_id'] = $this->databoxId;
        $record['base_id']    = null; // TODO
        $record['id']         = self::uniqueIdentifier($record);

        return $record;
    }

    private static function uniqueIdentifier($record)
    {
        return sprintf('%d_%d', $record['databox_id'], $record['record_id']);
    }

    private function statement()
    {
        if (!$this->statement) {
            $sql = 'SELECT
                        record_id,
                        coll_id as collection_id,
                        uuid,
                        sha256,
                        originalname as original_name,
                        mime,
                        type,
                        credate as created_at,
                        moddate as updated_at
                    FROM record
                    WHERE parent_record_id = 0 -- Only records, not stories
                    ORDER BY record_id ASC
                    LIMIT :offset, :limit;';
            $statement = $this->connection->prepare($sql);
            $statement->bindParam(':offset', $this->offset, PDO::PARAM_INT);
            $statement->bindParam(':limit', $this->batchSize, PDO::PARAM_INT);
            $this->statement = $statement;
        }

        return $this->statement;
    }
}
