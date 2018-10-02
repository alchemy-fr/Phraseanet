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

class Multi implements OptionType
{
    protected $name;
    protected $displayName;
    protected $defaultValue;
    protected $available;

    public function __construct($displayName, $name, Array $available, $defaultValue = null)
    {
        $this->displayName = $displayName;
        $this->name = $name;
        $this->available = [];
        foreach ($available as $a) {
            $this->available[$a] = false;
        }
        $this->defaultValue = $defaultValue;

        if ($defaultValue) {
            $this->setValue($defaultValue);
        }
    }

    public function setValue($value)
    {
        foreach ($this->available as $k => $v) {
            $this->available[$k] = false;
        }

        if (! $value) {
            return $this;
        }

        foreach ((array) $value as $v) {
            if ( ! array_key_exists($v, $this->available)) {
                throw new \Exception_InvalidArgument(
                    sprintf(
                        'The value provided `%s` for %s does not fit in range ; available are %s'
                        , $value
                        , $this->getName()
                        , implode(', ', $this->getAvailableValues())
                    )
                );
            }

            $this->available[$v] = true;
        }

        return $this;
    }

    public function getDisplayName()
    {
        return $this->displayName;
    }

    public function getType()
    {
        return self::TYPE_MULTI;
    }

    public function getAvailableValues()
    {
        return array_keys($this->available);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue($all = false)
    {
        if ($all) {
            return $this->available;
        }

        $value = [];

        foreach ($this->available as $a => $selected) {
            if ($selected) {
                $value[] = $a;
            }
        }

        return $value;
    }
}
