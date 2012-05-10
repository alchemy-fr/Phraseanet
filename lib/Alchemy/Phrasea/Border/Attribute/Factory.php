<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border\Attribute;

/**
 * This factory is intended to create Attribute based on their name and
 * serialized values. This is mostly used when reading Lazaret tables
 */
class Factory
{

    /**
     * Build a file package Attribute
     *
     * @param   string      $name       The name of the attribute, one of the
     *                                  Attribute::NAME_* constants
     * @param   string      $serialized The serialized value of the attribute
     *                                  (Attribute::asString result)
     * @return  Attribute   The attribute
     * @throws  \InvalidArgumentException
     */
    public static function getFileAttribute($name, $serialized)
    {
        switch ($name) {
            case Attribute::NAME_METADATA:
                return Metadata::loadFromString($serialized);
                break;
            case Attribute::NAME_STORY:
                return Story::loadFromString($serialized);
                break;
        }

        throw new \InvalidArgumentException(sprintf('Unknown attribute %s', $name));
    }
}
