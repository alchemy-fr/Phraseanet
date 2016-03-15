<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border\Attribute;

use Alchemy\Phrasea\Application;

/**
 * This factory is intended to create Attribute based on their name and
 * serialized values. This is mostly used when reading Lazaret tables
 */
class Factory
{

    /**
     * Build a file package Attribute
     *
     * @param  Application               $app        Application context
     * @param  string                    $name       The name of the attribute, one of the
     *                                               AttributeInterface::NAME_* constants
     * @param  string                    $serialized The serialized value of the attribute
     *                                               (AttributeInterface::asString result)
     * @return AttributeInterface        The attribute
     * @throws \InvalidArgumentException
     */
    public static function getFileAttribute(Application $app, $name, $serialized)
    {
        switch ($name)
        {
            case AttributeInterface::NAME_METADATA:
                return Metadata::loadFromString($app, $serialized);
            case AttributeInterface::NAME_STORY:
                return Story::loadFromString($app, $serialized);
            case AttributeInterface::NAME_METAFIELD:
                return MetaField::loadFromString($app, $serialized);
            case AttributeInterface::NAME_STATUS:
                return Status::loadFromString($app, $serialized);
        }

        throw new \InvalidArgumentException(sprintf('Unknown attribute %s', $name));
    }
}
