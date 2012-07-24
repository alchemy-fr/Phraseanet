<?php

class p4string
{

    public static function addFirstSlash($path)
    {
        if ($path == "") {
            return("./");
        }

        $c = substr($path, 0, 1);

        if ($c != "/" && $c != "\\") {
            return "/" . $path;
        }

        return($path);
    }

    public static function delFirstSlash($path)
    {
        if ($path == "/" || $path == "\\") {
            return("");
        }

        $c = substr($path, 0, 1);

        if ($c == "/" || $c == "\\") {
            return substr($path, 1, strlen($path));
        }

        $c = substr($path, 0, 2);

        if ($c == "\\") {
            return substr($path, 2, strlen($path) - 1);
        }

        if ($path == "") {
            return "./";
        }

        return($path);
    }

    public static function addEndSlash($path)
    {
        if ($path == "") {
            $path = getcwd();
        }

        $lastCharacter = substr($path, -1, 1);

        if ( ! in_array($lastCharacter, array('\\', '/'))) {
            $path .= DIRECTORY_SEPARATOR;
        }

        return $path;
    }

    public static function delEndSlash($path)
    {
        if ($path == "/" || $path == "\\") {
            return("");
        }

        $c = substr($path, -1, 1);

        if ($c == "/" || $c == "\\") {
            $path = substr($path, 0, strlen($path) - 1);
        }

        if ($path == "") {
            $path = ".";
        }

        return($path);
    }

    public static function cleanTags($string)
    {
        return strip_tags($string); //, '<p><a><b><i><div><ul><ol><li><br>');
    }

    /**
     * @deprecated
     *
     * @param  type $s
     * @return type
     */
    public static function JSstring($s)
    {
        return(str_replace(array("\\", "\"", "\r", "\n"), array("\\\\", "\\\"", "\\r", "\\n"), $s));
    }

    /**
     * @deprecated
     *
     * @param  type $s
     * @param  type $context
     * @param  type $quoted
     * @return type
     */
    public static function MakeString($s, $context = 'html', $quoted = '')
    {
        switch (mb_strtolower($context . '_' . $quoted)) {
            case 'js_': // old method
                $s = str_replace(array("\\", "\"", "'", "\r", "\n"), array("\\\\", "\\\"", "\\'", "\\r", "\\n"), $s);
                break;
            case 'js_"':
                $s = str_replace(array("\\", "\"", "\r", "\n"), array("\\\\", "\\\"", "\\r", "\\n"), $s);
                break;
            case 'js_\'':
                $s = str_replace(array("\\", "'", "\r", "\n"), array("\\\\", "\\'", "\\r", "\\n"), $s);
                break;

            case 'dquot_"':
                $s = str_replace(array("\\", "\"", "\r", "\n"), array("\\\\", "\\\"", "\\r", "\\n"), $s);
                break;
            case 'squot_"':
                $s = str_replace(array("\\", "'", "\r", "\n"), array("\\\\", "\\'", "\\r", "\\n"), $s);
                break;

            case 'html_': // old method
            case 'html_\'':
            case 'html_"':
                $s = str_replace(array("&", "<", ">", "\n"), array("&amp;", "&lt;", "&gt;", "<br/>\n"), $s);
                break;

            case 'htmlprop_':
                $s = str_replace(array("\"", "'", "<", ">"), array("&quot;", "&#39;", "&lt;", "&gt;"), $s);
                break;
            case 'htmlprop_\'':
                $s = str_replace(array("'", "<", ">"), array("&#39;", "&lt;", "&gt;"), $s);
                break;
            case 'htmlprop_"':
                $s = str_replace(array("\"", "<", ">"), array("&quot;", "&lt;", "&gt;"), $s);
                break;

            case 'form_':
            case 'form_\'':  // <input type... value='$phpvar'...>
            case 'form_"':
                $s = str_replace(array("&", "\"", "'", "<", ">"), array("&amp;", "&quot;", "&#39;", "&lt;", "&gt;"), $s);
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
