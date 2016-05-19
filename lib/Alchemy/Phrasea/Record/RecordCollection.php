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
use Assert\Assertion;

class RecordCollection implements \IteratorAggregate, \ArrayAccess, \Countable, DataboxGroupable, PerDataboxRecordId
{
    /**
     * @var \record_adapter[]
     */
    private $records = [];

    /**
     * @var array<int, int|string>
     */
    private $groups = [];

    /**
     * @var bool
     */
    private $reorderNeeded = false;

    public function __construct($records = [])
    {
        Assertion::allIsInstanceOf($records, \record_adapter::class);

        foreach ($records as $index => $record) {
            $this->add($record, $index);
        }
    }

    /**
     * @param \record_adapter $record
     * @param null|int|string $index
     * @return void
     */
    public function add(\record_adapter $record, $index = null)
    {
        if (null === $index) {
            $this->addWithUnknownIndex($record);

            return;
        }

        if (isset($this->records[$index])){
            unset($this->groups[$this->records[$index]->getDataboxId()][$index]);
            $this->reorderNeeded = true;
        }

        $this->records[$index] = $record;

        $this->addIndexToGroups($record, $index);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->records);
    }

    /**
     * @param int|string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->records[$offset]);
    }

    /**
     * @param int|string $offset
     * @return \record_adapter
     */
    public function offsetGet($offset)
    {
        return $this->records[$offset];
    }

    /**
     * @param int|string $offset
     * @param \record_adapter $value
     */
    public function offsetSet($offset, $value)
    {
        Assertion::isInstanceOf($value, \record_adapter::class);

        $this->add($value, $offset);
    }

    /**
     * @param int|string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        if (isset($this->records[$offset])) {
            unset($this->groups[$this->records[$offset]->getDataboxId()][$offset]);
        }

        unset($this->records[$offset]);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->records);
    }

    /**
     * Returns records groups by databoxId (possibly not in order)
     *
     * @return \record_adapter[][]
     */
    public function groupByDatabox()
    {
        return $this->groups;
    }

    /**
     * @return int[]
     */
    public function getDataboxIds()
    {
        return array_keys($this->groups);
    }

    /**
     * @param int $databoxId
     * @return \record_adapter[]
     */
    public function getDataboxGroup($databoxId)
    {
        return isset($this->groups[$databoxId]) ? $this->groups[$databoxId] : [];
    }

    /**
     * @return void
     */
    public function reorderGroups()
    {
        if (!$this->reorderNeeded) {
            return;
        }

        $groups = [];

        foreach ($this->records as $index => $record) {
            $databoxId = $record->getDataboxId();

            if (!isset($groups[$databoxId])) {
                $groups[$databoxId] = [];
            }

            $groups[$databoxId][$index] = $record;
        }

        $this->groups = $groups;
        $this->reorderNeeded = false;
    }

    /**
     * @param int $databoxId
     * @return int[]
     */
    public function getDataboxRecordIds($databoxId)
    {
        $recordIds = [];

        foreach ($this->getDataboxGroup($databoxId) as $record) {
            $recordIds[$record->getRecordId()] = true;
        }

        return array_keys($recordIds);
    }

    /**
     * @param \record_adapter $record
     * @return void
     */
    private function addWithUnknownIndex(\record_adapter $record)
    {
        $this->records[] = $record;

        end($this->records);

        $this->addIndexToGroups($record, key($this->records));
    }

    /**
     * @param \record_adapter $record
     * @param int|string $index
     * @return void
     */
    private function addIndexToGroups(\record_adapter $record, $index)
    {
        $databoxId = $record->getDataboxId();

        if (!isset($this->groups[$databoxId])) {
            $this->groups[$databoxId] = [];
        }

        $this->groups[$databoxId][$index] = $record;
    }
}
