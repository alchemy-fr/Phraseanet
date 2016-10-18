<?php

/*
 * This file is part of phrasea-4.0.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

abstract class FieldMapping
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

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $indexed = true;


    /**
     * @param string $name
     * @param string $type
     */
    public function __construct($name, $type)
    {
        if (trim($name) == '') {
            throw new \InvalidArgumentException('Field name is required');
        }

        if (! in_array($type, self::$types)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid field mapping type "%s", expected "%s" or Mapping instance.',
                $type,
                implode('", "', self::$types)
            ));
        }

        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isIndexed()
    {
        return $this->indexed;
    }

    public function enableIndexing()
    {
        $this->indexed = true;

        return $this;
    }

    public function disableIndexing()
    {
        $this->indexed = false;

        return $this;
    }

    /**
     * @return array
     */
    abstract public function toArray();

    /**
     * Helper function to append custom field properties to generic properties array
     *
     * @param array $fieldProperties
     * @return array
     */
    protected function buildArray(array $fieldProperties = [])
    {
        return array_merge([
            'type' => $this->getType(),
            'index' => $this->indexed ? 'yes' : 'no'
        ], $fieldProperties);
    }
}
