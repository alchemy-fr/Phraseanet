<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;

class SubDefinitionHydrator implements HydratorInterface
{
    private $connection;

    public function __construct(DriverConnection $connection)
    {
        $this->connection = $connection;
    }

    public function hydrateRecords(array &$records)
    {
        $sql = <<<SQL
            SELECT
              s.record_id,
              s.name,
              s.height,
              s.width,
              CONCAT(TRIM(TRAILING '/' FROM s.path), '/', s.file) AS path
            FROM subdef s
            WHERE s.record_id IN (?)
            AND s.name IN ('thumbnail', 'preview', 'thumbnailgif')
SQL;
        $statement = $this->connection->executeQuery($sql,
            array(array_keys($records)),
            array(Connection::PARAM_INT_ARRAY)
        );

        while ($subdefs = $statement->fetch()) {
            $records[$subdefs['record_id']]['subdefs'][$subdefs['name']] = array(
                'path' => $subdefs['path'],
                'width' => $subdefs['width'],
                'height' => $subdefs['height'],
            );
        }
    }
}
