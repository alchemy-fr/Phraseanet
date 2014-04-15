<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class caption_Field_ThesaurusValue
{
    /** @var string */
    private $value;
    /** @var \databox_field */
    private $field;
    /** @var string */
    private $query;

    public function __construct($value, \databox_field $field, $query)
    {
        $this->value = $value;
        $this->field = $field;
        $this->query = $query;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function __toString()
    {
        return $this->value;
    }
}
