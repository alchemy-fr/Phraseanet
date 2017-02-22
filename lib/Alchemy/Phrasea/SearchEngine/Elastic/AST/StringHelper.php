<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;


class StringHelper
{
    public static function escape($s)
    {
        return str_replace(["\"", "\\"], ["\\\"", "\\\\"], $s);
    }

    public static function unescape($s)
    {
        return str_replace(["\\\"", "\\\\"], ["\"", "\\"], $s);
    }
}
