<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Fetcher;

use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Doctrine\DBAL\Connection;
use databox;
use PDO;

class RecordPoolFetcher extends AbstractRecordFetcher
{
    /**
     * @var \record_adapter[]
     */
    private $pool;

    public function __construct(databox $databox, RecordHelper $helper, array $records)
    {
        if (count($records) === 0) {
            throw new \InvalidArgumentException('Pool of records must at least contain one record');
        }
        $this->pool = $records;

        parent::__construct($databox, $helper);
    }

    protected function statementRecords()
    {
        $sql = <<<SQL
            SELECT r.record_id
                 , r.coll_id as collection_id
                 , c.asciiname as collection_name
                 , r.uuid
                 , r.status as flags_bitmask
                 , r.sha256 -- TODO rename in "hash"
                 , r.originalname as original_name
                 , r.mime
                 , r.type
                 , r.parent_record_id
                 , r.credate as created_on
                 , r.moddate as updated_on
            FROM record r
            INNER JOIN coll c ON (c.coll_id = r.coll_id)
            WHERE r.record_id IN (?)
            ORDER BY r.record_id ASC
            LIMIT ?, ?
SQL;

        $records = array_map(function($record) {
            return $record->get_record_id();
        }, $this->pool);

        return $this->connection->executeQuery($sql, [
            $records,
            $this->offset,
            $this->batchSize
        ], [
            Connection::PARAM_INT_ARRAY,
            PDO::PARAM_INT,
            PDO::PARAM_INT,
        ]);
    }
}
