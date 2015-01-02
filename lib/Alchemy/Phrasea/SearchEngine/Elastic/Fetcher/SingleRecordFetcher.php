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
use databox;
use record_adapter;
use PDO;

class SingleRecordFetcher extends AbstractRecordFetcher
{
    /**
     * @var \record_adapter
     */
    private $record;

    public function __construct(databox $databox, RecordHelper $helper, record_adapter $record)
    {
        $this->record = $record;

        parent::__construct($databox, $helper);
    }

    public function fetch()
    {
        $records = parent::fetch();

        return array_pop($records);
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
        WHERE r.record_id = :record_id
        ORDER BY r.record_id ASC
SQL;

        return $this->connection->executeQuery($sql, [':record_id' => $this->record->get_record_id()], [PDO::PARAM_INT,]);
    }
}
