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

class StringHelper
{
    /**
     * @param string $str
     * @return string
     */
    public static function crlfNormalize($str)
    {
        return str_replace(["\r\n", "\r"], "\n", $str);
    }

    /**
     * @param string $str
     * @param string $separator
     * @param bool $pascalCase
     * @return string
     */
    public static function camelize($str, $separator = '_', $pascalCase = false)
    {
        $transformStr = str_replace(' ', '', ucwords(str_replace($separator, ' ', $str)));

        return $pascalCase ? $transformStr : lcfirst($transformStr);
    }

    const SQL_VALUE      = '\'';
    const SQL_IDENTIFIER = '`';

    public static function SqlQuote($s, $quote)
    {
        return $quote . str_replace($quote, $quote.$quote, $s) . $quote;
    }
}
