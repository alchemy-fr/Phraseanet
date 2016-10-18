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

use LogicException;
use RuntimeException;

class Mapping
{

    const DATE_FORMAT_MYSQL = 'yyyy-MM-dd HH:mm:ss';
    const DATE_FORMAT_CAPTION = 'yyyy/MM/dd'; // ES format
    const DATE_FORMAT_MYSQL_OR_CAPTION = 'yyyy-MM-dd HH:mm:ss||yyyy/MM/dd';
    const DATE_FORMAT_CAPTION_PHP = 'Y/m/d';  // PHP format

    // Core types
    const TYPE_STRING  = 'string';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE    = 'date';

    // Number core types
    const TYPE_FLOAT   = 'float';
    const TYPE_DOUBLE  = 'double';
    const TYPE_INTEGER = 'integer';
    const TYPE_LONG    = 'long';
    const TYPE_SHORT   = 'short';
    const TYPE_BYTE    = 'byte';
    const TYPE_IP      = 'ip';

    // Compound types
    const TYPE_OBJECT  = 'object';

    private static $types = array(
        self::TYPE_STRING,
        self::TYPE_BOOLEAN,
        self::TYPE_DATE,
        self::TYPE_FLOAT,
        self::TYPE_DOUBLE,
        self::TYPE_INTEGER,
        self::TYPE_LONG,
        self::TYPE_SHORT,
        self::TYPE_BYTE,
        self::TYPE_IP,
    );

    public static function disabledMapping()
    {
        return (new self())->disable();
    }

    /**
     * @var array
     */
    private $fields = array();

    /**
     * @var string
     */
    private $current;

    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * @param string $name
     * @param string|Mapping $type
     * @return $this
     */
    public function add($name, $type)
    {
        if ($type instanceof self) {
            return $this->addComplexType($name, $type);
        }

        if (! in_array($type, self::$types)) {
            throw new RuntimeException(sprintf(
                'Invalid field mapping type "%s", expected "%s" or Mapping instance.',
                $type,
                implode('", "', self::$types)
            ));
        }

        return $this->addFieldConfiguration($name, [ 'type' => $type ]);
    }

    /**
     * @param $name
     * @param Mapping $typeMapping
     * @return $this
     */
    public function addComplexType($name, Mapping $typeMapping)
    {
        return $this->addFieldConfiguration($name, [
            'type' => self::TYPE_OBJECT,
            'mapping' => $typeMapping
        ]);
    }

    /**
     * @param $name
     * @param array $configuration
     * @return $this
     */
    private function addFieldConfiguration($name, array $configuration)
    {
        $this->fields[$name] = $configuration;
        $this->current = $name;

        return $this;
    }

    /**
     * @return array
     */
    public function export()
    {
        $mapping = array();
        $mapping['properties'] = array();

        foreach ($this->fields as $name => $field) {
            if ($field['type'] === self::TYPE_OBJECT) {
                $field = $field['mapping']->export();
            }

            $mapping['properties'][$name] = $field;
        }

        if (!$this->enabled) {
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

    public function addRawVersion()
    {
        $field = & $this->currentField();

        $field['fields']['raw'] = [
            'type' => $field['type'],
            'index' => 'not_analyzed'
        ];

        return $this;
    }

    /**
     * @deprecated
     */
    public function addAnalyzedVersion(array $locales)
    {
        $this->addMultiField('light', 'general_light');

        return $this->addLocalizedSubfields($locales);
    }

    public function addLocalizedSubfields(array $locales)
    {
        foreach ($locales as $locale) {
            $this->addMultiField($locale, sprintf('%s_full', $locale));
        }

        return $this;
    }

    public function addMultiField($name, $analyzer = null)
    {
        $field = &$this->currentField();

        if (isset($field['fields'][$name])) {
            throw new LogicException(sprintf('There is already a "%s" multi field.', $name));
        }

        $field['fields'][$name] = array();
        $field['fields'][$name]['type'] = $field['type'];

        if ($analyzer) {
            $field['fields'][$name]['analyzer'] = $analyzer;
        }

        return $this;
    }

    public function enableTermVectors($recursive = false)
    {
        $field = &$this->currentField();

        if ($field['type'] !== self::TYPE_STRING) {
            throw new LogicException('Only string fields can have term vectors');
        }

        $field['term_vector'] = 'with_positions_offsets';

        if ($recursive) {
            if (isset($field['fields'])) {
                foreach ($field['fields'] as $name => &$options) {
                    $options['term_vector'] = 'with_positions_offsets';
                }
            }
        }

        return $this;
    }

    public function has($name)
    {
        return isset($this->fields[$name]);
    }

    protected function &currentField()
    {
        if (null === $this->current) {
            throw new LogicException('You must add a field first');
        }

        return $this->fields[$this->current];
    }
}
