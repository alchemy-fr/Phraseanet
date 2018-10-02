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

class Range implements OptionType
{
    protected $name;
    protected $displayName;
    protected $minValue;
    protected $maxValue;
    protected $defaultValue;
    protected $value;
    protected $step;

    public function __construct($displayName, $name, $minValue, $maxValue, $defaultValue = null, $step = 1)
    {
        $this->displayName = $displayName;
        $this->name = $name;
        $this->minValue = $minValue;
        $this->maxValue = $maxValue;
        $this->defaultValue = $defaultValue;
        $this->step = $step;

        if ($defaultValue) {
            $this->setValue($defaultValue);
        }
    }

    public function setValue($value)
    {
        if (!$value) {
            $this->value = null;

            return $this;
        }

        if ($value > $this->maxValue || $value < $this->minValue) {
            throw new \Exception_InvalidArgument(
                sprintf(
                    'The value `%s` provided for %s does not fit in range (%s - %s)', $value, $this->name, $this->minValue, $this->maxValue
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
        return self::TYPE_RANGE;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getStep()
    {
        return $this->step;
    }

    public function getMinValue()
    {
        return $this->minValue;
    }

    public function getMaxValue()
    {
        return $this->maxValue;
    }
}
