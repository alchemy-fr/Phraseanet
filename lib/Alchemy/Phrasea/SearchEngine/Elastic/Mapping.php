<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

class Mapping
{

    public static function disabledMapping()
    {
        return (new self())->disable();
    }

    /**
     * @var FieldMapping[]
     */
    private $fields = array();

    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * @param FieldMapping $fieldMapping
     * @return FieldMapping
     */
    public function addField(FieldMapping $fieldMapping)
    {
        return $this->fields[$fieldMapping->getName()] = $fieldMapping;
    }

    /**
     * @param string $name
     * @return bool
     * @deprecated Use hasField instead
     */
    public function has($name)
    {
        return $this->hasField($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasField($name)
    {
        return isset($this->fields[$name]);
    }

    public function removeField($name)
    {
        if ($this->has($name)) {
            $field = $this->fields[$name];

            unset($this->fields[$name]);

            return $field;
        }

        throw new \InvalidArgumentException('Mapping does not contain field: ' . $name);
    }

    /**
     * @return array
     */
    public function export()
    {
        $mapping = array();
        $mapping['properties'] = array();

        foreach ($this->fields as $name => $field) {
            $mapping['properties'][$name] = $field->toArray();
        }

        if (! $this->enabled) {
            $mapping['enabled'] = false;
        }

        return $mapping;
    }

    /**
     * Allows to disable parsing and indexing a named object completely.
     * This is handy when a portion of the JSON document contains arbitrary JSON
     * which should not be indexed, nor added to the mapping.
     */
    private function disable()
    {
        $this->enabled = false;

        return $this;
    }
}
