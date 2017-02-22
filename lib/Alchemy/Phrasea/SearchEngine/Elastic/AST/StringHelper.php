<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;


class StringHelper
{
    public static function escape($s)
    {
        return is_string($s) ? str_replace(["\"", "\\"], ["\\\"", "\\\\"], $s) : $s;
    }

    public static function unescape($s)
    {
        return is_string($s) ? str_replace(["\\\"", "\\\\"], ["\"", "\\"], $s) : $s;
    }
}
