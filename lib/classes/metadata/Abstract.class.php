<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class metadata_Abstract
{
    const MULTI = false;
    const MIN_LENGTH = 0;
    const DEPRECATED = false;
    const SOURCE = null;
    const NAME_SPACE = null;
    const TAGNAME = null;
    const MAX_LENGTH = null;
    const TYPE = null;
    const READONLY = false;
    const MANDATORY = false;

    /**
     * Tells if the metadata source is MultiValued
     *
     * @return boolean
     */
    public static function is_multi()
    {
        return static::MULTI;
    }

    /**
     * Returns the minimum character required for the metadata
     * Returns 0 if not minimal length
     *
     * @return int
     */
    public static function minLength()
    {
        return static::MIN_LENGTH;
    }

    /**
     * Tells if the metadata is deprecated
     *
     * @return boolean
     */
    public static function is_deprecated()
    {
        return static::DEPRECATED;
    }

    /**
     * Return the source value as an xpath value like
     *  /rdf:RDF/rdf:Description/NAMESPACE:tagname
     *
     * @return string
     */
    public static function get_source()
    {
        return static::SOURCE;
    }

    /**
     * Retuns the namespace of the metadata
     *
     * @return string
     */
    public static function get_namespace()
    {
        return static::NAME_SPACE;
    }

    /**
     * Returns the tagname of the metadata
     *
     * @return string
     */
    public static function get_tagname()
    {
        return static::TAGNAME;
    }

    /**
     * Returns the maximum character required for the metadata
     * Returns 0 if not maximal length
     *
     * @return int
     */
    public static function maxlength()
    {
        return static::MAX_LENGTH;
    }

    /**
     * Returns the type, one of the metadata_interface::TYPE_* values
     *
     * @return string
     */
    public static function get_type()
    {
        return static::TYPE;
    }

    /**
     * Returns an associative array of the avalaible values :
     * Keys are values and values are filled with the actual meaning of the value
     *
     * @return Array
     */
    public static function available_values()
    {
        return array();
    }

    /**
     * Returns true is the value is readonly
     *
     * @return boolean
     */
    public static function is_readonly()
    {
        return static::READONLY;
    }

    /**
     * Returns true if the value is mandatory
     *
     * @return boolean
     */
    public static function is_mandatory()
    {
        return static::MANDATORY;
    }
}
