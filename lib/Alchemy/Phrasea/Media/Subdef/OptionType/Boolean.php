<?php

namespace Alchemy\Phrasea\Media\Subdef\OptionType;

class Boolean implements OptionType
{

    protected $name;
    protected $default_value;
    protected $value;

    public function __construct($name, $default_value = null)
    {
        $this->name = $name;
        $this->default_value = $default_value;

        if ($default_value)
        {
            $this->setValue($default_value);
        }
    }

    public function setValue($value)
    {
        $this->value = (boolean) $value;

        return $this;
    }

    public function getType()
    {
        return self::TYPE_BOOLEAN;
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