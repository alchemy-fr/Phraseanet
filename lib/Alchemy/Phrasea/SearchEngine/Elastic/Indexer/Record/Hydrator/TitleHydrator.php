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

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;

class TitleHydrator implements HydratorInterface
{
    private $connection;

    /** @var RecordHelper */
    private $helper;

    public function __construct(DriverConnection $connection, RecordHelper $helper)
    {
        $this->connection = $connection;
        $this->helper = $helper;
    }

    public function hydrateRecords(array &$records)
    {
        $sql = "SELECT\n"
            . "m.`record_id`,\n"
            . "   CASE ms.`thumbtitle`\n"
            . "     WHEN '1' THEN 'default'\n"
            . "     WHEN '0' THEN 'default'\n"
            . "     ELSE ms.`thumbtitle`\n"
            . "   END AS locale,\n"
            . "   CASE ms.`thumbtitle`\n"
            . "     WHEN '0' THEN r.`originalname`\n"
            . "     ELSE GROUP_CONCAT(m.`value` ORDER BY ms.`thumbtitle`, ms.`sorter` SEPARATOR ' - ')\n"
            . "   END AS title\n"
            . "FROM metadatas AS m FORCE INDEX(`record_id`)\n"
            . "STRAIGHT_JOIN metadatas_structure AS ms ON (ms.`id` = m.`meta_struct_id`)\n"
            . "STRAIGHT_JOIN record AS r ON (r.`record_id` = m.`record_id`)\n"
            . "WHERE m.`record_id` IN (?)\n"
            . "GROUP BY m.`record_id`, ms.`thumbtitle`\n";

        $statement = $this->connection->executeQuery(
            $sql,
            array(array_keys($records)),
            array(Connection::PARAM_INT_ARRAY)
        );

        while ($row = $statement->fetch()) {
            $records[$row['record_id']]['title'][$row['locale']] = $this->helper->sanitizeValue($row['title'], FieldMapping::TYPE_STRING);
        }
    }
}
