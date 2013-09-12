<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Version
{
    protected static $number = '3.9.0.a7';
    protected static $name = 'Diplodocus';

    public static function getNumber()
    {
        return static::$number;
    }

    public static function getName()
    {
        return static::$name;
    }
}
