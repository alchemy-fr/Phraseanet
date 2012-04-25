<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media\Subdef\OptionType;

class Range implements OptionType
{
    protected $min_value;
    protected $max_value;
    protected $default_value;
    protected $value;
    protected $step;

    public function __construct($name, $min_value, $max_value, $default_value = null, $step = 1)
    {
        $this->name = $name;
        $this->min_value = $min_value;
        $this->max_value = $max_value;
        $this->default_value = $default_value;
        $this->step = $step;

        if ($default_value) {
            $this->setValue($default_value);
        }
    }

    public function setValue($value)
    {
        if ($value > $this->max_value || $value < $this->min_value) {
            throw new \Exception_InvalidArgument('The value provided does not fit in range');
        }

        $this->value = $value;

        return $this;
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
        return $this->min_value;
    }

    public function getMaxValue()
    {
        return $this->max_value;
    }
}
