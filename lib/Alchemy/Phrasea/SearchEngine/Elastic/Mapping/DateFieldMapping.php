<?php

/*
 * This file is part of phrasea-4.0.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Mapping;

/**
 * Class DateFieldMapping
 * @package Alchemy\Phrasea\SearchEngine\Elastic\Mapping
 */
class DateFieldMapping extends ComplexFieldMapping
{
    /**
     * @var string
     */
    private $format;

    /**
     * @param string $name
     * @param string $format
     */
    public function __construct($name, $format)
    {
        parent::__construct($name, self::TYPE_DATE);

        $this->format = $format;
    }

    /**
     * @param string $format
     * @return $this
     */
    public function setDateFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        return $this->format;
    }

    /**
     * @return array
     */
    protected function getProperties()
    {
        return array_merge([
            'format' => $this->format,
            'ignore_malformed' => true
            ], parent::getProperties());
    }
}
