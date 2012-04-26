<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface metadata_Interface
{
    const TYPE_STRING = 'string';
    const TYPE_DIGITS = 'digits';
    const TYPE_RATIONAL64 = 'rational64';
    const TYPE_BINARY = 'binary';
    const TYPE_INT8U = 'int8u';
    const TYPE_DATETIME = 'datetime';
    const TYPE_DATE = 'date';
    const TYPE_TIME = 'time';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_FLOAT = 'float';
    const TYPE_INT16U = 'int16u';
    const TYPE_INT32U = 'int32u';
    const TYPE_INTEGER = 'integer';
    const TYPE_LANGALT = 'langalt';
    const TYPE_REAL = 'real';

    public static function get_source();

    public static function get_namespace();

    public static function get_tagname();

    public static function is_multi();

    public static function maxlength();

    public static function minlength();

    public static function get_type();

    public static function is_deprecated();

    public static function available_values();

    public static function is_readonly();

    public static function is_mandatory();
}
