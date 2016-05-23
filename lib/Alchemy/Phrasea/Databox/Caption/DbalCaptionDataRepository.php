<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox\Caption;

use Doctrine\DBAL\Connection;

class DbalCaptionDataRepository implements CaptionDataRepository
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findByRecordIds(array $recordIds)
    {
        if (!$recordIds) {
            return [];
        }

        $sql = <<<'SQL'
SELECT m.record_id, m.id AS meta_id, s.id AS structure_id, value, VocabularyType, VocabularyId
FROM metadatas m INNER JOIN metadatas_structure s ON s.id = m.meta_struct_id
WHERE m.record_id IN (:recordIds)
ORDER BY m.record_id ASC, s.sorter ASC
SQL;

        $data = $this->connection->fetchAll(
            $sql, ['recordIds' => $recordIds], ['recordIds' => Connection::PARAM_INT_ARRAY]
        );

        return $this->mapByRecordId($data, $recordIds);
    }

    /**
     * @param array $data
     * @param int[] $recordIds
     * @return array[]
     */
    private function mapByRecordId(array $data, array $recordIds)
    {
        $groups = array_fill_keys($recordIds, []);

        foreach ($data as $item) {
            $recordId = $item['record_id'];

            $groups[$recordId][] = $item;
        }

        return $groups;
    }
}
