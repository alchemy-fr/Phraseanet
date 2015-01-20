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
    private $enabled = true;

    const DATE_FORMAT_MYSQL = 'yyyy-MM-dd HH:mm:ss';
    const DATE_FORMAT_CAPTION = 'yyyy/MM/dd'; // ES format
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
            $field['mapping'] = $type;
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
        $mapping = array();
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

    public function analyzer($analyzer, $type = null)
    {
        $field = &$this->currentField();
        if ($field['type'] !== self::TYPE_STRING) {
            throw new LogicException('Only string fields can be analyzed');
        }
        switch ($type) {
            case null:
                $field['analyzer'] = $analyzer;
                unset($field['index_analyzer'], $field['search_analyzer']);
                break;
            case 'indexing':
                $field['index_analyzer'] = $analyzer;
                break;
            case 'searching':
                $field['search_analyzer'] = $analyzer;
                break;
            default:
                throw new LogicException(sprintf('Invalid analyzer type "%s".', $type));
        }
        $field['index'] = 'analyzed';

        return $this;
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

    public static function disabledMapping()
    {
        return (new self())->disable();
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
        $field = &$this->currentField();

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
        $field = &$this->currentField();
        $field['fields']['light'] = [
            'type' => $field['type'],
            'analyzer' => 'general_light'
        ];

        return $this->addLocalizedSubfields($locales);
    }

    public function addLocalizedSubfields(array $locales)
    {
        $field = &$this->currentField();

        foreach ($locales as $locale) {
            $field['fields'][$locale] = array();
            $field['fields'][$locale]['type'] = $field['type'];
            $field['fields'][$locale]['analyzer'] = sprintf('%s_full', $locale);
        }

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
