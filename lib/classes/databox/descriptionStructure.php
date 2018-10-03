<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Assert\Assertion;

class databox_descriptionStructure implements IteratorAggregate, Countable
{
    /**
     * @var databox_field[]
     */
    protected $elements = [];

    /** @var  unicode */
    private $unicode;

    const STRICT_COMPARE = 1;
    const SLUG_COMPARE = 2;

    /**
     * Cache array for the get element by name function
     *
     * @var int[]|null
     */
    protected $cache_name_id;

    /**
     * @param databox_field[] $fields
     * @param unicode $unicode
     */
    public function __construct($fields, unicode $unicode)
    {
        $this->unicode = $unicode;
        Assertion::allIsInstanceOf($fields, databox_field::class);

        foreach ($fields as $field) {
            $this->add_element($field);
        }
    }

    /**
     * @return Iterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->elements);
    }

    /**
     * @param  databox_field $field
     * @return $this
     */
    public function add_element(databox_field $field)
    {
        $this->elements[$field->get_id()] = $field;

        if (null !== $this->cache_name_id) {
            $this->cache_name_id[$field->get_name()] = $field->get_id();
        }

        return $this;
    }

    /**
     * @param  databox_field $field
     * @return $this
     */
    public function remove_element(databox_field $field)
    {
        if (isset($this->elements[$field->get_id()])) {
            unset($this->elements[$field->get_id()], $this->cache_name_id[$field->get_name()]);
        }

        return $this;
    }

    /**
     * @return databox_field[]
     */
    public function get_elements()
    {
        return $this->elements;
    }

    /**
     * @param int $id
     * @return databox_field
     */
    public function get_element($id)
    {
        if (!isset($this->elements[$id])) {
            throw new Exception_Databox_FieldNotFound ();
        }

        return $this->elements[$id];
    }

    /**
     * @param  string $name
     * @param int $compareMode      // use STRICT_COMPARE if the name already comes from phrasea (faster)
     *
     * @return databox_field|null
     */
    public function get_element_by_name($name, $compareMode=self::SLUG_COMPARE)
    {
        if (null === $this->cache_name_id) {
            $this->cache_name_id = [];

            foreach ($this->elements as $id => $meta) {
                $this->cache_name_id[$meta->get_name()] = $id;
            }
        }

        if($compareMode == self::SLUG_COMPARE) {
            $name = databox_field::generateName($name, $this->unicode);
        }

        return isset($this->cache_name_id[$name])
            ? $this->elements[$this->cache_name_id[$name]]
            : null;
    }

    /**
     * @return databox_field[]
     */
    public function getDcesFields()
    {
        return array_filter($this->elements, function (databox_field $field) {
            return null !== $field->get_dces_element();
        });
    }

    /**
     * @param string $label
     * @return databox_field|null
     */
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
     * @param  string  $id
     * @return bool
     */
    public function isset_element($id)
    {
        return isset($this->elements[$id]);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_map(function (databox_field $element) {
            return $element->toArray();
        }, array_values($this->elements));
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->elements);
    }
}
