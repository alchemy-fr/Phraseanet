<?php

class p4field
{

    public static function isyes($v)
    {
        $v = mb_strtolower(trim($v));

        return($v == '1' || $v == 'y' || $v == 'yes' || $v == 'o' || $v == 'oui' || $v == 'on' || $v == 'true');
    }

    public static function isno($v)
    {
        $v = mb_strtolower(trim($v));

        return($v == '0' || $v == 'n' || $v == 'no' || $v == 'non' || $v = 'off' || $v == 'false');
    }
}
