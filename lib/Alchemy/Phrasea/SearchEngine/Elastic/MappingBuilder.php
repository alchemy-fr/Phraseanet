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

use Alchemy\Phrasea\SearchEngine\Elastic\Mapping\ComplexFieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping\DateFieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping\StringFieldMapping;

class MappingBuilder
{
    /**
     * @var Mapping
     */
    private $mapping;

    public function __construct()
    {
        $this->mapping = new Mapping();
    }

    /**
     * @param string $name
     * @return StringFieldMapping
     */
    public function addStringField($name)
    {
        return $this->mapping->addField(new StringFieldMapping($name));
    }

    /**
     * @param string $name
     * @return FieldMapping
     */
    public function addIntegerField($name)
    {
        return $this->mapping->addField(new FieldMapping($name, FieldMapping::TYPE_INTEGER));
    }

    /**
     * @param string $name
     * @return FieldMapping
     */
    public function addLongField($name)
    {
        return $this->mapping->addField(new FieldMapping($name, FieldMapping::TYPE_LONG));
    }

    /**
     * @param string $name
     * @return FieldMapping
     */
    public function addObjectField($name)
    {
        return $this->mapping->addField(new ComplexFieldMapping($name, FieldMapping::TYPE_OBJECT));
    }

    /**
     * @param string $name
     * @param string $format
     * @return DateFieldMapping
     */
    public function addDateField($name, $format)
    {
        return $this->mapping->addField(new DateFieldMapping($name, $format));
    }

    /**
     * @param string $name
     * @param string $type
     * @return FieldMapping
     */
    public function addField($name, $type)
    {
        return $this->mapping->addField(new FieldMapping($name, $type));
    }

    /***
     * @param FieldMapping $fieldMapping
     * @return FieldMapping
     */
    public function add(FieldMapping $fieldMapping)
    {
        return $this->mapping->addField($fieldMapping);
    }

    /**
     * @return Mapping
     */
    public function getMapping()
    {
        return $this->mapping;
    }
}
