<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class thesaurus
{

    public static function xquery_escape($s)
    {
        return(str_replace(["&", "\"", "'"], ["&amp;", "&quot;", "&apos;"], $s));
    }
}
