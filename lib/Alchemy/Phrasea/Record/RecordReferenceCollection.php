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

use Alchemy\Phrasea\Databox\DataboxGroupable;
use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Assert\Assertion;

class RecordReferenceCollection implements \IteratorAggregate, \ArrayAccess, \Countable, DataboxGroupable, PerDataboxRecordId
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
     * @return array
     */
    public function getDataboxIds()
    {
        if (null === $this->groups) {
            $this->reorderGroups();
        }

        return array_keys($this->groups);
    }

    /**
     * @param \appbox $appbox
     * @return \record_adapter[]
     */
    public function toRecords(\appbox $appbox)
    {
        $databoxIds = $this->getDataboxIds();
        $records = array_fill_keys($databoxIds, []);

        foreach ($databoxIds as $databoxId) {
            $databox = $appbox->get_databox($databoxId);
            $recordIds =  $this->getDataboxRecordIds($databoxId);

            foreach ($databox->getRecordRepository()->findByRecordIds($recordIds) as $record) {
                $records[$record->getDataboxId()][$record->getRecordId()] = $record;
            }
        }

        $sorted = [];

        foreach ($this->references as $index => $reference) {
            $databoxId = $reference->getDataboxId();
            $recordId = $reference->getRecordId();

            if (isset($records[$databoxId][$recordId])) {
                $sorted[$index] = $records[$databoxId][$recordId];
            }
        }

        return $sorted;
    }

    /**
     * @param int|string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->references[$offset]);
    }

    /**
     * @param int|string $offset
     * @return RecordReferenceInterface
     */
    public function offsetGet($offset)
    {
        return $this->references[$offset];
    }

    /**
     * @param int|string $offset
     * @param RecordReferenceInterface $value
     */
    public function offsetSet($offset, $value)
    {
        Assertion::isInstanceOf($value, RecordReferenceInterface::class);

        $this->add($value, $offset);
    }

    /**
     * @param int|string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->references[$offset]);
        $this->groups = null;
    }

    /**
     * @return RecordReferenceInterface[][]
     */
    public function groupByDatabox()
    {
        if (null === $this->groups) {
            $this->reorderGroups();
        }

        return $this->groups;
    }

    public function reorderGroups()
    {
        if (null !== $this->groups) {
            return;
        }

        $groups = [];

        foreach ($this->references as $index => $reference) {
            if (!isset($groups[$reference->getDataboxId()])) {
                $groups[$reference->getDataboxId()] = [];
            }

            $groups[$reference->getDataboxId()][$index] = $reference;
        }

        $this->groups = $groups;
    }

    /**
     * @param int $databoxId
     * @return RecordReferenceInterface[]
     */
    public function getDataboxGroup($databoxId)
    {
        // avoid call to reorderGroups when not needed
        if (null === $this->groups) {
            $this->reorderGroups();
        }

        return isset($this->groups[$databoxId]) ? $this->groups[$databoxId] : [];
    }

    public function getDataboxRecordIds($databoxId)
    {
        $indexes = [];

        foreach ($this->getDataboxGroup($databoxId) as $index => $references) {
            $indexes[$references->getRecordId()] = $index;
        }

        return array_flip($indexes);
    }

    public function count()
    {
        return count($this->references);
    }
}
