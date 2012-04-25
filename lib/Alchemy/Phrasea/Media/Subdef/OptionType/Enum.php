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
