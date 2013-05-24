<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class databox_descriptionStructure implements IteratorAggregate
{
    /**
     *
     * @var Array
     */
    protected $elements = array();

    /**
     * Cache array for the get element by name function
     *
     * @var Array
     */
    protected $cache_name_id = array();

    /**
     *
     * @return databox_field
     */
    public function getIterator()
    {
        return new ArrayIterator($this->elements);
    }

    /**
     *
     * @param  databox_field                $field
     * @return databox_descriptionStructure
     */
    public function add_element(databox_field $field)
    {
        $this->elements[$field->get_id()] = $field;

        return $this;
    }

    /**
     *
     * @param  databox_field                $field
     * @return databox_descriptionStructure
     */
    public function remove_element(databox_field $field)
    {
        if (isset($this->elements[$field->get_id()]))
            unset($this->elements[$field->get_id()]);

        return $this;
    }

    /**
     *
     * @return array
     */
    public function get_elements()
    {
        return $this->elements;
    }

    /**
     *
     * @param  int           $id
     * @return databox_field
     */
    public function get_element($id)
    {
        if ( ! isset($this->elements[$id]))
            throw new Exception_Databox_FieldNotFound ();

        return $this->elements[$id];
    }

    /**
     *
     * @param  string        $name
     * @return databox_field
     */
    public function get_element_by_name($name)
    {
        $name = databox_field::generateName($name);

        if (isset($this->cache_name_id[$name])) {
            return $this->elements[$this->cache_name_id[$name]];
        }

        foreach ($this->elements as $id => $meta) {
            if ($meta->get_name() === $name) {
                $this->cache_name_id[$name] = $id;

                return $meta;
            }
        }

        return null;
    }

    public function get_dces_field($label)
    {
        foreach ($this->elements as $field) {
            if (null !== $dces_element = $field->get_dces_element()) {
                if ($label === $dces_element->get_label()) {
                    return $field;
                }
            }
        }

        return null;
    }

    /**
     *
     * @param  string  $id
     * @return boolean
     */
    public function isset_element($id)
    {
        return isset($this->elements[$id]);
    }

    public function toArray()
    {
        return array_map(function ($element) {
            return $element->toArray();
        }, array_values($this->elements));
    }
}
