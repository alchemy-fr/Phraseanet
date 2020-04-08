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
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
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
    private $options;
    private $connection;
    private $statement;
    private $delegate;

    // since we fetch records dy DESC, this will be the HIGHEST record_id fetched during last batch
    private $upper_rid = PHP_INT_MAX;
    private $batchSize = 1;
    private $buffer = array();

    private $hydrators = array();
    private $postFetch;
    private $onDrain;

    public function __construct(databox $databox,ElasticsearchOptions $options, array $hydrators, FetcherDelegateInterface $delegate = null)
    {
        $this->databox = $databox;
        $this->options = $options;
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
        // printf("Query %d(%d) -> %d rows\n", $this->upper_rid, $this->batchSize, $statement->rowCount());

        $records = [];
        $this->upper_rid = PHP_INT_MAX;
        while ($record = $statement->fetch()) {
            $records[$record['record_id']] = $record;
            $rid = (int)($record['record_id']);
            if($rid < $this->upper_rid) {
                $this->upper_rid = (int)($record['record_id']);
            }
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
        $this->upper_rid = PHP_INT_MAX;
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
            $sql =  "SELECT r.*, c.asciiname AS collection_name, subdef.width, subdef.height, subdef.size\n"
                 . " FROM ((\n"
                 . "     SELECT r.record_id, r.coll_id AS collection_id, r.uuid, r.status AS flags_bitfield, r.sha256,\n"
                 . "            r.originalname AS original_name, r.mime, r.type, r.parent_record_id,\n"
                 . "            r.credate AS created_on, r.moddate AS updated_on, r.coll_id\n"
                 . "     FROM record r\n"
                 . "     WHERE -- WHERE\n"
                 . "     ORDER BY " . $this->options->getPopulateOrderAsSQL() . " " . $this->options->getPopulateDirectionAsSQL() . "\n"
                 . "     LIMIT :limit\n"
                 . "   ) AS r\n"
                 . "   INNER JOIN coll c ON (c.coll_id = r.coll_id)\n"
                 . " )\n"
                 . " LEFT JOIN\n"
                 . " subdef ON subdef.record_id=r.record_id AND subdef.name='document'\n"
                 . " ORDER BY " . $this->options->getPopulateOrderAsSQL() . " " . $this->options->getPopulateDirectionAsSQL() . "";

            $where = 'record_id < :upper_rid';
            if( ($w = $this->delegate->buildWhereClause()) != '') {
                $where = '(' . $where . ') AND (' . $w . ')';
            }
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
                $statement->bindParam(':upper_rid', $this->upper_rid, PDO::PARAM_INT);
                $statement->bindParam(':limit', $this->batchSize, PDO::PARAM_INT);
                $this->statement = $statement;
            } else {
                // Inject own query parameters
                $params[':upper_rid'] = $this->upper_rid;
                $params[':limit'] = $this->batchSize;
                $types[':offset'] = $types[':limit'] = PDO::PARAM_INT;

                return $this->connection->executeQuery($sql, $params, $types);
            }
        }

        $this->statement->execute();

        return $this->statement;
    }
}
