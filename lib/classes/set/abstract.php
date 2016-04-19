<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

abstract class set_abstract implements IteratorAggregate
{
    /**
     * @var \record_adapter[]
     */
    protected $elements = [];

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->elements);
    }

    /**
     * @param  string $offset
     * @param  record_adapter $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->elements[] = $value;
        } else {
            $this->elements[$offset] = $value;
        }
    }

    /**
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->elements[$offset]);
    }

    /**
     * @param  string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->elements[$offset]);
    }

    /**
     * @param  string         $offset
     * @return record_adapter|null
     */
    public function offsetGet($offset)
    {
        return isset($this->elements[$offset]) ? $this->elements[$offset] : null;
    }

    /**
     * @return bool
     */
    public function is_empty()
    {
        return count($this->elements) == 0;
    }

    public function __isset($key)
    {
        trigger_error("Unable to use magic method get for key $key");
        if (isset($this->$key)) {
            return true;
        }

        return false;
    }

    /**
     * Get the number of element in the set
     *
     * @return int
     */
    public function get_count()
    {
        return count($this->elements);
    }

    /**
     * @return int
     */
    public function get_count_groupings()
    {
        $n = 0;
        foreach ($this->elements as $record) {
            if ($record->isStory())
                $n ++;
        }

        return $n;
    }

    /**
     * @return record_adapter[]
     */
    public function get_elements()
    {
        return $this->elements;
    }

    /**
     * @param  record_adapter $record
     * @return $this
     */
    public function add_element(\record_adapter $record)
    {
        $this->elements[$record->getId()] = $record;

        return $this;
    }

    /**
     * @param  record_adapter $record
     * @return $this
     */
    public function remove_element(\record_adapter $record)
    {
        $key = $record->getId();
        if (isset($this->elements[$key]))
            unset($this->elements[$key]);

        return $this;
    }

    /**
     * @return string
     */
    public function serialize_list()
    {
        $basrec = [];
        foreach ($this->elements as $record) {
            $basrec[] = $record->getId();
        }

        return implode(';', $basrec);
    }
}
