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
 * File attribute interface
 */
interface AttributeInterface
{
    const NAME_METADATA = 'metadata';
    const NAME_METAFIELD = 'metafield';
    const NAME_STORY = 'story';
    const NAME_STATUS = 'status';

    /**
     * Return the name of the attribute, one of the self::NAME_* constants
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the value
     */
    public function getValue();

    /**
     * Returns the value as a string
     *
     * @return string
     */
    public function asString();

    /**
     * Build the current object with is string value
     *
     * @param Application $app    the application context
     * @param string      $string the serialized string
     *
     * @throws \InvalidArgumentException
     */
    public static function loadFromString(Application $app, $string);
}
