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
    private $fields = array();
    private $current;

    const DATE_FORMAT_MYSQL = 'yyyy-MM-dd HH:mm:ss';
    const DATE_FORMAT_CAPTION = 'yyyy/MM/dd';

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
    );

    public function add($name, $type)
    {
        $field = array();
        if ($type instanceof self) {
            $field['type'] = self::TYPE_OBJECT;
            $field['properties'] = $type;
        }
        elseif (in_array($type, self::$types)) {
            $field['type'] = $type;
        } else {
            throw new RuntimeException(sprintf(
                'Invalid field mapping type "%s", expected "%s" or Mapping instance.',
                $type,
                implode('", "', self::$types)
            ));
        }

        $this->fields[$name] = $field;
        $this->current = $name;

        return $this;
    }

    public function export()
    {
        return ['properties' => $this->exportProperties()];
    }

    public function exportProperties()
    {
        $properties = array();
        foreach ($this->fields as $name => $field) {
            $properties[$name] = $field;
            if ($field['type'] === self::TYPE_OBJECT) {
                $properties[$name]['properties'] = $field['properties']->exportProperties();
            }
        }

        return $properties;
    }

    public function notAnalyzed()
    {
        $field = &$this->currentField();
        if ($field['type'] !== self::TYPE_STRING) {
            throw new LogicException('Only string fields can be not analyzed');
        }
        $field['index'] = 'not_analyzed';

        return $this;
    }

    public function notIndexed()
    {
        $field = &$this->currentField();
        $field['index'] = 'no';

        return $this;
    }

    public function addRawVersion()
    {
        $field = &$this->currentField();

        $field['fields']['raw'] = [
            'type' => $field['type'],
            'index' => 'not_analyzed'
        ];

        return $this;
    }

    public function addAnalyzedVersion(array $langs)
    {
        $field = &$this->currentField();

        foreach ($langs as $lang) {
            $field['fields'][$lang] = [
                'type' => $field['type'],
                'analyzer' => sprintf('%s_full', $lang)
            ];
        }

        $field['fields']['light'] = [
            'type' => $field['type'],
            'analyzer' => 'general_light'
        ];

        return $this;
    }

    public function format($format)
    {
        $field = &$this->currentField();
        if ($field['type'] !== self::TYPE_DATE) {
            throw new LogicException('Only date fields can have a format');
        }
        $field['format'] = $format;

        return $this;
    }

    protected function &currentField()
    {
        if (null === $this->current) {
            throw new LogicException('You must add a field first');
        }

        return $this->fields[$this->current];
    }
}
