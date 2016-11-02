<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record;

use Alchemy\Phrasea\Core\PhraseaTokens;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\Exception;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\FetcherDelegate;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\FetcherDelegateInterface;
use Closure;
use databox;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use PDO;

class Fetcher
{
    private $databox;
    private $connection;
    private $statement;
    private $delegate;

    private $offset = 0;
    private $batchSize = 1;
    private $buffer = array();

    private $hydrators = array();
    private $postFetch;
    private $onDrain;

    public function __construct(databox $databox, array $hydrators, FetcherDelegateInterface $delegate = null)
    {
        $this->databox = $databox;
        $this->connection = $databox->get_connection();;
        $this->hydrators  = $hydrators;
        $this->delegate   = $delegate ?: new FetcherDelegate();
    }

    public function getDatabox()
    {
        return $this->databox;
    }

    public function fetch()
    {
        if (empty($this->buffer)) {
            if ($records = $this->fetchBatch()) {
                // Keep original order while preventing array_shift
                $this->buffer = array_reverse($records);
            }
        }

        return array_pop($this->buffer);
    }

    private function fetchBatch()
    {
        // Fetch records rows
        $statement = $this->getExecutedStatement();
        // printf("Query %d/%d -> %d rows\n", $this->offset, $this->batchSize, $statement->rowCount());

        $records = [];
        while ($record = $statement->fetch()) {
            $records[$record['record_id']] = $record;
            $this->offset++;
        }
        if (empty($records)) {
            $this->onDrain->__invoke();
            return;
        }

        // Hydrate records
        foreach ($this->hydrators as $hydrator) {
            $hydrator->hydrateRecords($records);
        }
        foreach ($records as $record) {
            if (!isset($record['id'])) {
                throw new Exception('No record hydrator set the "id" key.');
            }
        }

        if ($this->postFetch) {
            $this->postFetch->__invoke($records);
        }

        return $records;
    }

    public function restart()
    {
        $this->buffer = array();
        $this->offset = 0;
    }

    public function setBatchSize($size)
    {
        if ($size < 1) {
            throw new \LogicException("Batch size must be greater than or equal to 1");
        }
        $this->batchSize = (int) $size;
    }

    public function setPostFetch(Closure $postFetch)
    {
        $this->postFetch = $postFetch;
    }

    public function onDrain(Closure $onDrain)
    {
        $this->onDrain = $onDrain;
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement
     */
    private function getExecutedStatement()
    {
        if (!$this->statement) {
            $sql = "SELECT r.record_id"
                 . ", r.coll_id AS collection_id"
                 . ", c.asciiname AS collection_name"
                 . ", r.uuid"
                 . ", r.status AS flags_bitfield"
                 . ", r.sha256" // -- TODO rename in "hash"
                 . ", r.originalname AS original_name"
                 . ", r.mime, r.type, r.parent_record_id, r.credate AS created_on, r.moddate AS updated_on"
                 . ", subdef.width, subdef.height"
                 . " FROM (record r INNER JOIN coll c ON (c.coll_id = r.coll_id))"
                 . " LEFT JOIN subdef ON subdef.record_id=r.record_id AND subdef.name='document'"
                 . " -- WHERE"
                 . " ORDER BY r.record_id DESC"
                 . " LIMIT :offset, :limit";

            $where = $this->delegate->buildWhereClause();
            $sql = str_replace('-- WHERE', $where, $sql);

            // Build parameters list
            $params = $this->delegate->getParameters();
            $types  = $this->delegate->getParametersTypes();

            // Find if query is preparable
            static $nonPreparableTypes = array(
                Connection::PARAM_INT_ARRAY,
                Connection::PARAM_STR_ARRAY
            );
            $preparable = array() === array_intersect($nonPreparableTypes, $types);

            if ($preparable) {
                $statement = $this->connection->prepare($sql);
                foreach ($params as $key => $value) {
                    if (isset($types[$key])) {
                        $statement->bindValue($key, $value, $types[$key]);
                    } else {
                        $statement->bindValue($key, $value);
                    }
                }
                // Reference bound parameters
                $statement->bindParam(':offset', $this->offset, PDO::PARAM_INT);
                $statement->bindParam(':limit', $this->batchSize, PDO::PARAM_INT);
                $this->statement = $statement;
            } else {
                // Inject own query parameters
                $params[':offset'] = $this->offset;
                $params[':limit'] = $this->batchSize;
                $types[':offset'] = $types[':limit'] = PDO::PARAM_INT;

                return $this->connection->executeQuery($sql, $params, $types);
            }
        }

        $this->statement->execute();

        return $this->statement;
    }
}
