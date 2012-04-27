<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media\Subdef\OptionType;

/**
 * Boolean Subdef Option
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Boolean implements OptionType
{
    protected $name;
    protected $displayName;
    protected $defaultValue;
    protected $value;

    public function __construct($displayName, $name, $defaultValue = null)
    {
        $this->displayName = $displayName;
        $this->name = $name;
        $this->defaultValue = $defaultValue;

        if ($defaultValue) {
            $this->setValue($defaultValue);
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

    public function getDisplayName()
    {
        return $this->displayName;
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
