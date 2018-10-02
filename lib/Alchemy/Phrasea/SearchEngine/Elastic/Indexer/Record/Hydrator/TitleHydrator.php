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

class TitleHydrator implements HydratorInterface
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
                m.`record_id`,
                CASE ms.`thumbtitle`
                  WHEN "1" THEN "default"
                  WHEN "0" THEN "default"
                  ELSE ms.`thumbtitle`
                END AS locale,
                CASE ms.`thumbtitle`
                  WHEN "0" THEN r.`originalname`
                  ELSE GROUP_CONCAT(m.`value` ORDER BY ms.`thumbtitle`, ms.`sorter` SEPARATOR " - ")
                END AS title
            FROM metadatas AS m FORCE INDEX(`record_id`)
            STRAIGHT_JOIN metadatas_structure AS ms ON (ms.`id` = m.`meta_struct_id`)
            STRAIGHT_JOIN record AS r ON (r.`record_id` = m.`record_id`)
            WHERE m.`record_id` IN (?)
            GROUP BY m.`record_id`, ms.`thumbtitle`
SQL;
        $statement = $this->connection->executeQuery(
            $sql,
            array(array_keys($records)),
            array(Connection::PARAM_INT_ARRAY)
        );

        while ($row = $statement->fetch()) {
            $records[$row['record_id']]['title'][$row['locale']] = $row['title'];
        }
    }
}
