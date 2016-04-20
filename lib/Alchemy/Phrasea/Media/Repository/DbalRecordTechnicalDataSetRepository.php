<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media\Repository;

use Alchemy\Phrasea\Media\Factory\TechnicalDataFactory;
use Alchemy\Phrasea\Media\RecordTechnicalDataSet;
use Alchemy\Phrasea\Media\RecordTechnicalDataSetRepository;
use Doctrine\DBAL\Connection;

class DbalRecordTechnicalDataSetRepository implements RecordTechnicalDataSetRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var TechnicalDataFactory
     */
    private $dataFactory;

    public function __construct(Connection $connection, TechnicalDataFactory $dataFactory)
    {
        $this->connection = $connection;
        $this->dataFactory = $dataFactory;
    }

    /**
     * @param int[] $recordIds
     * @return RecordTechnicalDataSet[]
     */
    public function findByRecordIds(array $recordIds)
    {
        if (empty($recordIds)) {
            return [];
        }

        $data = $this->connection->fetchAll(
            'SELECT record_id, name, value FROM technical_datas WHERE record_id IN (:recordIds)',
            ['recordIds' => $recordIds],
            ['recordIds' => Connection::PARAM_INT_ARRAY]
        );

        return $this->mapSetsFromDatabaseResult($recordIds, $data);
    }

    /**
     * @param array $recordIds
     * @param array $data
     * @return RecordTechnicalDataSet[]
     */
    private function mapSetsFromDatabaseResult(array $recordIds, array $data)
    {
        $groups = [];

        foreach ($recordIds as $recordId) {
            $groups[$recordId] = new RecordTechnicalDataSet($recordId);
        }

        foreach ($data as $item) {
            $group =& $groups[$item['record_id']];
            $group[] = $this->dataFactory->createFromNameAndValue($item['name'], $item['value']);
        }

        return array_values($groups);
    }
}
