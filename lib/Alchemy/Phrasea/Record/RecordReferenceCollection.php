<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Record;

use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Assert\Assertion;

class RecordReferenceCollection implements \IteratorAggregate
{
    /**
     * @param array<int|string,array> $records
     * @return RecordReferenceCollection
     */
    public static function fromArrayOfArray($records)
    {
        Assertion::allIsArrayAccessible($records);

        $references = [];

        foreach ($records as $index => $record) {
            if (isset($record['id'])) {
                $references[$index] = RecordReference::createFromRecordReference($record['id']);
            } elseif (isset($record['databox_id']) && isset($record['record_id'])) {
                $references[$index] = RecordReference::createFromDataboxIdAndRecordId($record['databox_id'], $record['record_id']);
            }
        }

        return new self($references);
    }

    /**
     * @var RecordReferenceInterface[]
     */
    private $references = [];

    /**
     * @param RecordReferenceInterface[] $references
     */
    public function __construct($references)
    {
        Assertion::allIsInstanceOf($references, RecordReferenceInterface::class);

        $this->references = $references;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->references);
    }

    /**
     * @return array<int,array<int,int>>
     */
    public function groupPerDataboxId()
    {
        $groups = [];

        foreach ($this->references as $index => $reference) {
            $databoxId = $reference->getDataboxId();

            if (!isset($groups[$databoxId])) {
                $groups[$databoxId] = [];
            }

            $groups[$databoxId][$reference->getRecordId()] = $index;
        }

        return $groups;
    }

    /**
     * @param \appbox $appbox
     * @return \record_adapter[]
     */
    public function toRecords(\appbox $appbox)
    {
        $groups = $this->groupPerDataboxId();

        $records = [];

        foreach ($groups as $databoxId => $recordIds) {
            $databox = $appbox->get_databox($databoxId);

            foreach ($databox->getRecordRepository()->findByRecordIds(array_keys($recordIds)) as $record) {
                $records[$recordIds[$record->getRecordId()]] = $record;
            }
        }

        ksort($records);

        return array_values($records);
    }
}
