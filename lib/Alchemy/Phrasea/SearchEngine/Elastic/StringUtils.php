<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

class StringUtils
{
    public static function slugify($string, $separator = '-')
    {
        // Replace non letter or digits by _
        $string = preg_replace('/[^\\pL\d]+/u', $separator, $string);
        $string = trim($string, $separator);

        // Transliterate
        if (function_exists('iconv')) {
            $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        }

        // Remove non wording characters
        $string = preg_replace('/[^-\w]+/', '', $string);
        $string = strtolower($string);

        return $string;
    }
}
