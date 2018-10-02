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

class Enum implements OptionType
{
    protected $name;
    protected $displayName;
    protected $defaultValue;
    protected $available;
    protected $value;

    public function __construct($displayName, $name, Array $available, $defaultValue = null)
    {
        $this->displayName = $displayName;
        $this->name = $name;
        $this->available = $available;
        $this->defaultValue = $defaultValue;

        if ($defaultValue) {
            $this->setValue($defaultValue);
        }
    }

    public function setValue($value)
    {
        if (! $value) {
            $this->value = null;

            return $this;
        }

        if ( ! in_array($value, $this->available)) {
            throw new \Exception_InvalidArgument(
                sprintf(
                    'The value provided `%s` for %s does not fit in range ; available are %s'
                    , $value
                    , $this->getName()
                    , implode(', ', $this->getAvailableValues())
                )
            );
        }

        $this->value = $value;

        return $this;
    }

    public function getDisplayName()
    {
        return $this->displayName;
    }

    public function getType()
    {
        return self::TYPE_ENUM;
    }

    public function getAvailableValues()
    {
        return $this->available;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }
}
