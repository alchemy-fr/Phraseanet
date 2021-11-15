<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media\Subdef\OptionType;

class Text implements OptionType
{
    protected $name;
    protected $displayName;
    protected $defaultValue;
    protected $value;

    public function __construct($displayName, $name, $defaultValue = '')
    {
        $this->displayName = $displayName;
        $this->name = $name;
        $this->defaultValue = $defaultValue;

        if ($defaultValue) {
            $this->setValue($defaultValue);
        }
    }

    public function getDisplayName()
    {
        return $this->displayName;
    }

    public function getType()
    {
        return self::TYPE_TEXT;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        if (!$value) {
            $this->value = '';

            return $this;
        }

        $this->value = $value;

        return $this;
    }
}
