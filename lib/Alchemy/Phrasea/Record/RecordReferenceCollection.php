<?php
/*
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

class RecordReferenceCollection implements \IteratorAggregate, \ArrayAccess
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
            } elseif (isset($record['databox_id'], $record['record_id'])) {
                $references[$index] = RecordReference::createFromDataboxIdAndRecordId($record['databox_id'], $record['record_id']);
            }
        }

        return new self($references);
    }

    /**
     * Append all RecordReferences extracted via call to extractor on each element
     *
     * @param array|\Traversable $list List of elements to process
     * @param callable $extractor Extracts data from each element or return null if unavailable
     * @param callable $creator Creates Reference from extracted data. no-op when null
     * @return RecordReferenceCollection
     */
    public static function fromListExtractor($list, callable $extractor, callable $creator = null)
    {
        Assertion::isTraversable($list);

        $references = [];

        if (null === $creator) {
            $creator = function ($data) {
                return $data;
            };
        }

        foreach ($list as $index => $item) {
            $data = $extractor($item);

            if (null === $data) {
                continue;
            }

            $reference = $creator($data);

            if ($reference instanceof RecordReferenceInterface) {
                $references[$index] = $reference;
            }
        }

        return new self($references);
    }

    /**
     * @var RecordReferenceInterface[]
     */
    private $references = [];

    /**
     * @var null|array
     */
    private $groups;

    /**
     * @param RecordReferenceInterface[] $references
     */
    public function __construct($references = [])
    {
        Assertion::allIsInstanceOf($references, RecordReferenceInterface::class);

        $this->references = $references instanceof \Traversable ? iterator_to_array($references, true) : $references;
    }

    /**
     * @param RecordReferenceInterface $reference
     * @param null|string|int $index
     */
    public function add(RecordReferenceInterface $reference, $index = null)
    {
        $this->groups = null;

        if (null === $index) {
            $this->references[] = $reference;

            return;
        }

        $this->references[$index] = $reference;
    }

    /**
     * @param int $databoxId
     * @param int $recordId
     * @param null|string|int $index
     * @return void
     */
    public function addRecordReference($databoxId, $recordId, $index = null)
    {
        $this->add(RecordReference::createFromDataboxIdAndRecordId($databoxId, $recordId), $index);
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
        if (null === $this->groups) {
            $this->groups = [];

            foreach ($this->references as $index => $reference) {
                $databoxId = $reference->getDataboxId();

                if (!isset($this->groups[$databoxId])) {
                    $this->groups[$databoxId] = [];
                }

                $this->groups[$databoxId][$reference->getRecordId()] = $index;
            }
        }

        return $this->groups;
    }

    /**
     * @return array
     */
    public function getDataboxIds()
    {
        return array_keys($this->groupPerDataboxId());
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

        $indexes = array_flip(array_keys($this->references));

        uksort($records, function ($keyA, $keyB) use ($indexes) {
            $indexA = $indexes[$keyA];
            $indexB = $indexes[$keyB];

            if ($indexA < $indexB) {
                return -1;
            } elseif ($indexA > $indexB) {
                return 1;
            }

            return 0;
        });

        return $records;
    }

    public function offsetExists($offset)
    {
        return isset($this->references[$offset]);
    }

    /**
     * @param mixed $offset
     * @return RecordReferenceInterface
     */
    public function offsetGet($offset)
    {
        return $this->references[$offset];
    }

    public function offsetSet($offset, $value)
    {
        Assertion::isInstanceOf($value, RecordReferenceInterface::class);

        $this->add($value, $offset);
    }

    public function offsetUnset($offset)
    {
        unset($this->references[$offset]);
        $this->groups = null;
    }
}
