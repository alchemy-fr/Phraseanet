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

use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\Exception;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\FetcherDelegate;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\FetcherDelegateInterface;
use Closure;
use databox;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use LogicException;
use PDO;

class Fetcher
{
    private $databox;
    private $options;
    private $connection;
    private $statement;
    private $delegate;

    // since we fetch records dy different order/direction, we setup sql limit
    /** @var int|string  */
    private $boundLimit;           // may be highest or lowest int or date, as a startup condition for sql or loop
    /** @var int|string */
    private $lastLimit;            // the last falue fetched
    /** @var Closure  */
    private $updateLastLimitDelegate;   // must update the lastLimit by comparing the current record rid or moddate while fetching

    private $batchSize = 1;
    private $buffer = array();

    private $hydrators = array();
    private $postFetch;
    private $onDrain;

    public function __construct(databox $databox, ElasticsearchOptions $options, array $hydrators, FetcherDelegateInterface $delegate = null)
    {
        $this->databox = $databox;
        $this->options = $options;
        $this->connection = $databox->get_connection();;
        $this->hydrators  = $hydrators;
        $this->delegate   = $delegate ?: new FetcherDelegate();

        // set the boundLimit and updateDelegate, depends on populate-order and populate-direction
        // the bound limit value is used on first run, but also as initial value on fetch loop

        // too bad we cannot assign to a variable a builtin function ("min" or "max") as a closure (= vector)
        // we need to encapsulate the builtin function into a closure in php.
        //
        if($options->getPopulateOrder() === ElasticsearchOptions::POPULATE_ORDER_RID) {
            // record_id
            if ($options->getPopulateDirection() === ElasticsearchOptions::POPULATE_DIRECTION_ASC) {
                $this->boundLimit = 0;
                $this->updateLastLimitDelegate = function ($record) {
                    $this->lastLimit = max($this->lastLimit, (int)($record['record_id']));
                };
            }
            else {
                $this->boundLimit = PHP_INT_MAX;
                $this->updateLastLimitDelegate = function ($record) {
                    $this->lastLimit = min($this->lastLimit, (int)($record['record_id']));
                };
            }
        }
        else {
            // moddate ; max() min() is ok on strings !
            if ($options->getPopulateDirection() === ElasticsearchOptions::POPULATE_DIRECTION_ASC) {
                $this->boundLimit = '0000-00-00 00:00:00';
                $this->updateLastLimitDelegate = function ($record) {
                    $this->lastLimit = max($this->lastLimit, $record['updated_on']);
                };
            }
            else {
                $this->boundLimit = '9999-12-31 23:59:59';
                $this->updateLastLimitDelegate = function ($record) {
                    $this->lastLimit = min($this->lastLimit, $record['updated_on']);
                };
            }
        }

        // limit for first run
        $this->lastLimit = $this->boundLimit;
    }

    public function getDatabox()
    {
        return $this->databox;
    }

    /**
     * @return mixed
     * @throws DBALException
     * @throws Exception
     */
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

    /**
     * @return array
     * @throws DBALException
     * @throws Exception
     */
    private function fetchBatch()
    {
        // Fetch records rows
        $statement = $this->getExecutedStatement();
        // printf("Query %s (%d) -> %d rows\n", $this->lastLimit, $this->batchSize, $statement->rowCount());

        $records = [];
        $this->lastLimit = $this->boundLimit;   // initial low or high value
        while ($record = $statement->fetch()) {
            $records[$record['record_id']] = $record;
            // compare/update limit
            // ($this->updateLastLimitDelegate)($record);       // php 7.2 only
            call_user_func($this->updateLastLimitDelegate, $record);
        }
        if (empty($records)) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->onDrain->__invoke();
            return [];
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
            /** @noinspection PhpUndefinedMethodInspection */
            $this->postFetch->__invoke($records);
        }

        return $records;
    }

    public function restart()
    {
        $this->buffer = array();
        $this->lastLimit = $this->boundLimit;
    }

    public function setBatchSize($size)
    {
        if ($size < 1) {
            throw new LogicException("Batch size must be greater than or equal to 1");
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
     * @return Statement
     * @throws DBALException
     */
    private function getExecutedStatement()
    {
        if (!$this->statement) {
            $sql =  "SELECT r.*, c.asciiname AS collection_name, subdef.width, subdef.height, subdef.size\n"
                . " FROM ((\n"
                . "     SELECT record_id, coll_id AS collection_id, uuid, status AS flags_bitfield, sha256,\n"
                . "            originalname AS original_name, mime, type, parent_record_id, cover_record_id,\n"
                . "            credate AS created_on, moddate AS updated_on, coll_id\n"
                . "     FROM record\n"
                . "     WHERE -- WHERE\n"
                . "     ORDER BY " . ($this->options->getPopulateOrder() === ElasticsearchOptions::POPULATE_ORDER_RID ? 'record_id':'moddate')
                . " " . $this->options->getPopulateDirectionAsSQL() . "\n"
                . "     LIMIT :limit\n"
                . "   ) AS r\n"
                . "   INNER JOIN coll c ON (c.coll_id = r.coll_id)\n"
                . " )\n"
                . " LEFT JOIN\n"
                . " subdef ON subdef.record_id=r.record_id AND subdef.name='document'\n"
                . " ORDER BY " . ($this->options->getPopulateOrder() === ElasticsearchOptions::POPULATE_ORDER_RID ? 'record_id':'updated_on')
                . " " . $this->options->getPopulateDirectionAsSQL() . "";

            $where = ($this->options->getPopulateOrder() === ElasticsearchOptions::POPULATE_ORDER_RID ? 'record_id' : 'moddate') .
                ($this->options->getPopulateDirection() === ElasticsearchOptions::POPULATE_DIRECTION_DESC ? ' < ' : ' > ') .
                ':bound';
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
                $statement->bindParam(':bound', $this->lastLimit, PDO::PARAM_INT);
                $statement->bindParam(':limit', $this->batchSize, PDO::PARAM_INT);
                $this->statement = $statement;
            } else {
                // Inject own query parameters
                $params[':bound'] = $this->lastLimit;
                $params[':limit'] = $this->batchSize;
                $types[':offset'] = $types[':limit'] = PDO::PARAM_INT;

                return $this->connection->executeQuery($sql, $params, $types);
            }
        }

        $this->statement->execute();

        return $this->statement;
    }
}