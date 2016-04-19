<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Search;

use Assert\Assertion;

class CaptionView
{
    /**
     * @var \caption_record
     */
    private $caption;

    /**
     * @var \caption_field[]
     */
    private $fields = [];

    public function __construct(\caption_record $caption)
    {
        $this->caption = $caption;
    }

    /**
     * @return \caption_record
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * @param \caption_field[] $fields
     */
    public function setFields($fields)
    {
        Assertion::allIsInstanceOf($fields, \caption_field::class);

        $this->fields = [];

        foreach ($fields as $field) {
            $this->fields[$field->get_name()] = $field;
        }
    }

    /**
     * @return \caption_field[]
     */
    public function getFields()
    {
        return $this->fields;
    }
}
