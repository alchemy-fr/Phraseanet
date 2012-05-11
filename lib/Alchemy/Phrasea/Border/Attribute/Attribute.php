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
 * File attribute interface
 */
interface Attribute
{
    const NAME_METADATA = 'metadata';
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
     */
    public static function loadFromString($string);
}
