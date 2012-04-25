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
 * @package     APIv1
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class API_V1_exception_abstract extends Exception
{
    protected static $details;

    public function __construct()
    {
        return $this;
    }

    public static function get_details()
    {
        return static::$details;
    }
}
