<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine;

use Alchemy\Phrasea\Model\RecordInterface;
use Assert\Assertion;

class SearchEngineResultToRecordsConverter
{
    /**
     * @var \appbox
     */
    private $appbox;

    public function __construct(\appbox $appbox)
    {
        $this->appbox = $appbox;
    }

    /**
     * @param RecordInterface[] $records
     * @return \record_adapter[]
     */
    public function convert($records)
    {
        Assertion::allIsInstanceOf($records, RecordInterface::class);

        $perDataboxRecordIds = $this->groupRecordIdsPerDataboxId($records);

        $records = [];

        foreach ($perDataboxRecordIds as $databoxId => $recordIds) {
            $databox = $this->appbox->get_databox($databoxId);

            foreach ($databox->getRecordRepository()->findByRecordIds(array_keys($recordIds)) as $record) {
                $records[$recordIds[$record->getRecordId()]] = $record;
            }
        }

        ksort($records);

        return $records;
    }

    /**
     * @param RecordInterface[] $records
     * @return array[]
     */
    private function groupRecordIdsPerDataboxId($records)
    {
        $number = 0;
        $perDataboxRecordIds = [];

        foreach ($records as $record) {
            $databoxId = $record->getDataboxId();

            if (!isset($perDataboxRecordIds[$databoxId])) {
                $perDataboxRecordIds[$databoxId] = [];
            }

            $perDataboxRecordIds[$databoxId][$record->getRecordId()] = $number++;
        }

        return $perDataboxRecordIds;
    }
}
