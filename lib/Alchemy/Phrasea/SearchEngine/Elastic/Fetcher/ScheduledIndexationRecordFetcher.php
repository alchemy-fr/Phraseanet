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

use Alchemy\Phrasea\Core\PhraseaTokens;
use PDO;

class ScheduledIndexationRecordFetcher extends AbstractRecordFetcher
{
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
        WHERE (jeton & ?) > 0
        AND (jeton & ?) = 0
        ORDER BY r.record_id ASC
        LIMIT ?, ?
SQL;

        $params = [
            PhraseaTokens::TOKEN_INDEX,
            PhraseaTokens::TOKEN_INDEXING,
            $this->offset,
            $this->batchSize,
        ];

        return $this->connection->executeQuery($sql, $params, [
            PDO::PARAM_INT,
            PDO::PARAM_INT,
            PDO::PARAM_INT,
            PDO::PARAM_INT,
        ]);
    }
}
