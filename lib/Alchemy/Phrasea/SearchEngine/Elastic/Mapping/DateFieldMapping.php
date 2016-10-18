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

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;

/**
 * Class DateFieldMapping
 * @package Alchemy\Phrasea\SearchEngine\Elastic\Mapping
 */
class DateFieldMapping extends FieldMapping
{
    /**
     * @var string
     */
    private $format;

    /**
     * @param string $name
     * @param string $type
     * @param string $format
     */
    public function __construct($name, $type, $format)
    {
        parent::__construct($name, $type);

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
    public function toArray()
    {
        return $this->buildArray([ 'format' => $this->format ]);
    }
}
