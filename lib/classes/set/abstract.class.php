<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class set_abstract implements IteratorAggregate
{
    /**
     *
     * @var Array
     */
    protected $elements = array();

    /**
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        $this->load_elements();

        return new ArrayIterator($this->elements);
    }

    /**
     *
     * @return set
     */
    protected function load_elements()
    {
        return $this;
    }

    /**
     *
     * @param string $offset
     * @param string $value
     * @return Void
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
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->elements[$offset]);
    }

    /**
     *
     * @param string $offset
     * @return Void
     */
    public function offsetUnset($offset)
    {
        unset($this->elements[$offset]);
    }

    /**
     *
     * @param string $offset
     * @return record_adapter
     */
    public function offsetGet($offset)
    {
        return isset($this->elements[$offset]) ? $this->elements[$offset] : null;
    }

    /**
     *
     * @return int
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
     * @return Int
     */
    public function get_count()
    {
        return count($this->elements);
    }

    /**
     *
     * @return int
     */
    public function get_count_groupings()
    {
        $n = 0;
        foreach ($this->elements as $record) {
            if ($record->is_grouping())
                $n ++;
        }

        return $n;
    }

    /**
     *
     * @return Array
     */
    public function get_elements()
    {
        $this->load_elements();

        return $this->elements;
    }

    /**
     *
     * @param record_Interface $record
     * @return set
     */
    public function add_element(record_Interface &$record)
    {
        $this->elements[$record->get_serialize_key()] = $record;

        return $this;
    }

    /**
     *
     * @param record_Interface $record
     * @return set
     */
    public function remove_element(record_Interface &$record)
    {
        $key = $record->get_serialize_key();
        if (isset($this->elements[$key]))
            unset($this->elements[$key]);

        return $this;
    }

    /**
     *
     * @return string
     */
    public function serialize_list()
    {
        $basrec = array();
        foreach ($this->elements as $record) {
            $basrec[] = $record->get_serialize_key();
        }

        return implode(';', $basrec);
    }
}
