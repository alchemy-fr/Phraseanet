<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class unicode
{
    protected $endCharacters_utf8 = "\t\r\n !\"#\$%&'()+,-./:;<=>@[\]^_`{|}~£§¨°";

    public function get_indexer_bad_chars()
    {
        return $this->endCharacters_utf8;
    }

    public function has_indexer_bad_char($string)
    {
        return mb_strpos($this->endCharacters_utf8, $string);
    }

    public function remove_indexer_chars($string)
    {
        $so = "";
        $string = phrasea_utf8_convert_to($string, 'lcnd');            //

        $l = mb_strlen($string, "UTF-8");
        $lastwasblank = false;
        for ($i = 0; $i < $l; $i ++) {
            $c = mb_substr($string, $i, 1, "UTF-8");
            if (mb_strpos($this->endCharacters_utf8, $c) !== FALSE) {
                $lastwasblank = true;
            } else {
                if ($lastwasblank && $so != "")
                    $so .= " ";
                $so .= $c;
                $lastwasblank = false;
            }
        }

        return($so);
    }

    public function remove_diacritics($string)
    {
        return phrasea_utf8_convert_to($string, 'nd');
    }

    public function remove_nonazAZ09($string, $keep_underscores = true, $keep_minus = true, $keep_dot = false)
    {
        $regexp = '/[a-zA-Z0-9';
        if ($keep_minus === true) {
            $regexp .= '-';
        }
        if ($keep_underscores === true) {
            $regexp .= '_';
        }
        if ($keep_dot === true) {
            $regexp .= '\.';
        }

        $regexp .= ']{1}/';

        $string = $this->remove_diacritics($string);

        $out = '';

        $l = mb_strlen($string);
        for ($i = 0; $i < $l; $i ++) {
            $c = mb_substr($string, $i, 1);
            if (preg_match($regexp, $c))
                $out .= $c;
        }

        return $out;
    }

    /**
     * Removes all digits a the begining of a string
     * @Example : returns 'soleil' for '123soleil' and 'bb2' for '1bb2'
     *
     * @param  type $string
     * @return type
     */
    public function remove_first_digits($string)
    {
        while ($string != '' && ctype_digit($string[0])) {
            $string = substr($string, 1);
        }

        return $string;
    }

    /**
     * Guess the charset of a string and returns the UTF-8 version
     *
     * @param  string $string
     * @return string
     */
    public function toUTF8($string)
    {
        /**
         * (8x except 85, 8C) + (9x except 9C) + (BC, BD, BE)
         */
        static $macchars = "\x81\x82\x83\x84\x86\x87\x88\x89\x8A\x8B\x8D\x8E\x8F\x90\x91\x92\x93\x94\x95\x96\x97\x98\x99\x9A\x9B\x9D\x9E\x9F\xBC\xBD\xBE";

        if (mb_convert_encoding(mb_convert_encoding($string, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32') == $string) {
            $mac = mb_convert_encoding($string, 'windows-1252', 'UTF-8');
            for ($i = strlen($mac); $i;) {
                if (strpos($macchars, $mac[ -- $i]) !== false) {
                    return(iconv('MACINTOSH', 'UTF-8', $mac));
                }
            }

            return($string);
        } else {
            for ($i = strlen($string); $i;) {
                if (strpos($macchars, $string[ -- $i]) !== false) {
                    return(iconv('MACINTOSH', 'UTF-8', $string));
                }
            }

            return(iconv('windows-1252', 'UTF-8', $string));
        }
    }

    /**
     * Removes ctrl chars (tous < 32 sauf 9,10,13)
     *
     * @param string $string
     * @param string $substitution
     *
     * @return string
     */
    public function substituteCtrlCharacters($string, $substitution = '_')
    {
        static $chars_in = null;

        if (is_null($chars_in)) {

            $chars_in = array();

            for ($cc = 0; $cc < 32; $cc ++) {
                if (in_array($cc, array(9, 10, 13))) {
                    continue;
                }

                $chars_in[] = chr($cc);
            }
        }

        return str_replace($chars_in, $substitution, $string);
    }

    /**
     * Parse a string and try to return the date normalized
     *
     * @example usage :
     *
     *      //returns '2012/00/00 00:00:00'
     *      $unicode->parseDate('2012');
     *
     * @todo timezonify
     *
     * @param  string $date
     * @return string
     */
    public function parseDate($date)
    {
        $date = str_replace(array('-', ':', '/', '.'), ' ', $date);
        $date_yyyy = $date_mm = $date_dd = $date_hh = $date_ii = $date_ss = 0;

        switch (sscanf($date, '%d %d %d %d %d %d', $date_yyyy, $date_mm, $date_dd, $date_hh, $date_ii, $date_ss)) {
            case 1:
                $date = sprintf('%04d/00/00 00:00:00', $date_yyyy);
                break;
            case 2:
                $date = sprintf('%04d/%02d/00 00:00:00', $date_yyyy, $date_mm);
                break;
            case 3:
                $date = sprintf('%04d/%02d/%02d 00:00:00', $date_yyyy, $date_mm, $date_dd);
                break;
            case 4:
                $date = sprintf('%04d/%02d/%02d %02d:00:00', $date_yyyy, $date_mm, $date_dd, $date_hh);
                break;
            case 5:
                $date = sprintf('%04d/%02d/%02d %02d:%02d:00', $date_yyyy, $date_mm, $date_dd, $date_hh, $date_ii);
                break;
            case 6:
                $date = sprintf('%04d/%02d/%02d %02d:%02d:%02d', $date_yyyy, $date_mm, $date_dd, $date_hh, $date_ii, $date_ss);
                break;
            default:
                $date = '0000/00/00 00:00:00';
        }

        return $date;
    }
}
