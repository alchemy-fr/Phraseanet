<?php

namespace Alchemy\Phrasea\Media\Subdef\OptionType;

class Enum implements OptionType
{

    protected $default_value;
    protected $value;
    protected $available;

    public function __construct($name, Array $available, $default_value = null)
    {
        $this->name = $name;
        $this->available = $available;
        $this->default_value = $default_value;

        if ($default_value)
        {
            $this->setValue($default_value);
        }
    }

    public function setValue($value)
    {
        if ( ! in_array($value, $this->available))
        {
            throw new \Exception_InvalidArgument('The value provided does not fit in range');
        }

        $this->value = $value;

        return $this;
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