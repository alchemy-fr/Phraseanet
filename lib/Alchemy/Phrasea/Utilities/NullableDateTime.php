<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Utilities;

final class NullableDateTime
{
    public static function format(\DateTime $dateTime = null, $format = DATE_ATOM, $default = null)
    {
        return $dateTime ? $dateTime->format($format) : $default;
    }
}
