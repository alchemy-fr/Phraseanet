<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class p4string
{
    public static function addEndSlash($path)
    {
        if ($path == "") {
            $path = getcwd();
        }

        $lastCharacter = substr($path, -1, 1);

        if ( ! in_array($lastCharacter, ['\\', '/'])) {
            $path .= DIRECTORY_SEPARATOR;
        }

        return $path;
    }

    /**
     * deprecated
     *
     * @param  type $s
     * @return type
     */
    public static function JSstring($s)
    {
        return(str_replace(["\\", "\"", "\r", "\n"], ["\\\\", "\\\"", "\\r", "\\n"], $s));
    }

    /**
     * deprecated
     *
     * @param string $s
     * @param string $context
     * @param string $quoted
     */
    public static function MakeString($s, $context = 'html', $quoted = '')
    {
        switch (mb_strtolower($context . '_' . $quoted)) {
            case 'js_': // old method
                $s = str_replace(["\\", "\"", "'", "\r", "\n"], ["\\\\", "\\\"", "\\'", "\\r", "\\n"], $s);
                break;
            case 'js_"':
                $s = str_replace(["\\", "\"", "\r", "\n"], ["\\\\", "\\\"", "\\r", "\\n"], $s);
                break;
            case 'js_\'':
                $s = str_replace(["\\", "'", "\r", "\n"], ["\\\\", "\\'", "\\r", "\\n"], $s);
                break;

            case 'dquot_"':
                $s = str_replace(["\\", "\"", "\r", "\n"], ["\\\\", "\\\"", "\\r", "\\n"], $s);
                break;
            case 'squot_"':
                $s = str_replace(["\\", "'", "\r", "\n"], ["\\\\", "\\'", "\\r", "\\n"], $s);
                break;

            case 'html_': // old method
            case 'html_\'':
            case 'html_"':
                $s = str_replace(["&", "<", ">", "\n"], ["&amp;", "&lt;", "&gt;", "<br/>\n"], $s);
                break;

            case 'htmlprop_':
                $s = str_replace(["\"", "'", "<", ">"], ["&quot;", "&#39;", "&lt;", "&gt;"], $s);
                break;
            case 'htmlprop_\'':
                $s = str_replace(["'", "<", ">"], ["&#39;", "&lt;", "&gt;"], $s);
                break;
            case 'htmlprop_"':
                $s = str_replace(["\"", "<", ">"], ["&quot;", "&lt;", "&gt;"], $s);
                break;

            case 'form_':
            case 'form_\'':  // <input type... value='$phpvar'...>
            case 'form_"':
                $s = str_replace(["&", "\"", "'", "<", ">"], ["&amp;", "&quot;", "&#39;", "&lt;", "&gt;"], $s);
                break;

            case 'none_"':
            default:
                break;
        }

        return($s);
    }

    public static function hasAccent($string)
    {
        $ret = true;
        preg_match('/^[a-zA-Z0-9-_]*$/', $string, $matches);

        if (count($matches) == '1' && $matches[0] == $string) {
            $ret = false;
        }

        return $ret;
    }

    public static function jsonencode($datas)
    {
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            return json_encode($datas, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_APOS);
        } else {
            return json_encode($datas);
        }
    }

    public static function format_octets($octets, $precision = 2)
    {
        $octets = (float) $octets;
        if ($octets < 900) {
            return $octets . ' o';
        }
        $koctet = round($octets / 1024, $precision);
        if ($koctet < 900) {
            return $koctet . ' ko';
        }
        $Moctet = round($octets / (1024 * 1024), $precision);
        if ($Moctet < 900) {
            return $Moctet . ' Mo';
        }
        $Goctet = round($octets / (1024 * 1024 * 1024), $precision);
        if ($Goctet < 900) {
            return $Goctet . ' Go';
        }
        $Toctet = round($octets / (1024 * 1024 * 1024 * 1024), $precision);

        return $Toctet . ' To';
    }

    public static function format_seconds($seconds)
    {
        $durations = $durationm = $durationh = 0;
        $durations = fmod($seconds, 60);
        $durations = $durations <= 9 ? '0' . $durations : $durations;
        $durationm = fmod(floor($seconds / 60), 60);
        $durationm = ($durationm <= 9 ? '0' . $durationm : $durationm) . ':';
        $durationh = floor($seconds / 3600);
        $durationh = $durationh == 0 ? '' : (
            ($durationh <= 9 ? '0' . $durationh : $durationh) . ':');
        $d = $durationh . $durationm . $durations;

        if ($d == '00:00') {
            $d = '';
        }
        if ($seconds < 0) {
            $d = '';
        }
        if ($seconds === 0) {
            $d = '00:00';
        }

        return $d;
    }
}
