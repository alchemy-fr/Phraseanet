<?php

/*
 * Phraseanet Date class, mostly inspired by :
 *
 * JavaScript Pretty Date
 * Copyright (c) 2008 John Resig (jquery.com)
 * Licensed under the MIT license.
 *
 * Ported to PHP >= 5.1 by Zach Leatherman (zachleat.com)
 * Slight modification denoted below to handle months and years.
 *
 *
 */

class phraseadate
{

    /**
     *
     * @param  DateTime $date
     * @return string
     */
    public static function getTime(DateTime $date)
    {
        $locale = Session_Handler::get_locale();

        switch ($locale) {
            default:
            case 'fr_FR':
            case 'de_DE':
                $time = $date->format('H:i');
                break;
            case 'en_GB':
                $time = $date->format('h:iA');
                break;
        }

        return $time;
    }

    /**
     *
     * @param  DateTime $date
     * @return string
     */
    public static function getDate(DateTime $date)
    {
        $compareTo = new DateTime('now');
        $diff = $compareTo->format('U') - $date->format('U');
        $dayDiff = floor($diff / 86400);

        if (is_nan($dayDiff)) {
            return '';
        }

        if ($dayDiff < 365) {
            return self::formatDate($date, Session_Handler::get_locale(), 'DAY_MONTH');
        } else {
            return self::formatDate($date, Session_Handler::get_locale(), 'DAY_MONTH_YEAR');
        }
    }

    /**
     *
     * @param  DateTime $date
     * @return string
     */
    public static function getPrettyString(DateTime $date = null)
    {
        if (is_null($date)) {
            return null;
        }

        $compareTo = new DateTime('now');
        $diff = $compareTo->format('U') - $date->format('U');
        $dayDiff = floor($diff / 86400);

        if (is_nan($dayDiff) || $dayDiff > 365000) {
            return '';
        }

        $date_string = self::formatDate($date, Session_Handler::get_locale(), 'DAY_MONTH');

        if ($dayDiff == 0) {
            if ($diff < 60) {
                return _('phraseanet::temps:: a l\'instant');
            } elseif ($diff < 120) {
                return _('phraseanet::temps:: il y a une minute');
            } elseif ($diff < 3600) {
                return sprintf(_('phraseanet::temps:: il y a %d minutes')
                        , floor($diff / 60));
            } elseif ($diff < 7200) {
                return _('phraseanet::temps:: il y a une heure');
            } elseif ($diff < 86400) {
                return sprintf(_('phraseanet::temps:: il y a %d heures')
                        , floor($diff / 3600));
            }
        } elseif ($dayDiff == 1) {
            return _('phraseanet::temps:: hier');
        } elseif ($dayDiff < 365 && $dayDiff > 0) {
            return $date_string;
        } else {
            return self::formatDate($date, Session_Handler::get_locale(), 'DAY_MONTH_YEAR');
        }
    }

    /**
     *
     * @param  DateTime $date
     * @return string
     */
    public static function format_mysql(DateTime $date)
    {
        return $date->format(DATE_ISO8601);
    }

    /**
     *
     * @param  DateTime $date
     * @param  string   $locale
     * @param  string   $format
     * @return string
     */
    private function formatDate(DateTime $date, $locale, $format)
    {

        switch ($locale) {
            default:
            case 'fr_FR':
                switch ($format) {
                    default:
                    case 'DAY_MONTH':
                        $date_formated = strftime("%e %B", $date->format('U'));
                        break;
                    case 'DAY_MONTH_YEAR':
                        $date_formated = strftime("%e %B %Y", $date->format('U'));
                        break;
                }
                break;
            case 'en_GB':
                switch ($format) {
                    default:
                    case 'DAY_MONTH':
                        $date_formated = strftime("%B %e", $date->format('U'));
                        break;
                    case 'DAY_MONTH_YEAR':
                        $date_formated = strftime("%B %e %Y", $date->format('U'));
                        break;
                }
                break;
            case 'de_DE':
                switch ($format) {
                    default:
                    case 'DAY_MONTH':
                        $date_formated = strftime("%e. %B", $date->format('U'));
                        break;
                    case 'DAY_MONTH_YEAR':
                        $date_formated = strftime("%e. %B %Y", $date->format('U'));
                        break;
                }
                break;
        }

        return $date_formated;
    }

    /**
     *
     * @param  string $isodelimdate
     * @param  string $format
     * @return string
     */
    public static function isodateToDate($isodelimdate, $format)
    {
        $tc = array();
        $bal = array();
        $isodelimdate = trim($isodelimdate);

        while ($isodelimdate != "") {
            if (($c = $isodelimdate[0]) == "<") {
                if (($p = strpos($isodelimdate, ">")) !== false) {
                    if ($isodelimdate[1] == "/") {
                        array_pop($bal);
                    } else {
                        if ($isodelimdate[$p - 1] != "/")
                            array_push($bal, substr($isodelimdate, 1, $p - 1));
                    }
                    $isodelimdate = substr($isodelimdate, $p + 1);
                } else {
                    $isodelimdate = "";
                }
            } else {
                $tc[] = array("char"        => $c, "bals"        => $bal);
                $isodelimdate = substr($isodelimdate, 1);
            }
        }

        $strdate = "";
        $paterns = array("YYYY" => 0, "YY"   => 2, "MM"   => 5,
            "DD"   => 8, "HH"   => 11, "NN"   => 14, "SS"   => 17);

        while ($format != "") {
            $patfound = false;
            foreach ($paterns as $pat => $idx) {
                if (substr($format, 0, ($l = strlen($pat))) == $pat) {
                    for ($i = 0; $i < $l; $i ++ ) {
                        $bal_out = "";
                        if (isset($tc[$idx + $i])) {
                            foreach ($tc[$idx + $i]["bals"] as $b) {
                                $strdate .= "<$b>";
                                $bal_out = "</$b>" . $bal_out;
                            }
                            $strdate .= $tc[$idx + $i]["char"] . $bal_out;
                        }
                    }
                    $format = substr($format, $l);
                    $patfound = true;
                    break;
                }
            }
            if ( ! $patfound) {
                $strdate .= $format[0];
                $format = substr($format, 1);
            }
        }

        return($strdate);
    }

    /**
     *
     * @param  string $strdate
     * @param  string $format
     * @return string
     */
    public static function dateToIsodate($strdate, $format)
    {
        $v_y = $v_m = $v_d = $v_h = $v_n = $v_s = 0;
        $v = str_replace(
            array("-", ":", "/", "."), array(" ", " ", " ", " "), trim($strdate)
        );
        $n = 0;

        $format = str_replace(
            array("-", ":", "/", "."), array(" ", " ", " ", " "), $format
        );
        $isodelimdate = null;
        switch ($format) {
            case "MM YYYY":
            case "MM YYYY HH NN SS":
                $n = sscanf($v, "%d %d %d %d %d", $v_m, $v_y, $v_h, $v_n, $v_s);
                break;
            case "MMYYYY":
            case "MMYYYYHHNNSS":
                $n = sscanf($v, "%d%d%d%d%d", $v_m, $v_y, $v_h, $v_n, $v_s);
                break;
            case "DD MM YYYY":
            case "DD MM YYYY HH NN SS":
                $n = sscanf($v, "%d %d %d %d %d %d", $v_d, $v_m, $v_y, $v_h, $v_n, $v_s);
                break;
            case "DDMMYYYY":
            case "DDMMYYYYHHNNSS":
                $n = sscanf($v, "%02d%02d%04d%02d%02d%02d", $v_d, $v_m, $v_y, $v_h, $v_n, $v_s);
                break;
            case "DD MM YY":
            case "DD MM YY HH NN SS":
                $n = sscanf($v, "%d %d %d %d %d %d", $v_d, $v_m, $v_y, $v_h, $v_n, $v_s);
                if ($v_y < 20)
                    $v_y += 2000;
                else
                if ($v_y < 100)
                    $v += 1900;
                break;
            case "DDMMYY":
            case "DDMMYYHHNNSS":
                $n = sscanf($v, "%02d%02d%02d%02d%02d%02d", $v_d, $v_m, $v_y, $v_h, $v_n, $v_s);
                if ($v_y < 20)
                    $v_y += 2000;
                else
                    $v += 1900;
                break;
            case "MM DD YYYY":
            case "MM DD YYYY HH NN SS":
                $n = sscanf($v, "%d %d %d %d %d %d", $v_m, $v_d, $v_y, $v_h, $v_n, $v_s);
                break;
            case "MMDDYYYY":
            case "MMDDYYYYHHNNSS":
                $n = sscanf($v, "%02d%02d%04d%02d%02d%02d", $v_m, $v_d, $v_y, $v_h, $v_n, $v_s);
                break;
            case "MM DD YY":
            case "MM DD YY HH NN SS":
                $n = sscanf($v, "%d %d %d %d %d %d", $v_m, $v_d, $v_y, $v_h, $v_n, $v_s);
                if ($v_y < 20)
                    $v_y += 2000;
                else
                if ($v_y < 100)
                    $v += 1900;
                break;
            case "MMDDYY":
            case "MMDDYYHHNNSS":
                $n = sscanf($v, "%02d%02d%02d%02d%02d%02d", $v_m, $v_d, $v_y, $v_h, $v_n, $v_s);
                if ($v_y < 20)
                    $v_y += 2000;
                else
                    $v += 1900;
                break;
            case "YYYY MM DD":
            case "YYYY MM DD HH NN SS":
                $n = sscanf($v, "%d %d %d %d %d %d", $v_y, $v_m, $v_d, $v_h, $v_n, $v_s);
                break;
            case "YYYYMMDD":
            case "YYYYMMDDHHNNSS":
                $n = sscanf($v, "%04d%02d%02d%02d%02d%02d", $v_y, $v_m, $v_d, $v_h, $v_n, $v_s);
                break;
            case "YY MM DD":
            case "YY MM DD HH NN SS":
                $n = sscanf($v, "%d %d %d %d %d %d", $v_y, $v_m, $v_d, $v_h, $v_n, $v_s);
                if ($v_y < 20)
                    $v_y += 2000;
                else
                if ($v_y < 100)
                    $v += 1900;
                break;
            case "YYMMDD":
            case "YYMMDDHHNNSS":
                $n = sscanf($v, "%02d%02d%02d%02d%02d%02d", $v_y, $v_m, $v_d, $v_h, $v_n, $v_s);
                if ($v_y < 20)
                    $v_y += 2000;
                else
                    $v += 1900;
                break;
            case "YYYY DD MM":
            case "YYYY DD MM HH NN SS":
                $n = sscanf($v, "%d %d %d %d %d %d", $v_y, $v_d, $v_m, $v_h, $v_n, $v_s);
                break;
            case "YYYYDDMM":
            case "YYYYDDMMHHNNSS":
                $n = sscanf($v, "%04d%02d%02d%02d%02d%02d", $v_y, $v_d, $v_m, $v_h, $v_n, $v_s);
                break;
            case "YY DD MM":
            case "YY DD MM HH NN SS":
                $n = sscanf($v, "%d %d %d %d %d %d", $v_y, $v_d, $v_m, $v_h, $v_n, $v_s);
                if ($v_y < 20)
                    $v_y += 2000;
                else
                if ($v_y < 100)
                    $v += 1900;
                break;
            case "YYDDMM":
            case "YYDDMMHHNNSS":
                $n = sscanf($v, "%02d%02d%02d%02d%02d%02d", $v_y, $v_d, $v_m, $v_h, $v_n, $v_s);
                if ($v_y < 20)
                    $v_y += 2000;
                else
                    $v += 1900;
                break;
            default:
                $n = 0;
                // printf("format de date inconnu : %s\n", $format);
                break;
        }
        if ($n > 0) {
            if ($v_y >= 0 && $v_y <= 9999 && $v_m >= 0 && $v_m <= 99
                && $v_d >= 0 && $v_d <= 99 && $v_h >= 0 && $v_h <= 99
                && $v_n >= 0 && $v_n <= 99 && $v_s >= 0 && $v_s <= 99) {
                $isodelimdate = sprintf("%04d/%02d/%02d %02d:%02d:%02d", $v_y, $v_m, $v_d, $v_h, $v_n, $v_s);
            } else {

            }
        } else {

        }

        return($isodelimdate);
    }
}
