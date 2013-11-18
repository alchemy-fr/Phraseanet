<?php

class thesaurus
{

    public static function xquery_escape($s)
    {
        return(str_replace(["&", "\"", "'"], ["&amp;", "&quot;", "&apos;"], $s));
    }
}
