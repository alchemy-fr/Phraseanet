<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class unicode
{
    const CONVERT_TO_LC   = 'lc';           // lowercase
    const CONVERT_TO_ND   = 'nd';           // no-diacritics
    const CONVERT_TO_LCND = 'lcnd';         // lowercase no-diacritics

    private $maps = [];

    private function &getMap($mapId)
    {
        if(array_key_exists($mapId, $this->maps)) {
            return $this->maps[$mapId];
        }
        switch($mapId) {
            case self::CONVERT_TO_LC:
                $this->setMap_LC();
                return $this->maps[self::CONVERT_TO_LC];
            case self::CONVERT_TO_ND:
                $this->setMap_ND();
                return $this->maps[self::CONVERT_TO_ND];
            case self::CONVERT_TO_LCND:
                $this->setMap_LCND();
                return $this->maps[self::CONVERT_TO_LCND];
        }
        return [];
    }

    private function setMap_LC()
    {
        $this->maps[self::CONVERT_TO_LC] = [
            "\x41"     => "\x61"        ,  /* U+0041: LATIN CAPITAL LETTER A                                   -> U+0061: LATIN SMALL LETTER A                                   */
            "\x42"     => "\x62"        ,  /* U+0042: LATIN CAPITAL LETTER B                                   -> U+0062: LATIN SMALL LETTER B                                   */
            "\x43"     => "\x63"        ,  /* U+0043: LATIN CAPITAL LETTER C                                   -> U+0063: LATIN SMALL LETTER C                                   */
            "\x44"     => "\x64"        ,  /* U+0044: LATIN CAPITAL LETTER D                                   -> U+0064: LATIN SMALL LETTER D                                   */
            "\x45"     => "\x65"        ,  /* U+0045: LATIN CAPITAL LETTER E                                   -> U+0065: LATIN SMALL LETTER E                                   */
            "\x46"     => "\x66"        ,  /* U+0046: LATIN CAPITAL LETTER F                                   -> U+0066: LATIN SMALL LETTER F                                   */
            "\x47"     => "\x67"        ,  /* U+0047: LATIN CAPITAL LETTER G                                   -> U+0067: LATIN SMALL LETTER G                                   */
            "\x48"     => "\x68"        ,  /* U+0048: LATIN CAPITAL LETTER H                                   -> U+0068: LATIN SMALL LETTER H                                   */
            "\x49"     => "\x69"        ,  /* U+0049: LATIN CAPITAL LETTER I                                   -> U+0069: LATIN SMALL LETTER I                                   */
            "\x4A"     => "\x6A"        ,  /* U+004A: LATIN CAPITAL LETTER J                                   -> U+006A: LATIN SMALL LETTER J                                   */
            "\x4B"     => "\x6B"        ,  /* U+004B: LATIN CAPITAL LETTER K                                   -> U+006B: LATIN SMALL LETTER K                                   */
            "\x4C"     => "\x6C"        ,  /* U+004C: LATIN CAPITAL LETTER L                                   -> U+006C: LATIN SMALL LETTER L                                   */
            "\x4D"     => "\x6D"        ,  /* U+004D: LATIN CAPITAL LETTER M                                   -> U+006D: LATIN SMALL LETTER M                                   */
            "\x4E"     => "\x6E"        ,  /* U+004E: LATIN CAPITAL LETTER N                                   -> U+006E: LATIN SMALL LETTER N                                   */
            "\x4F"     => "\x6F"        ,  /* U+004F: LATIN CAPITAL LETTER O                                   -> U+006F: LATIN SMALL LETTER O                                   */
            "\x50"     => "\x70"        ,  /* U+0050: LATIN CAPITAL LETTER P                                   -> U+0070: LATIN SMALL LETTER P                                   */
            "\x51"     => "\x71"        ,  /* U+0051: LATIN CAPITAL LETTER Q                                   -> U+0071: LATIN SMALL LETTER Q                                   */
            "\x52"     => "\x72"        ,  /* U+0052: LATIN CAPITAL LETTER R                                   -> U+0072: LATIN SMALL LETTER R                                   */
            "\x53"     => "\x73"        ,  /* U+0053: LATIN CAPITAL LETTER S                                   -> U+0073: LATIN SMALL LETTER S                                   */
            "\x54"     => "\x74"        ,  /* U+0054: LATIN CAPITAL LETTER T                                   -> U+0074: LATIN SMALL LETTER T                                   */
            "\x55"     => "\x75"        ,  /* U+0055: LATIN CAPITAL LETTER U                                   -> U+0075: LATIN SMALL LETTER U                                   */
            "\x56"     => "\x76"        ,  /* U+0056: LATIN CAPITAL LETTER V                                   -> U+0076: LATIN SMALL LETTER V                                   */
            "\x57"     => "\x77"        ,  /* U+0057: LATIN CAPITAL LETTER W                                   -> U+0077: LATIN SMALL LETTER W                                   */
            "\x58"     => "\x78"        ,  /* U+0058: LATIN CAPITAL LETTER X                                   -> U+0078: LATIN SMALL LETTER X                                   */
            "\x59"     => "\x79"        ,  /* U+0059: LATIN CAPITAL LETTER Y                                   -> U+0079: LATIN SMALL LETTER Y                                   */
            "\x5A"     => "\x7A"        ,  /* U+005A: LATIN CAPITAL LETTER Z                                   -> U+007A: LATIN SMALL LETTER Z                                   */
            "\xC3\x80" => "\xC3\xA0"    ,  /* U+00C0: LATIN CAPITAL LETTER A WITH GRAVE                        -> U+00E0: LATIN SMALL LETTER A WITH GRAVE                        */
            "\xC3\x81" => "\xC3\xA1"    ,  /* U+00C1: LATIN CAPITAL LETTER A WITH ACUTE                        -> U+00E1: LATIN SMALL LETTER A WITH ACUTE                        */
            "\xC3\x82" => "\xC3\xA2"    ,  /* U+00C2: LATIN CAPITAL LETTER A WITH CIRCUMFLEX                   -> U+00E2: LATIN SMALL LETTER A WITH CIRCUMFLEX                   */
            "\xC3\x83" => "\xC3\xA3"    ,  /* U+00C3: LATIN CAPITAL LETTER A WITH TILDE                        -> U+00E3: LATIN SMALL LETTER A WITH TILDE                        */
            "\xC3\x84" => "\xC3\xA4"    ,  /* U+00C4: LATIN CAPITAL LETTER A WITH DIAERESIS                    -> U+00E4: LATIN SMALL LETTER A WITH DIAERESIS                    */
            "\xC3\x85" => "\xC3\xA5"    ,  /* U+00C5: LATIN CAPITAL LETTER A WITH RING ABOVE                   -> U+00E5: LATIN SMALL LETTER A WITH RING ABOVE                   */
            "\xC3\x86" => "\xC3\xA6"    ,  /* U+00C6: LATIN CAPITAL LETTER AE                                  -> U+00E6: LATIN SMALL LETTER AE                                  */
            "\xC3\x87" => "\xC3\xA7"    ,  /* U+00C7: LATIN CAPITAL LETTER C WITH CEDILLA                      -> U+00E7: LATIN SMALL LETTER C WITH CEDILLA                      */
            "\xC3\x88" => "\xC3\xA8"    ,  /* U+00C8: LATIN CAPITAL LETTER E WITH GRAVE                        -> U+00E8: LATIN SMALL LETTER E WITH GRAVE                        */
            "\xC3\x89" => "\xC3\xA9"    ,  /* U+00C9: LATIN CAPITAL LETTER E WITH ACUTE                        -> U+00E9: LATIN SMALL LETTER E WITH ACUTE                        */
            "\xC3\x8A" => "\xC3\xAA"    ,  /* U+00CA: LATIN CAPITAL LETTER E WITH CIRCUMFLEX                   -> U+00EA: LATIN SMALL LETTER E WITH CIRCUMFLEX                   */
            "\xC3\x8B" => "\xC3\xAB"    ,  /* U+00CB: LATIN CAPITAL LETTER E WITH DIAERESIS                    -> U+00EB: LATIN SMALL LETTER E WITH DIAERESIS                    */
            "\xC3\x8C" => "\xC3\xAC"    ,  /* U+00CC: LATIN CAPITAL LETTER I WITH GRAVE                        -> U+00EC: LATIN SMALL LETTER I WITH GRAVE                        */
            "\xC3\x8D" => "\xC3\xAD"    ,  /* U+00CD: LATIN CAPITAL LETTER I WITH ACUTE                        -> U+00ED: LATIN SMALL LETTER I WITH ACUTE                        */
            "\xC3\x8E" => "\xC3\xAE"    ,  /* U+00CE: LATIN CAPITAL LETTER I WITH CIRCUMFLEX                   -> U+00EE: LATIN SMALL LETTER I WITH CIRCUMFLEX                   */
            "\xC3\x8F" => "\xC3\xAF"    ,  /* U+00CF: LATIN CAPITAL LETTER I WITH DIAERESIS                    -> U+00EF: LATIN SMALL LETTER I WITH DIAERESIS                    */
            "\xC3\x90" => "\xC3\xB0"    ,  /* U+00D0: LATIN CAPITAL LETTER ETH                                 -> U+00F0: LATIN SMALL LETTER ETH                                 */
            "\xC3\x91" => "\xC3\xB1"    ,  /* U+00D1: LATIN CAPITAL LETTER N WITH TILDE                        -> U+00F1: LATIN SMALL LETTER N WITH TILDE                        */
            "\xC3\x92" => "\xC3\xB2"    ,  /* U+00D2: LATIN CAPITAL LETTER O WITH GRAVE                        -> U+00F2: LATIN SMALL LETTER O WITH GRAVE                        */
            "\xC3\x93" => "\xC3\xB3"    ,  /* U+00D3: LATIN CAPITAL LETTER O WITH ACUTE                        -> U+00F3: LATIN SMALL LETTER O WITH ACUTE                        */
            "\xC3\x94" => "\xC3\xB4"    ,  /* U+00D4: LATIN CAPITAL LETTER O WITH CIRCUMFLEX                   -> U+00F4: LATIN SMALL LETTER O WITH CIRCUMFLEX                   */
            "\xC3\x95" => "\xC3\xB5"    ,  /* U+00D5: LATIN CAPITAL LETTER O WITH TILDE                        -> U+00F5: LATIN SMALL LETTER O WITH TILDE                        */
            "\xC3\x96" => "\xC3\xB6"    ,  /* U+00D6: LATIN CAPITAL LETTER O WITH DIAERESIS                    -> U+00F6: LATIN SMALL LETTER O WITH DIAERESIS                    */
            "\xC3\x98" => "\xC3\xB8"    ,  /* U+00D8: LATIN CAPITAL LETTER O WITH STROKE                       -> U+00F8: LATIN SMALL LETTER O WITH STROKE                       */
            "\xC3\x99" => "\xC3\xB9"    ,  /* U+00D9: LATIN CAPITAL LETTER U WITH GRAVE                        -> U+00F9: LATIN SMALL LETTER U WITH GRAVE                        */
            "\xC3\x9A" => "\xC3\xBA"    ,  /* U+00DA: LATIN CAPITAL LETTER U WITH ACUTE                        -> U+00FA: LATIN SMALL LETTER U WITH ACUTE                        */
            "\xC3\x9B" => "\xC3\xBB"    ,  /* U+00DB: LATIN CAPITAL LETTER U WITH CIRCUMFLEX                   -> U+00FB: LATIN SMALL LETTER U WITH CIRCUMFLEX                   */
            "\xC3\x9C" => "\xC3\xBC"    ,  /* U+00DC: LATIN CAPITAL LETTER U WITH DIAERESIS                    -> U+00FC: LATIN SMALL LETTER U WITH DIAERESIS                    */
            "\xC3\x9D" => "\xC3\xBD"    ,  /* U+00DD: LATIN CAPITAL LETTER Y WITH ACUTE                        -> U+00FD: LATIN SMALL LETTER Y WITH ACUTE                        */
            "\xC3\x9E" => "\xC3\xBE"    ,  /* U+00DE: LATIN CAPITAL LETTER THORN                               -> U+00FE: LATIN SMALL LETTER THORN                               */
            "\xC4\x80" => "\xC4\x81"    ,  /* U+0100: LATIN CAPITAL LETTER A WITH MACRON                       -> U+0101: LATIN SMALL LETTER A WITH MACRON                       */
            "\xC4\x82" => "\xC4\x83"    ,  /* U+0102: LATIN CAPITAL LETTER A WITH BREVE                        -> U+0103: LATIN SMALL LETTER A WITH BREVE                        */
            "\xC4\x84" => "\xC4\x85"    ,  /* U+0104: LATIN CAPITAL LETTER A WITH OGONEK                       -> U+0105: LATIN SMALL LETTER A WITH OGONEK                       */
            "\xC4\x86" => "\xC4\x87"    ,  /* U+0106: LATIN CAPITAL LETTER C WITH ACUTE                        -> U+0107: LATIN SMALL LETTER C WITH ACUTE                        */
            "\xC4\x88" => "\xC4\x89"    ,  /* U+0108: LATIN CAPITAL LETTER C WITH CIRCUMFLEX                   -> U+0109: LATIN SMALL LETTER C WITH CIRCUMFLEX                   */
            "\xC4\x8A" => "\xC4\x8B"    ,  /* U+010A: LATIN CAPITAL LETTER C WITH DOT ABOVE                    -> U+010B: LATIN SMALL LETTER C WITH DOT ABOVE                    */
            "\xC4\x8C" => "\xC4\x8D"    ,  /* U+010C: LATIN CAPITAL LETTER C WITH CARON                        -> U+010D: LATIN SMALL LETTER C WITH CARON                        */
            "\xC4\x8E" => "\xC4\x8F"    ,  /* U+010E: LATIN CAPITAL LETTER D WITH CARON                        -> U+010F: LATIN SMALL LETTER D WITH CARON                        */
            "\xC4\x90" => "\xC4\x91"    ,  /* U+0110: LATIN CAPITAL LETTER D WITH STROKE                       -> U+0111: LATIN SMALL LETTER D WITH STROKE                       */
            "\xC4\x92" => "\xC4\x93"    ,  /* U+0112: LATIN CAPITAL LETTER E WITH MACRON                       -> U+0113: LATIN SMALL LETTER E WITH MACRON                       */
            "\xC4\x94" => "\xC4\x95"    ,  /* U+0114: LATIN CAPITAL LETTER E WITH BREVE                        -> U+0115: LATIN SMALL LETTER E WITH BREVE                        */
            "\xC4\x96" => "\xC4\x97"    ,  /* U+0116: LATIN CAPITAL LETTER E WITH DOT ABOVE                    -> U+0117: LATIN SMALL LETTER E WITH DOT ABOVE                    */
            "\xC4\x98" => "\xC4\x99"    ,  /* U+0118: LATIN CAPITAL LETTER E WITH OGONEK                       -> U+0119: LATIN SMALL LETTER E WITH OGONEK                       */
            "\xC4\x9A" => "\xC4\x9B"    ,  /* U+011A: LATIN CAPITAL LETTER E WITH CARON                        -> U+011B: LATIN SMALL LETTER E WITH CARON                        */
            "\xC4\x9C" => "\xC4\x9D"    ,  /* U+011C: LATIN CAPITAL LETTER G WITH CIRCUMFLEX                   -> U+011D: LATIN SMALL LETTER G WITH CIRCUMFLEX                   */
            "\xC4\x9E" => "\xC4\x9F"    ,  /* U+011E: LATIN CAPITAL LETTER G WITH BREVE                        -> U+011F: LATIN SMALL LETTER G WITH BREVE                        */
            "\xC4\xA0" => "\xC4\xA1"    ,  /* U+0120: LATIN CAPITAL LETTER G WITH DOT ABOVE                    -> U+0121: LATIN SMALL LETTER G WITH DOT ABOVE                    */
            "\xC4\xA2" => "\xC4\xA3"    ,  /* U+0122: LATIN CAPITAL LETTER G WITH CEDILLA                      -> U+0123: LATIN SMALL LETTER G WITH CEDILLA                      */
            "\xC4\xA4" => "\xC4\xA5"    ,  /* U+0124: LATIN CAPITAL LETTER H WITH CIRCUMFLEX                   -> U+0125: LATIN SMALL LETTER H WITH CIRCUMFLEX                   */
            "\xC4\xA6" => "\xC4\xA7"    ,  /* U+0126: LATIN CAPITAL LETTER H WITH STROKE                       -> U+0127: LATIN SMALL LETTER H WITH STROKE                       */
            "\xC4\xA8" => "\xC4\xA9"    ,  /* U+0128: LATIN CAPITAL LETTER I WITH TILDE                        -> U+0129: LATIN SMALL LETTER I WITH TILDE                        */
            "\xC4\xAA" => "\xC4\xAB"    ,  /* U+012A: LATIN CAPITAL LETTER I WITH MACRON                       -> U+012B: LATIN SMALL LETTER I WITH MACRON                       */
            "\xC4\xAC" => "\xC4\xAD"    ,  /* U+012C: LATIN CAPITAL LETTER I WITH BREVE                        -> U+012D: LATIN SMALL LETTER I WITH BREVE                        */
            "\xC4\xAE" => "\xC4\xAF"    ,  /* U+012E: LATIN CAPITAL LETTER I WITH OGONEK                       -> U+012F: LATIN SMALL LETTER I WITH OGONEK                       */
            "\xC4\xB0" => "\x69"        ,  /* U+0130: LATIN CAPITAL LETTER I WITH DOT ABOVE                    -> U+0069: LATIN SMALL LETTER I                                   */
            "\xC4\xB2" => "\xC4\xB3"    ,  /* U+0132: LATIN CAPITAL LIGATURE IJ                                -> U+0133: LATIN SMALL LIGATURE IJ                                */
            "\xC4\xB4" => "\xC4\xB5"    ,  /* U+0134: LATIN CAPITAL LETTER J WITH CIRCUMFLEX                   -> U+0135: LATIN SMALL LETTER J WITH CIRCUMFLEX                   */
            "\xC4\xB6" => "\xC4\xB7"    ,  /* U+0136: LATIN CAPITAL LETTER K WITH CEDILLA                      -> U+0137: LATIN SMALL LETTER K WITH CEDILLA                      */
            "\xC4\xB9" => "\xC4\xBA"    ,  /* U+0139: LATIN CAPITAL LETTER L WITH ACUTE                        -> U+013A: LATIN SMALL LETTER L WITH ACUTE                        */
            "\xC4\xBB" => "\xC4\xBC"    ,  /* U+013B: LATIN CAPITAL LETTER L WITH CEDILLA                      -> U+013C: LATIN SMALL LETTER L WITH CEDILLA                      */
            "\xC4\xBD" => "\xC4\xBE"    ,  /* U+013D: LATIN CAPITAL LETTER L WITH CARON                        -> U+013E: LATIN SMALL LETTER L WITH CARON                        */
            "\xC4\xBF" => "\xC5\x80"    ,  /* U+013F: LATIN CAPITAL LETTER L WITH MIDDLE DOT                   -> U+0140: LATIN SMALL LETTER L WITH MIDDLE DOT                   */
            "\xC5\x81" => "\xC5\x82"    ,  /* U+0141: LATIN CAPITAL LETTER L WITH STROKE                       -> U+0142: LATIN SMALL LETTER L WITH STROKE                       */
            "\xC5\x83" => "\xC5\x84"    ,  /* U+0143: LATIN CAPITAL LETTER N WITH ACUTE                        -> U+0144: LATIN SMALL LETTER N WITH ACUTE                        */
            "\xC5\x85" => "\xC5\x86"    ,  /* U+0145: LATIN CAPITAL LETTER N WITH CEDILLA                      -> U+0146: LATIN SMALL LETTER N WITH CEDILLA                      */
            "\xC5\x87" => "\xC5\x88"    ,  /* U+0147: LATIN CAPITAL LETTER N WITH CARON                        -> U+0148: LATIN SMALL LETTER N WITH CARON                        */
            "\xC5\x8A" => "\xC5\x8B"    ,  /* U+014A: LATIN CAPITAL LETTER ENG                                 -> U+014B: LATIN SMALL LETTER ENG                                 */
            "\xC5\x8C" => "\xC5\x8D"    ,  /* U+014C: LATIN CAPITAL LETTER O WITH MACRON                       -> U+014D: LATIN SMALL LETTER O WITH MACRON                       */
            "\xC5\x8E" => "\xC5\x8F"    ,  /* U+014E: LATIN CAPITAL LETTER O WITH BREVE                        -> U+014F: LATIN SMALL LETTER O WITH BREVE                        */
            "\xC5\x90" => "\xC5\x91"    ,  /* U+0150: LATIN CAPITAL LETTER O WITH DOUBLE ACUTE                 -> U+0151: LATIN SMALL LETTER O WITH DOUBLE ACUTE                 */
            "\xC5\x92" => "\xC5\x93"    ,  /* U+0152: LATIN CAPITAL LIGATURE OE                                -> U+0153: LATIN SMALL LIGATURE OE                                */
            "\xC5\x94" => "\xC5\x95"    ,  /* U+0154: LATIN CAPITAL LETTER R WITH ACUTE                        -> U+0155: LATIN SMALL LETTER R WITH ACUTE                        */
            "\xC5\x96" => "\xC5\x97"    ,  /* U+0156: LATIN CAPITAL LETTER R WITH CEDILLA                      -> U+0157: LATIN SMALL LETTER R WITH CEDILLA                      */
            "\xC5\x98" => "\xC5\x99"    ,  /* U+0158: LATIN CAPITAL LETTER R WITH CARON                        -> U+0159: LATIN SMALL LETTER R WITH CARON                        */
            "\xC5\x9A" => "\xC5\x9B"    ,  /* U+015A: LATIN CAPITAL LETTER S WITH ACUTE                        -> U+015B: LATIN SMALL LETTER S WITH ACUTE                        */
            "\xC5\x9C" => "\xC5\x9D"    ,  /* U+015C: LATIN CAPITAL LETTER S WITH CIRCUMFLEX                   -> U+015D: LATIN SMALL LETTER S WITH CIRCUMFLEX                   */
            "\xC5\x9E" => "\xC5\x9F"    ,  /* U+015E: LATIN CAPITAL LETTER S WITH CEDILLA                      -> U+015F: LATIN SMALL LETTER S WITH CEDILLA                      */
            "\xC5\xA0" => "\xC5\xA1"    ,  /* U+0160: LATIN CAPITAL LETTER S WITH CARON                        -> U+0161: LATIN SMALL LETTER S WITH CARON                        */
            "\xC5\xA2" => "\xC5\xA3"    ,  /* U+0162: LATIN CAPITAL LETTER T WITH CEDILLA                      -> U+0163: LATIN SMALL LETTER T WITH CEDILLA                      */
            "\xC5\xA4" => "\xC5\xA5"    ,  /* U+0164: LATIN CAPITAL LETTER T WITH CARON                        -> U+0165: LATIN SMALL LETTER T WITH CARON                        */
            "\xC5\xA6" => "\xC5\xA7"    ,  /* U+0166: LATIN CAPITAL LETTER T WITH STROKE                       -> U+0167: LATIN SMALL LETTER T WITH STROKE                       */
            "\xC5\xA8" => "\xC5\xA9"    ,  /* U+0168: LATIN CAPITAL LETTER U WITH TILDE                        -> U+0169: LATIN SMALL LETTER U WITH TILDE                        */
            "\xC5\xAA" => "\xC5\xAB"    ,  /* U+016A: LATIN CAPITAL LETTER U WITH MACRON                       -> U+016B: LATIN SMALL LETTER U WITH MACRON                       */
            "\xC5\xAC" => "\xC5\xAD"    ,  /* U+016C: LATIN CAPITAL LETTER U WITH BREVE                        -> U+016D: LATIN SMALL LETTER U WITH BREVE                        */
            "\xC5\xAE" => "\xC5\xAF"    ,  /* U+016E: LATIN CAPITAL LETTER U WITH RING ABOVE                   -> U+016F: LATIN SMALL LETTER U WITH RING ABOVE                   */
            "\xC5\xB0" => "\xC5\xB1"    ,  /* U+0170: LATIN CAPITAL LETTER U WITH DOUBLE ACUTE                 -> U+0171: LATIN SMALL LETTER U WITH DOUBLE ACUTE                 */
            "\xC5\xB2" => "\xC5\xB3"    ,  /* U+0172: LATIN CAPITAL LETTER U WITH OGONEK                       -> U+0173: LATIN SMALL LETTER U WITH OGONEK                       */
            "\xC5\xB4" => "\xC5\xB5"    ,  /* U+0174: LATIN CAPITAL LETTER W WITH CIRCUMFLEX                   -> U+0175: LATIN SMALL LETTER W WITH CIRCUMFLEX                   */
            "\xC5\xB6" => "\xC5\xB7"    ,  /* U+0176: LATIN CAPITAL LETTER Y WITH CIRCUMFLEX                   -> U+0177: LATIN SMALL LETTER Y WITH CIRCUMFLEX                   */
            "\xC5\xB8" => "\xC3\xBF"    ,  /* U+0178: LATIN CAPITAL LETTER Y WITH DIAERESIS                    -> U+00FF: LATIN SMALL LETTER Y WITH DIAERESIS                    */
            "\xC5\xB9" => "\xC5\xBA"    ,  /* U+0179: LATIN CAPITAL LETTER Z WITH ACUTE                        -> U+017A: LATIN SMALL LETTER Z WITH ACUTE                        */
            "\xC5\xBB" => "\xC5\xBC"    ,  /* U+017B: LATIN CAPITAL LETTER Z WITH DOT ABOVE                    -> U+017C: LATIN SMALL LETTER Z WITH DOT ABOVE                    */
            "\xC5\xBD" => "\xC5\xBE"    ,  /* U+017D: LATIN CAPITAL LETTER Z WITH CARON                        -> U+017E: LATIN SMALL LETTER Z WITH CARON                        */
            "\xC6\x81" => "\xC9\x93"    ,  /* U+0181: LATIN CAPITAL LETTER B WITH HOOK                         -> U+0253: LATIN SMALL LETTER B WITH HOOK                         */
            "\xC6\x82" => "\xC6\x83"    ,  /* U+0182: LATIN CAPITAL LETTER B WITH TOPBAR                       -> U+0183: LATIN SMALL LETTER B WITH TOPBAR                       */
            "\xC6\x84" => "\xC6\x85"    ,  /* U+0184: LATIN CAPITAL LETTER TONE SIX                            -> U+0185: LATIN SMALL LETTER TONE SIX                            */
            "\xC6\x86" => "\xC9\x94"    ,  /* U+0186: LATIN CAPITAL LETTER OPEN O                              -> U+0254: LATIN SMALL LETTER OPEN O                              */
            "\xC6\x87" => "\xC6\x88"    ,  /* U+0187: LATIN CAPITAL LETTER C WITH HOOK                         -> U+0188: LATIN SMALL LETTER C WITH HOOK                         */
            "\xC6\x89" => "\xC9\x96"    ,  /* U+0189: LATIN CAPITAL LETTER AFRICAN D                           -> U+0256: LATIN SMALL LETTER D WITH TAIL                         */
            "\xC6\x8A" => "\xC9\x97"    ,  /* U+018A: LATIN CAPITAL LETTER D WITH HOOK                         -> U+0257: LATIN SMALL LETTER D WITH HOOK                         */
            "\xC6\x8B" => "\xC6\x8C"    ,  /* U+018B: LATIN CAPITAL LETTER D WITH TOPBAR                       -> U+018C: LATIN SMALL LETTER D WITH TOPBAR                       */
            "\xC6\x8E" => "\xC7\x9D"    ,  /* U+018E: LATIN CAPITAL LETTER REVERSED E                          -> U+01DD: LATIN SMALL LETTER TURNED E                            */
            "\xC6\x8F" => "\xC9\x99"    ,  /* U+018F: LATIN CAPITAL LETTER SCHWA                               -> U+0259: LATIN SMALL LETTER SCHWA                               */
            "\xC6\x90" => "\xC9\x9B"    ,  /* U+0190: LATIN CAPITAL LETTER OPEN E                              -> U+025B: LATIN SMALL LETTER OPEN E                              */
            "\xC6\x91" => "\xC6\x92"    ,  /* U+0191: LATIN CAPITAL LETTER F WITH HOOK                         -> U+0192: LATIN SMALL LETTER F WITH HOOK                         */
            "\xC6\x93" => "\xC9\xA0"    ,  /* U+0193: LATIN CAPITAL LETTER G WITH HOOK                         -> U+0260: LATIN SMALL LETTER G WITH HOOK                         */
            "\xC6\x94" => "\xC9\xA3"    ,  /* U+0194: LATIN CAPITAL LETTER GAMMA                               -> U+0263: LATIN SMALL LETTER GAMMA                               */
            "\xC6\x96" => "\xC9\xA9"    ,  /* U+0196: LATIN CAPITAL LETTER IOTA                                -> U+0269: LATIN SMALL LETTER IOTA                                */
            "\xC6\x97" => "\xC9\xA8"    ,  /* U+0197: LATIN CAPITAL LETTER I WITH STROKE                       -> U+0268: LATIN SMALL LETTER I WITH STROKE                       */
            "\xC6\x98" => "\xC6\x99"    ,  /* U+0198: LATIN CAPITAL LETTER K WITH HOOK                         -> U+0199: LATIN SMALL LETTER K WITH HOOK                         */
            "\xC6\x9C" => "\xC9\xAF"    ,  /* U+019C: LATIN CAPITAL LETTER TURNED M                            -> U+026F: LATIN SMALL LETTER TURNED M                            */
            "\xC6\x9D" => "\xC9\xB2"    ,  /* U+019D: LATIN CAPITAL LETTER N WITH LEFT HOOK                    -> U+0272: LATIN SMALL LETTER N WITH LEFT HOOK                    */
            "\xC6\x9F" => "\xC9\xB5"    ,  /* U+019F: LATIN CAPITAL LETTER O WITH MIDDLE TILDE                 -> U+0275: LATIN SMALL LETTER BARRED O                            */
            "\xC6\xA0" => "\xC6\xA1"    ,  /* U+01A0: LATIN CAPITAL LETTER O WITH HORN                         -> U+01A1: LATIN SMALL LETTER O WITH HORN                         */
            "\xC6\xA2" => "\xC6\xA3"    ,  /* U+01A2: LATIN CAPITAL LETTER OI                                  -> U+01A3: LATIN SMALL LETTER OI                                  */
            "\xC6\xA4" => "\xC6\xA5"    ,  /* U+01A4: LATIN CAPITAL LETTER P WITH HOOK                         -> U+01A5: LATIN SMALL LETTER P WITH HOOK                         */
            "\xC6\xA6" => "\xCA\x80"    ,  /* U+01A6: LATIN LETTER YR                                          -> U+0280: LATIN LETTER SMALL CAPITAL R                           */
            "\xC6\xA7" => "\xC6\xA8"    ,  /* U+01A7: LATIN CAPITAL LETTER TONE TWO                            -> U+01A8: LATIN SMALL LETTER TONE TWO                            */
            "\xC6\xA9" => "\xCA\x83"    ,  /* U+01A9: LATIN CAPITAL LETTER ESH                                 -> U+0283: LATIN SMALL LETTER ESH                                 */
            "\xC6\xAC" => "\xC6\xAD"    ,  /* U+01AC: LATIN CAPITAL LETTER T WITH HOOK                         -> U+01AD: LATIN SMALL LETTER T WITH HOOK                         */
            "\xC6\xAE" => "\xCA\x88"    ,  /* U+01AE: LATIN CAPITAL LETTER T WITH RETROFLEX HOOK               -> U+0288: LATIN SMALL LETTER T WITH RETROFLEX HOOK               */
            "\xC6\xAF" => "\xC6\xB0"    ,  /* U+01AF: LATIN CAPITAL LETTER U WITH HORN                         -> U+01B0: LATIN SMALL LETTER U WITH HORN                         */
            "\xC6\xB1" => "\xCA\x8A"    ,  /* U+01B1: LATIN CAPITAL LETTER UPSILON                             -> U+028A: LATIN SMALL LETTER UPSILON                             */
            "\xC6\xB2" => "\xCA\x8B"    ,  /* U+01B2: LATIN CAPITAL LETTER V WITH HOOK                         -> U+028B: LATIN SMALL LETTER V WITH HOOK                         */
            "\xC6\xB3" => "\xC6\xB4"    ,  /* U+01B3: LATIN CAPITAL LETTER Y WITH HOOK                         -> U+01B4: LATIN SMALL LETTER Y WITH HOOK                         */
            "\xC6\xB5" => "\xC6\xB6"    ,  /* U+01B5: LATIN CAPITAL LETTER Z WITH STROKE                       -> U+01B6: LATIN SMALL LETTER Z WITH STROKE                       */
            "\xC6\xB7" => "\xCA\x92"    ,  /* U+01B7: LATIN CAPITAL LETTER EZH                                 -> U+0292: LATIN SMALL LETTER EZH                                 */
            "\xC6\xB8" => "\xC6\xB9"    ,  /* U+01B8: LATIN CAPITAL LETTER EZH REVERSED                        -> U+01B9: LATIN SMALL LETTER EZH REVERSED                        */
            "\xC6\xBC" => "\xC6\xBD"    ,  /* U+01BC: LATIN CAPITAL LETTER TONE FIVE                           -> U+01BD: LATIN SMALL LETTER TONE FIVE                           */
            "\xC7\x84" => "\xC7\x86"    ,  /* U+01C4: LATIN CAPITAL LETTER DZ WITH CARON                       -> U+01C6: LATIN SMALL LETTER DZ WITH CARON                       */
            "\xC7\x85" => "\xC7\x86"    ,  /* U+01C5: LATIN CAPITAL LETTER D WITH SMALL LETTER Z WITH CARON    -> U+01C6: LATIN SMALL LETTER DZ WITH CARON                       */
            "\xC7\x87" => "\xC7\x89"    ,  /* U+01C7: LATIN CAPITAL LETTER LJ                                  -> U+01C9: LATIN SMALL LETTER LJ                                  */
            "\xC7\x88" => "\xC7\x89"    ,  /* U+01C8: LATIN CAPITAL LETTER L WITH SMALL LETTER J               -> U+01C9: LATIN SMALL LETTER LJ                                  */
            "\xC7\x8A" => "\xC7\x8C"    ,  /* U+01CA: LATIN CAPITAL LETTER NJ                                  -> U+01CC: LATIN SMALL LETTER NJ                                  */
            "\xC7\x8B" => "\xC7\x8C"    ,  /* U+01CB: LATIN CAPITAL LETTER N WITH SMALL LETTER J               -> U+01CC: LATIN SMALL LETTER NJ                                  */
            "\xC7\x8D" => "\xC7\x8E"    ,  /* U+01CD: LATIN CAPITAL LETTER A WITH CARON                        -> U+01CE: LATIN SMALL LETTER A WITH CARON                        */
            "\xC7\x8F" => "\xC7\x90"    ,  /* U+01CF: LATIN CAPITAL LETTER I WITH CARON                        -> U+01D0: LATIN SMALL LETTER I WITH CARON                        */
            "\xC7\x91" => "\xC7\x92"    ,  /* U+01D1: LATIN CAPITAL LETTER O WITH CARON                        -> U+01D2: LATIN SMALL LETTER O WITH CARON                        */
            "\xC7\x93" => "\xC7\x94"    ,  /* U+01D3: LATIN CAPITAL LETTER U WITH CARON                        -> U+01D4: LATIN SMALL LETTER U WITH CARON                        */
            "\xC7\x95" => "\xC7\x96"    ,  /* U+01D5: LATIN CAPITAL LETTER U WITH DIAERESIS AND MACRON         -> U+01D6: LATIN SMALL LETTER U WITH DIAERESIS AND MACRON         */
            "\xC7\x97" => "\xC7\x98"    ,  /* U+01D7: LATIN CAPITAL LETTER U WITH DIAERESIS AND ACUTE          -> U+01D8: LATIN SMALL LETTER U WITH DIAERESIS AND ACUTE          */
            "\xC7\x99" => "\xC7\x9A"    ,  /* U+01D9: LATIN CAPITAL LETTER U WITH DIAERESIS AND CARON          -> U+01DA: LATIN SMALL LETTER U WITH DIAERESIS AND CARON          */
            "\xC7\x9B" => "\xC7\x9C"    ,  /* U+01DB: LATIN CAPITAL LETTER U WITH DIAERESIS AND GRAVE          -> U+01DC: LATIN SMALL LETTER U WITH DIAERESIS AND GRAVE          */
            "\xC7\x9E" => "\xC7\x9F"    ,  /* U+01DE: LATIN CAPITAL LETTER A WITH DIAERESIS AND MACRON         -> U+01DF: LATIN SMALL LETTER A WITH DIAERESIS AND MACRON         */
            "\xC7\xA0" => "\xC7\xA1"    ,  /* U+01E0: LATIN CAPITAL LETTER A WITH DOT ABOVE AND MACRON         -> U+01E1: LATIN SMALL LETTER A WITH DOT ABOVE AND MACRON         */
            "\xC7\xA2" => "\xC7\xA3"    ,  /* U+01E2: LATIN CAPITAL LETTER AE WITH MACRON                      -> U+01E3: LATIN SMALL LETTER AE WITH MACRON                      */
            "\xC7\xA4" => "\xC7\xA5"    ,  /* U+01E4: LATIN CAPITAL LETTER G WITH STROKE                       -> U+01E5: LATIN SMALL LETTER G WITH STROKE                       */
            "\xC7\xA6" => "\xC7\xA7"    ,  /* U+01E6: LATIN CAPITAL LETTER G WITH CARON                        -> U+01E7: LATIN SMALL LETTER G WITH CARON                        */
            "\xC7\xA8" => "\xC7\xA9"    ,  /* U+01E8: LATIN CAPITAL LETTER K WITH CARON                        -> U+01E9: LATIN SMALL LETTER K WITH CARON                        */
            "\xC7\xAA" => "\xC7\xAB"    ,  /* U+01EA: LATIN CAPITAL LETTER O WITH OGONEK                       -> U+01EB: LATIN SMALL LETTER O WITH OGONEK                       */
            "\xC7\xAC" => "\xC7\xAD"    ,  /* U+01EC: LATIN CAPITAL LETTER O WITH OGONEK AND MACRON            -> U+01ED: LATIN SMALL LETTER O WITH OGONEK AND MACRON            */
            "\xC7\xAE" => "\xC7\xAF"    ,  /* U+01EE: LATIN CAPITAL LETTER EZH WITH CARON                      -> U+01EF: LATIN SMALL LETTER EZH WITH CARON                      */
            "\xC7\xB1" => "\xC7\xB3"    ,  /* U+01F1: LATIN CAPITAL LETTER DZ                                  -> U+01F3: LATIN SMALL LETTER DZ                                  */
            "\xC7\xB2" => "\xC7\xB3"    ,  /* U+01F2: LATIN CAPITAL LETTER D WITH SMALL LETTER Z               -> U+01F3: LATIN SMALL LETTER DZ                                  */
            "\xC7\xB4" => "\xC7\xB5"    ,  /* U+01F4: LATIN CAPITAL LETTER G WITH ACUTE                        -> U+01F5: LATIN SMALL LETTER G WITH ACUTE                        */
            "\xC7\xB6" => "\xC6\x95"    ,  /* U+01F6: LATIN CAPITAL LETTER HWAIR                               -> U+0195: LATIN SMALL LETTER HV                                  */
            "\xC7\xB7" => "\xC6\xBF"    ,  /* U+01F7: LATIN CAPITAL LETTER WYNN                                -> U+01BF: LATIN LETTER WYNN                                      */
            "\xC7\xB8" => "\xC7\xB9"    ,  /* U+01F8: LATIN CAPITAL LETTER N WITH GRAVE                        -> U+01F9: LATIN SMALL LETTER N WITH GRAVE                        */
            "\xC7\xBA" => "\xC7\xBB"    ,  /* U+01FA: LATIN CAPITAL LETTER A WITH RING ABOVE AND ACUTE         -> U+01FB: LATIN SMALL LETTER A WITH RING ABOVE AND ACUTE         */
            "\xC7\xBC" => "\xC7\xBD"    ,  /* U+01FC: LATIN CAPITAL LETTER AE WITH ACUTE                       -> U+01FD: LATIN SMALL LETTER AE WITH ACUTE                       */
            "\xC7\xBE" => "\xC7\xBF"    ,  /* U+01FE: LATIN CAPITAL LETTER O WITH STROKE AND ACUTE             -> U+01FF: LATIN SMALL LETTER O WITH STROKE AND ACUTE             */
            "\xC8\x80" => "\xC8\x81"    ,  /* U+0200: LATIN CAPITAL LETTER A WITH DOUBLE GRAVE                 -> U+0201: LATIN SMALL LETTER A WITH DOUBLE GRAVE                 */
            "\xC8\x82" => "\xC8\x83"    ,  /* U+0202: LATIN CAPITAL LETTER A WITH INVERTED BREVE               -> U+0203: LATIN SMALL LETTER A WITH INVERTED BREVE               */
            "\xC8\x84" => "\xC8\x85"    ,  /* U+0204: LATIN CAPITAL LETTER E WITH DOUBLE GRAVE                 -> U+0205: LATIN SMALL LETTER E WITH DOUBLE GRAVE                 */
            "\xC8\x86" => "\xC8\x87"    ,  /* U+0206: LATIN CAPITAL LETTER E WITH INVERTED BREVE               -> U+0207: LATIN SMALL LETTER E WITH INVERTED BREVE               */
            "\xC8\x88" => "\xC8\x89"    ,  /* U+0208: LATIN CAPITAL LETTER I WITH DOUBLE GRAVE                 -> U+0209: LATIN SMALL LETTER I WITH DOUBLE GRAVE                 */
            "\xC8\x8A" => "\xC8\x8B"    ,  /* U+020A: LATIN CAPITAL LETTER I WITH INVERTED BREVE               -> U+020B: LATIN SMALL LETTER I WITH INVERTED BREVE               */
            "\xC8\x8C" => "\xC8\x8D"    ,  /* U+020C: LATIN CAPITAL LETTER O WITH DOUBLE GRAVE                 -> U+020D: LATIN SMALL LETTER O WITH DOUBLE GRAVE                 */
            "\xC8\x8E" => "\xC8\x8F"    ,  /* U+020E: LATIN CAPITAL LETTER O WITH INVERTED BREVE               -> U+020F: LATIN SMALL LETTER O WITH INVERTED BREVE               */
            "\xC8\x90" => "\xC8\x91"    ,  /* U+0210: LATIN CAPITAL LETTER R WITH DOUBLE GRAVE                 -> U+0211: LATIN SMALL LETTER R WITH DOUBLE GRAVE                 */
            "\xC8\x92" => "\xC8\x93"    ,  /* U+0212: LATIN CAPITAL LETTER R WITH INVERTED BREVE               -> U+0213: LATIN SMALL LETTER R WITH INVERTED BREVE               */
            "\xC8\x94" => "\xC8\x95"    ,  /* U+0214: LATIN CAPITAL LETTER U WITH DOUBLE GRAVE                 -> U+0215: LATIN SMALL LETTER U WITH DOUBLE GRAVE                 */
            "\xC8\x96" => "\xC8\x97"    ,  /* U+0216: LATIN CAPITAL LETTER U WITH INVERTED BREVE               -> U+0217: LATIN SMALL LETTER U WITH INVERTED BREVE               */
            "\xC8\x98" => "\xC8\x99"    ,  /* U+0218: LATIN CAPITAL LETTER S WITH COMMA BELOW                  -> U+0219: LATIN SMALL LETTER S WITH COMMA BELOW                  */
            "\xC8\x9A" => "\xC8\x9B"    ,  /* U+021A: LATIN CAPITAL LETTER T WITH COMMA BELOW                  -> U+021B: LATIN SMALL LETTER T WITH COMMA BELOW                  */
            "\xC8\x9C" => "\xC8\x9D"    ,  /* U+021C: LATIN CAPITAL LETTER YOGH                                -> U+021D: LATIN SMALL LETTER YOGH                                */
            "\xC8\x9E" => "\xC8\x9F"    ,  /* U+021E: LATIN CAPITAL LETTER H WITH CARON                        -> U+021F: LATIN SMALL LETTER H WITH CARON                        */
            "\xC8\xA0" => "\xC6\x9E"    ,  /* U+0220: LATIN CAPITAL LETTER N WITH LONG RIGHT LEG               -> U+019E: LATIN SMALL LETTER N WITH LONG RIGHT LEG               */
            "\xC8\xA2" => "\xC8\xA3"    ,  /* U+0222: LATIN CAPITAL LETTER OU                                  -> U+0223: LATIN SMALL LETTER OU                                  */
            "\xC8\xA4" => "\xC8\xA5"    ,  /* U+0224: LATIN CAPITAL LETTER Z WITH HOOK                         -> U+0225: LATIN SMALL LETTER Z WITH HOOK                         */
            "\xC8\xA6" => "\xC8\xA7"    ,  /* U+0226: LATIN CAPITAL LETTER A WITH DOT ABOVE                    -> U+0227: LATIN SMALL LETTER A WITH DOT ABOVE                    */
            "\xC8\xA8" => "\xC8\xA9"    ,  /* U+0228: LATIN CAPITAL LETTER E WITH CEDILLA                      -> U+0229: LATIN SMALL LETTER E WITH CEDILLA                      */
            "\xC8\xAA" => "\xC8\xAB"    ,  /* U+022A: LATIN CAPITAL LETTER O WITH DIAERESIS AND MACRON         -> U+022B: LATIN SMALL LETTER O WITH DIAERESIS AND MACRON         */
            "\xC8\xAC" => "\xC8\xAD"    ,  /* U+022C: LATIN CAPITAL LETTER O WITH TILDE AND MACRON             -> U+022D: LATIN SMALL LETTER O WITH TILDE AND MACRON             */
            "\xC8\xAE" => "\xC8\xAF"    ,  /* U+022E: LATIN CAPITAL LETTER O WITH DOT ABOVE                    -> U+022F: LATIN SMALL LETTER O WITH DOT ABOVE                    */
            "\xC8\xB0" => "\xC8\xB1"    ,  /* U+0230: LATIN CAPITAL LETTER O WITH DOT ABOVE AND MACRON         -> U+0231: LATIN SMALL LETTER O WITH DOT ABOVE AND MACRON         */
            "\xC8\xB2" => "\xC8\xB3"    ,  /* U+0232: LATIN CAPITAL LETTER Y WITH MACRON                       -> U+0233: LATIN SMALL LETTER Y WITH MACRON                       */
            "\xC8\xBA" => "\xE2\xB1\xA5",  /* U+023A: LATIN CAPITAL LETTER A WITH STROKE                       -> U+2C65: LATIN SMALL LETTER A WITH STROKE                       */
            "\xC8\xBB" => "\xC8\xBC"    ,  /* U+023B: LATIN CAPITAL LETTER C WITH STROKE                       -> U+023C: LATIN SMALL LETTER C WITH STROKE                       */
            "\xC8\xBD" => "\xC6\x9A"    ,  /* U+023D: LATIN CAPITAL LETTER L WITH BAR                          -> U+019A: LATIN SMALL LETTER L WITH BAR                          */
            "\xC8\xBE" => "\xE2\xB1\xA6",  /* U+023E: LATIN CAPITAL LETTER T WITH DIAGONAL STROKE              -> U+2C66: LATIN SMALL LETTER T WITH DIAGONAL STROKE              */
            "\xC9\x81" => "\xC9\x82"    ,  /* U+0241: LATIN CAPITAL LETTER GLOTTAL STOP                        -> U+0242: LATIN SMALL LETTER GLOTTAL STOP                        */
            "\xC9\x83" => "\xC6\x80"    ,  /* U+0243: LATIN CAPITAL LETTER B WITH STROKE                       -> U+0180: LATIN SMALL LETTER B WITH STROKE                       */
            "\xC9\x84" => "\xCA\x89"    ,  /* U+0244: LATIN CAPITAL LETTER U BAR                               -> U+0289: LATIN SMALL LETTER U BAR                               */
            "\xC9\x85" => "\xCA\x8C"    ,  /* U+0245: LATIN CAPITAL LETTER TURNED V                            -> U+028C: LATIN SMALL LETTER TURNED V                            */
            "\xC9\x86" => "\xC9\x87"    ,  /* U+0246: LATIN CAPITAL LETTER E WITH STROKE                       -> U+0247: LATIN SMALL LETTER E WITH STROKE                       */
            "\xC9\x88" => "\xC9\x89"    ,  /* U+0248: LATIN CAPITAL LETTER J WITH STROKE                       -> U+0249: LATIN SMALL LETTER J WITH STROKE                       */
            "\xC9\x8A" => "\xC9\x8B"    ,  /* U+024A: LATIN CAPITAL LETTER SMALL Q WITH HOOK TAIL              -> U+024B: LATIN SMALL LETTER Q WITH HOOK TAIL                    */
            "\xC9\x8C" => "\xC9\x8D"    ,  /* U+024C: LATIN CAPITAL LETTER R WITH STROKE                       -> U+024D: LATIN SMALL LETTER R WITH STROKE                       */
            "\xC9\x8E" => "\xC9\x8F"    ,  /* U+024E: LATIN CAPITAL LETTER Y WITH STROKE                       -> U+024F: LATIN SMALL LETTER Y WITH STROKE                       */
            "\xCD\xB0" => "\xCD\xB1"    ,  /* U+0370: GREEK CAPITAL LETTER HETA                                -> U+0371: GREEK SMALL LETTER HETA                                */
            "\xCD\xB2" => "\xCD\xB3"    ,  /* U+0372: GREEK CAPITAL LETTER ARCHAIC SAMPI                       -> U+0373: GREEK SMALL LETTER ARCHAIC SAMPI                       */
            "\xCD\xB6" => "\xCD\xB7"    ,  /* U+0376: GREEK CAPITAL LETTER PAMPHYLIAN DIGAMMA                  -> U+0377: GREEK SMALL LETTER PAMPHYLIAN DIGAMMA                  */
            "\xCE\x86" => "\xCE\xAC"    ,  /* U+0386: GREEK CAPITAL LETTER ALPHA WITH TONOS                    -> U+03AC: GREEK SMALL LETTER ALPHA WITH TONOS                    */
            "\xCE\x88" => "\xCE\xAD"    ,  /* U+0388: GREEK CAPITAL LETTER EPSILON WITH TONOS                  -> U+03AD: GREEK SMALL LETTER EPSILON WITH TONOS                  */
            "\xCE\x89" => "\xCE\xAE"    ,  /* U+0389: GREEK CAPITAL LETTER ETA WITH TONOS                      -> U+03AE: GREEK SMALL LETTER ETA WITH TONOS                      */
            "\xCE\x8A" => "\xCE\xAF"    ,  /* U+038A: GREEK CAPITAL LETTER IOTA WITH TONOS                     -> U+03AF: GREEK SMALL LETTER IOTA WITH TONOS                     */
            "\xCE\x8C" => "\xCF\x8C"    ,  /* U+038C: GREEK CAPITAL LETTER OMICRON WITH TONOS                  -> U+03CC: GREEK SMALL LETTER OMICRON WITH TONOS                  */
            "\xCE\x8E" => "\xCF\x8D"    ,  /* U+038E: GREEK CAPITAL LETTER UPSILON WITH TONOS                  -> U+03CD: GREEK SMALL LETTER UPSILON WITH TONOS                  */
            "\xCE\x8F" => "\xCF\x8E"    ,  /* U+038F: GREEK CAPITAL LETTER OMEGA WITH TONOS                    -> U+03CE: GREEK SMALL LETTER OMEGA WITH TONOS                    */
            "\xCE\x91" => "\xCE\xB1"    ,  /* U+0391: GREEK CAPITAL LETTER ALPHA                               -> U+03B1: GREEK SMALL LETTER ALPHA                               */
            "\xCE\x92" => "\xCE\xB2"    ,  /* U+0392: GREEK CAPITAL LETTER BETA                                -> U+03B2: GREEK SMALL LETTER BETA                                */
            "\xCE\x93" => "\xCE\xB3"    ,  /* U+0393: GREEK CAPITAL LETTER GAMMA                               -> U+03B3: GREEK SMALL LETTER GAMMA                               */
            "\xCE\x94" => "\xCE\xB4"    ,  /* U+0394: GREEK CAPITAL LETTER DELTA                               -> U+03B4: GREEK SMALL LETTER DELTA                               */
            "\xCE\x95" => "\xCE\xB5"    ,  /* U+0395: GREEK CAPITAL LETTER EPSILON                             -> U+03B5: GREEK SMALL LETTER EPSILON                             */
            "\xCE\x96" => "\xCE\xB6"    ,  /* U+0396: GREEK CAPITAL LETTER ZETA                                -> U+03B6: GREEK SMALL LETTER ZETA                                */
            "\xCE\x97" => "\xCE\xB7"    ,  /* U+0397: GREEK CAPITAL LETTER ETA                                 -> U+03B7: GREEK SMALL LETTER ETA                                 */
            "\xCE\x98" => "\xCE\xB8"    ,  /* U+0398: GREEK CAPITAL LETTER THETA                               -> U+03B8: GREEK SMALL LETTER THETA                               */
            "\xCE\x99" => "\xCE\xB9"    ,  /* U+0399: GREEK CAPITAL LETTER IOTA                                -> U+03B9: GREEK SMALL LETTER IOTA                                */
            "\xCE\x9A" => "\xCE\xBA"    ,  /* U+039A: GREEK CAPITAL LETTER KAPPA                               -> U+03BA: GREEK SMALL LETTER KAPPA                               */
            "\xCE\x9B" => "\xCE\xBB"    ,  /* U+039B: GREEK CAPITAL LETTER LAMDA                               -> U+03BB: GREEK SMALL LETTER LAMDA                               */
            "\xCE\x9C" => "\xCE\xBC"    ,  /* U+039C: GREEK CAPITAL LETTER MU                                  -> U+03BC: GREEK SMALL LETTER MU                                  */
            "\xCE\x9D" => "\xCE\xBD"    ,  /* U+039D: GREEK CAPITAL LETTER NU                                  -> U+03BD: GREEK SMALL LETTER NU                                  */
            "\xCE\x9E" => "\xCE\xBE"    ,  /* U+039E: GREEK CAPITAL LETTER XI                                  -> U+03BE: GREEK SMALL LETTER XI                                  */
            "\xCE\x9F" => "\xCE\xBF"    ,  /* U+039F: GREEK CAPITAL LETTER OMICRON                             -> U+03BF: GREEK SMALL LETTER OMICRON                             */
            "\xCE\xA0" => "\xCF\x80"    ,  /* U+03A0: GREEK CAPITAL LETTER PI                                  -> U+03C0: GREEK SMALL LETTER PI                                  */
            "\xCE\xA1" => "\xCF\x81"    ,  /* U+03A1: GREEK CAPITAL LETTER RHO                                 -> U+03C1: GREEK SMALL LETTER RHO                                 */
            "\xCE\xA3" => "\xCF\x83"    ,  /* U+03A3: GREEK CAPITAL LETTER SIGMA                               -> U+03C3: GREEK SMALL LETTER SIGMA                               */
            "\xCE\xA4" => "\xCF\x84"    ,  /* U+03A4: GREEK CAPITAL LETTER TAU                                 -> U+03C4: GREEK SMALL LETTER TAU                                 */
            "\xCE\xA5" => "\xCF\x85"    ,  /* U+03A5: GREEK CAPITAL LETTER UPSILON                             -> U+03C5: GREEK SMALL LETTER UPSILON                             */
            "\xCE\xA6" => "\xCF\x86"    ,  /* U+03A6: GREEK CAPITAL LETTER PHI                                 -> U+03C6: GREEK SMALL LETTER PHI                                 */
            "\xCE\xA7" => "\xCF\x87"    ,  /* U+03A7: GREEK CAPITAL LETTER CHI                                 -> U+03C7: GREEK SMALL LETTER CHI                                 */
            "\xCE\xA8" => "\xCF\x88"    ,  /* U+03A8: GREEK CAPITAL LETTER PSI                                 -> U+03C8: GREEK SMALL LETTER PSI                                 */
            "\xCE\xA9" => "\xCF\x89"    ,  /* U+03A9: GREEK CAPITAL LETTER OMEGA                               -> U+03C9: GREEK SMALL LETTER OMEGA                               */
            "\xCE\xAA" => "\xCF\x8A"    ,  /* U+03AA: GREEK CAPITAL LETTER IOTA WITH DIALYTIKA                 -> U+03CA: GREEK SMALL LETTER IOTA WITH DIALYTIKA                 */
            "\xCE\xAB" => "\xCF\x8B"    ,  /* U+03AB: GREEK CAPITAL LETTER UPSILON WITH DIALYTIKA              -> U+03CB: GREEK SMALL LETTER UPSILON WITH DIALYTIKA              */
            "\xCF\x8F" => "\xCF\x97"    ,  /* U+03CF: GREEK CAPITAL KAI SYMBOL                                 -> U+03D7: GREEK KAI SYMBOL                                       */
            "\xCF\x98" => "\xCF\x99"    ,  /* U+03D8: GREEK LETTER ARCHAIC KOPPA                               -> U+03D9: GREEK SMALL LETTER ARCHAIC KOPPA                       */
            "\xCF\x9A" => "\xCF\x9B"    ,  /* U+03DA: GREEK LETTER STIGMA                                      -> U+03DB: GREEK SMALL LETTER STIGMA                              */
            "\xCF\x9C" => "\xCF\x9D"    ,  /* U+03DC: GREEK LETTER DIGAMMA                                     -> U+03DD: GREEK SMALL LETTER DIGAMMA                             */
            "\xCF\x9E" => "\xCF\x9F"    ,  /* U+03DE: GREEK LETTER KOPPA                                       -> U+03DF: GREEK SMALL LETTER KOPPA                               */
            "\xCF\xA0" => "\xCF\xA1"    ,  /* U+03E0: GREEK LETTER SAMPI                                       -> U+03E1: GREEK SMALL LETTER SAMPI                               */
            "\xCF\xA2" => "\xCF\xA3"    ,  /* U+03E2: COPTIC CAPITAL LETTER SHEI                               -> U+03E3: COPTIC SMALL LETTER SHEI                               */
            "\xCF\xA4" => "\xCF\xA5"    ,  /* U+03E4: COPTIC CAPITAL LETTER FEI                                -> U+03E5: COPTIC SMALL LETTER FEI                                */
            "\xCF\xA6" => "\xCF\xA7"    ,  /* U+03E6: COPTIC CAPITAL LETTER KHEI                               -> U+03E7: COPTIC SMALL LETTER KHEI                               */
            "\xCF\xA8" => "\xCF\xA9"    ,  /* U+03E8: COPTIC CAPITAL LETTER HORI                               -> U+03E9: COPTIC SMALL LETTER HORI                               */
            "\xCF\xAA" => "\xCF\xAB"    ,  /* U+03EA: COPTIC CAPITAL LETTER GANGIA                             -> U+03EB: COPTIC SMALL LETTER GANGIA                             */
            "\xCF\xAC" => "\xCF\xAD"    ,  /* U+03EC: COPTIC CAPITAL LETTER SHIMA                              -> U+03ED: COPTIC SMALL LETTER SHIMA                              */
            "\xCF\xAE" => "\xCF\xAF"    ,  /* U+03EE: COPTIC CAPITAL LETTER DEI                                -> U+03EF: COPTIC SMALL LETTER DEI                                */
            "\xCF\xB4" => "\xCE\xB8"    ,  /* U+03F4: GREEK CAPITAL THETA SYMBOL                               -> U+03B8: GREEK SMALL LETTER THETA                               */
            "\xCF\xB7" => "\xCF\xB8"    ,  /* U+03F7: GREEK CAPITAL LETTER SHO                                 -> U+03F8: GREEK SMALL LETTER SHO                                 */
            "\xCF\xB9" => "\xCF\xB2"    ,  /* U+03F9: GREEK CAPITAL LUNATE SIGMA SYMBOL                        -> U+03F2: GREEK LUNATE SIGMA SYMBOL                              */
            "\xCF\xBA" => "\xCF\xBB"    ,  /* U+03FA: GREEK CAPITAL LETTER SAN                                 -> U+03FB: GREEK SMALL LETTER SAN                                 */
            "\xCF\xBD" => "\xCD\xBB"    ,  /* U+03FD: GREEK CAPITAL REVERSED LUNATE SIGMA SYMBOL               -> U+037B: GREEK SMALL REVERSED LUNATE SIGMA SYMBOL               */
            "\xCF\xBE" => "\xCD\xBC"    ,  /* U+03FE: GREEK CAPITAL DOTTED LUNATE SIGMA SYMBOL                 -> U+037C: GREEK SMALL DOTTED LUNATE SIGMA SYMBOL                 */
            "\xCF\xBF" => "\xCD\xBD"    ,  /* U+03FF: GREEK CAPITAL REVERSED DOTTED LUNATE SIGMA SYMBOL        -> U+037D: GREEK SMALL REVERSED DOTTED LUNATE SIGMA SYMBOL        */
            "\xD0\x80" => "\xD1\x90"    ,  /* U+0400: CYRILLIC CAPITAL LETTER IE WITH GRAVE                    -> U+0450: CYRILLIC SMALL LETTER IE WITH GRAVE                    */
            "\xD0\x81" => "\xD1\x91"    ,  /* U+0401: CYRILLIC CAPITAL LETTER IO                               -> U+0451: CYRILLIC SMALL LETTER IO                               */
            "\xD0\x82" => "\xD1\x92"    ,  /* U+0402: CYRILLIC CAPITAL LETTER DJE                              -> U+0452: CYRILLIC SMALL LETTER DJE                              */
            "\xD0\x83" => "\xD1\x93"    ,  /* U+0403: CYRILLIC CAPITAL LETTER GJE                              -> U+0453: CYRILLIC SMALL LETTER GJE                              */
            "\xD0\x84" => "\xD1\x94"    ,  /* U+0404: CYRILLIC CAPITAL LETTER UKRAINIAN IE                     -> U+0454: CYRILLIC SMALL LETTER UKRAINIAN IE                     */
            "\xD0\x85" => "\xD1\x95"    ,  /* U+0405: CYRILLIC CAPITAL LETTER DZE                              -> U+0455: CYRILLIC SMALL LETTER DZE                              */
            "\xD0\x86" => "\xD1\x96"    ,  /* U+0406: CYRILLIC CAPITAL LETTER BYELORUSSIAN-UKRAINIAN I         -> U+0456: CYRILLIC SMALL LETTER BYELORUSSIAN-UKRAINIAN I         */
            "\xD0\x87" => "\xD1\x97"    ,  /* U+0407: CYRILLIC CAPITAL LETTER YI                               -> U+0457: CYRILLIC SMALL LETTER YI                               */
            "\xD0\x88" => "\xD1\x98"    ,  /* U+0408: CYRILLIC CAPITAL LETTER JE                               -> U+0458: CYRILLIC SMALL LETTER JE                               */
            "\xD0\x89" => "\xD1\x99"    ,  /* U+0409: CYRILLIC CAPITAL LETTER LJE                              -> U+0459: CYRILLIC SMALL LETTER LJE                              */
            "\xD0\x8A" => "\xD1\x9A"    ,  /* U+040A: CYRILLIC CAPITAL LETTER NJE                              -> U+045A: CYRILLIC SMALL LETTER NJE                              */
            "\xD0\x8B" => "\xD1\x9B"    ,  /* U+040B: CYRILLIC CAPITAL LETTER TSHE                             -> U+045B: CYRILLIC SMALL LETTER TSHE                             */
            "\xD0\x8C" => "\xD1\x9C"    ,  /* U+040C: CYRILLIC CAPITAL LETTER KJE                              -> U+045C: CYRILLIC SMALL LETTER KJE                              */
            "\xD0\x8D" => "\xD1\x9D"    ,  /* U+040D: CYRILLIC CAPITAL LETTER I WITH GRAVE                     -> U+045D: CYRILLIC SMALL LETTER I WITH GRAVE                     */
            "\xD0\x8E" => "\xD1\x9E"    ,  /* U+040E: CYRILLIC CAPITAL LETTER SHORT U                          -> U+045E: CYRILLIC SMALL LETTER SHORT U                          */
            "\xD0\x8F" => "\xD1\x9F"    ,  /* U+040F: CYRILLIC CAPITAL LETTER DZHE                             -> U+045F: CYRILLIC SMALL LETTER DZHE                             */
            "\xD0\x90" => "\xD0\xB0"    ,  /* U+0410: CYRILLIC CAPITAL LETTER A                                -> U+0430: CYRILLIC SMALL LETTER A                                */
            "\xD0\x91" => "\xD0\xB1"    ,  /* U+0411: CYRILLIC CAPITAL LETTER BE                               -> U+0431: CYRILLIC SMALL LETTER BE                               */
            "\xD0\x92" => "\xD0\xB2"    ,  /* U+0412: CYRILLIC CAPITAL LETTER VE                               -> U+0432: CYRILLIC SMALL LETTER VE                               */
            "\xD0\x93" => "\xD0\xB3"    ,  /* U+0413: CYRILLIC CAPITAL LETTER GHE                              -> U+0433: CYRILLIC SMALL LETTER GHE                              */
            "\xD0\x94" => "\xD0\xB4"    ,  /* U+0414: CYRILLIC CAPITAL LETTER DE                               -> U+0434: CYRILLIC SMALL LETTER DE                               */
            "\xD0\x95" => "\xD0\xB5"    ,  /* U+0415: CYRILLIC CAPITAL LETTER IE                               -> U+0435: CYRILLIC SMALL LETTER IE                               */
            "\xD0\x96" => "\xD0\xB6"    ,  /* U+0416: CYRILLIC CAPITAL LETTER ZHE                              -> U+0436: CYRILLIC SMALL LETTER ZHE                              */
            "\xD0\x97" => "\xD0\xB7"    ,  /* U+0417: CYRILLIC CAPITAL LETTER ZE                               -> U+0437: CYRILLIC SMALL LETTER ZE                               */
            "\xD0\x98" => "\xD0\xB8"    ,  /* U+0418: CYRILLIC CAPITAL LETTER I                                -> U+0438: CYRILLIC SMALL LETTER I                                */
            "\xD0\x99" => "\xD0\xB9"    ,  /* U+0419: CYRILLIC CAPITAL LETTER SHORT I                          -> U+0439: CYRILLIC SMALL LETTER SHORT I                          */
            "\xD0\x9A" => "\xD0\xBA"    ,  /* U+041A: CYRILLIC CAPITAL LETTER KA                               -> U+043A: CYRILLIC SMALL LETTER KA                               */
            "\xD0\x9B" => "\xD0\xBB"    ,  /* U+041B: CYRILLIC CAPITAL LETTER EL                               -> U+043B: CYRILLIC SMALL LETTER EL                               */
            "\xD0\x9C" => "\xD0\xBC"    ,  /* U+041C: CYRILLIC CAPITAL LETTER EM                               -> U+043C: CYRILLIC SMALL LETTER EM                               */
            "\xD0\x9D" => "\xD0\xBD"    ,  /* U+041D: CYRILLIC CAPITAL LETTER EN                               -> U+043D: CYRILLIC SMALL LETTER EN                               */
            "\xD0\x9E" => "\xD0\xBE"    ,  /* U+041E: CYRILLIC CAPITAL LETTER O                                -> U+043E: CYRILLIC SMALL LETTER O                                */
            "\xD0\x9F" => "\xD0\xBF"    ,  /* U+041F: CYRILLIC CAPITAL LETTER PE                               -> U+043F: CYRILLIC SMALL LETTER PE                               */
            "\xD0\xA0" => "\xD1\x80"    ,  /* U+0420: CYRILLIC CAPITAL LETTER ER                               -> U+0440: CYRILLIC SMALL LETTER ER                               */
            "\xD0\xA1" => "\xD1\x81"    ,  /* U+0421: CYRILLIC CAPITAL LETTER ES                               -> U+0441: CYRILLIC SMALL LETTER ES                               */
            "\xD0\xA2" => "\xD1\x82"    ,  /* U+0422: CYRILLIC CAPITAL LETTER TE                               -> U+0442: CYRILLIC SMALL LETTER TE                               */
            "\xD0\xA3" => "\xD1\x83"    ,  /* U+0423: CYRILLIC CAPITAL LETTER U                                -> U+0443: CYRILLIC SMALL LETTER U                                */
            "\xD0\xA4" => "\xD1\x84"    ,  /* U+0424: CYRILLIC CAPITAL LETTER EF                               -> U+0444: CYRILLIC SMALL LETTER EF                               */
            "\xD0\xA5" => "\xD1\x85"    ,  /* U+0425: CYRILLIC CAPITAL LETTER HA                               -> U+0445: CYRILLIC SMALL LETTER HA                               */
            "\xD0\xA6" => "\xD1\x86"    ,  /* U+0426: CYRILLIC CAPITAL LETTER TSE                              -> U+0446: CYRILLIC SMALL LETTER TSE                              */
            "\xD0\xA7" => "\xD1\x87"    ,  /* U+0427: CYRILLIC CAPITAL LETTER CHE                              -> U+0447: CYRILLIC SMALL LETTER CHE                              */
            "\xD0\xA8" => "\xD1\x88"    ,  /* U+0428: CYRILLIC CAPITAL LETTER SHA                              -> U+0448: CYRILLIC SMALL LETTER SHA                              */
            "\xD0\xA9" => "\xD1\x89"    ,  /* U+0429: CYRILLIC CAPITAL LETTER SHCHA                            -> U+0449: CYRILLIC SMALL LETTER SHCHA                            */
            "\xD0\xAA" => "\xD1\x8A"    ,  /* U+042A: CYRILLIC CAPITAL LETTER HARD SIGN                        -> U+044A: CYRILLIC SMALL LETTER HARD SIGN                        */
            "\xD0\xAB" => "\xD1\x8B"    ,  /* U+042B: CYRILLIC CAPITAL LETTER YERU                             -> U+044B: CYRILLIC SMALL LETTER YERU                             */
            "\xD0\xAC" => "\xD1\x8C"    ,  /* U+042C: CYRILLIC CAPITAL LETTER SOFT SIGN                        -> U+044C: CYRILLIC SMALL LETTER SOFT SIGN                        */
            "\xD0\xAD" => "\xD1\x8D"    ,  /* U+042D: CYRILLIC CAPITAL LETTER E                                -> U+044D: CYRILLIC SMALL LETTER E                                */
            "\xD0\xAE" => "\xD1\x8E"    ,  /* U+042E: CYRILLIC CAPITAL LETTER YU                               -> U+044E: CYRILLIC SMALL LETTER YU                               */
            "\xD0\xAF" => "\xD1\x8F"    ,  /* U+042F: CYRILLIC CAPITAL LETTER YA                               -> U+044F: CYRILLIC SMALL LETTER YA                               */
            "\xD1\xA0" => "\xD1\xA1"    ,  /* U+0460: CYRILLIC CAPITAL LETTER OMEGA                            -> U+0461: CYRILLIC SMALL LETTER OMEGA                            */
            "\xD1\xA2" => "\xD1\xA3"    ,  /* U+0462: CYRILLIC CAPITAL LETTER YAT                              -> U+0463: CYRILLIC SMALL LETTER YAT                              */
            "\xD1\xA4" => "\xD1\xA5"    ,  /* U+0464: CYRILLIC CAPITAL LETTER IOTIFIED E                       -> U+0465: CYRILLIC SMALL LETTER IOTIFIED E                       */
            "\xD1\xA6" => "\xD1\xA7"    ,  /* U+0466: CYRILLIC CAPITAL LETTER LITTLE YUS                       -> U+0467: CYRILLIC SMALL LETTER LITTLE YUS                       */
            "\xD1\xA8" => "\xD1\xA9"    ,  /* U+0468: CYRILLIC CAPITAL LETTER IOTIFIED LITTLE YUS              -> U+0469: CYRILLIC SMALL LETTER IOTIFIED LITTLE YUS              */
            "\xD1\xAA" => "\xD1\xAB"    ,  /* U+046A: CYRILLIC CAPITAL LETTER BIG YUS                          -> U+046B: CYRILLIC SMALL LETTER BIG YUS                          */
            "\xD1\xAC" => "\xD1\xAD"    ,  /* U+046C: CYRILLIC CAPITAL LETTER IOTIFIED BIG YUS                 -> U+046D: CYRILLIC SMALL LETTER IOTIFIED BIG YUS                 */
            "\xD1\xAE" => "\xD1\xAF"    ,  /* U+046E: CYRILLIC CAPITAL LETTER KSI                              -> U+046F: CYRILLIC SMALL LETTER KSI                              */
            "\xD1\xB0" => "\xD1\xB1"    ,  /* U+0470: CYRILLIC CAPITAL LETTER PSI                              -> U+0471: CYRILLIC SMALL LETTER PSI                              */
            "\xD1\xB2" => "\xD1\xB3"    ,  /* U+0472: CYRILLIC CAPITAL LETTER FITA                             -> U+0473: CYRILLIC SMALL LETTER FITA                             */
            "\xD1\xB4" => "\xD1\xB5"    ,  /* U+0474: CYRILLIC CAPITAL LETTER IZHITSA                          -> U+0475: CYRILLIC SMALL LETTER IZHITSA                          */
            "\xD1\xB6" => "\xD1\xB7"    ,  /* U+0476: CYRILLIC CAPITAL LETTER IZHITSA WITH DOUBLE GRAVE ACCENT -> U+0477: CYRILLIC SMALL LETTER IZHITSA WITH DOUBLE GRAVE ACCENT */
            "\xD1\xB8" => "\xD1\xB9"    ,  /* U+0478: CYRILLIC CAPITAL LETTER UK                               -> U+0479: CYRILLIC SMALL LETTER UK                               */
            "\xD1\xBA" => "\xD1\xBB"    ,  /* U+047A: CYRILLIC CAPITAL LETTER ROUND OMEGA                      -> U+047B: CYRILLIC SMALL LETTER ROUND OMEGA                      */
            "\xD1\xBC" => "\xD1\xBD"    ,  /* U+047C: CYRILLIC CAPITAL LETTER OMEGA WITH TITLO                 -> U+047D: CYRILLIC SMALL LETTER OMEGA WITH TITLO                 */
            "\xD1\xBE" => "\xD1\xBF"    ,  /* U+047E: CYRILLIC CAPITAL LETTER OT                               -> U+047F: CYRILLIC SMALL LETTER OT                               */
            "\xD2\x80" => "\xD2\x81"    ,  /* U+0480: CYRILLIC CAPITAL LETTER KOPPA                            -> U+0481: CYRILLIC SMALL LETTER KOPPA                            */
            "\xD2\x8A" => "\xD2\x8B"    ,  /* U+048A: CYRILLIC CAPITAL LETTER SHORT I WITH TAIL                -> U+048B: CYRILLIC SMALL LETTER SHORT I WITH TAIL                */
            "\xD2\x8C" => "\xD2\x8D"    ,  /* U+048C: CYRILLIC CAPITAL LETTER SEMISOFT SIGN                    -> U+048D: CYRILLIC SMALL LETTER SEMISOFT SIGN                    */
            "\xD2\x8E" => "\xD2\x8F"    ,  /* U+048E: CYRILLIC CAPITAL LETTER ER WITH TICK                     -> U+048F: CYRILLIC SMALL LETTER ER WITH TICK                     */
            "\xD2\x90" => "\xD2\x91"    ,  /* U+0490: CYRILLIC CAPITAL LETTER GHE WITH UPTURN                  -> U+0491: CYRILLIC SMALL LETTER GHE WITH UPTURN                  */
            "\xD2\x92" => "\xD2\x93"    ,  /* U+0492: CYRILLIC CAPITAL LETTER GHE WITH STROKE                  -> U+0493: CYRILLIC SMALL LETTER GHE WITH STROKE                  */
            "\xD2\x94" => "\xD2\x95"    ,  /* U+0494: CYRILLIC CAPITAL LETTER GHE WITH MIDDLE HOOK             -> U+0495: CYRILLIC SMALL LETTER GHE WITH MIDDLE HOOK             */
            "\xD2\x96" => "\xD2\x97"    ,  /* U+0496: CYRILLIC CAPITAL LETTER ZHE WITH DESCENDER               -> U+0497: CYRILLIC SMALL LETTER ZHE WITH DESCENDER               */
            "\xD2\x98" => "\xD2\x99"    ,  /* U+0498: CYRILLIC CAPITAL LETTER ZE WITH DESCENDER                -> U+0499: CYRILLIC SMALL LETTER ZE WITH DESCENDER                */
            "\xD2\x9A" => "\xD2\x9B"    ,  /* U+049A: CYRILLIC CAPITAL LETTER KA WITH DESCENDER                -> U+049B: CYRILLIC SMALL LETTER KA WITH DESCENDER                */
            "\xD2\x9C" => "\xD2\x9D"    ,  /* U+049C: CYRILLIC CAPITAL LETTER KA WITH VERTICAL STROKE          -> U+049D: CYRILLIC SMALL LETTER KA WITH VERTICAL STROKE          */
            "\xD2\x9E" => "\xD2\x9F"    ,  /* U+049E: CYRILLIC CAPITAL LETTER KA WITH STROKE                   -> U+049F: CYRILLIC SMALL LETTER KA WITH STROKE                   */
            "\xD2\xA0" => "\xD2\xA1"    ,  /* U+04A0: CYRILLIC CAPITAL LETTER BASHKIR KA                       -> U+04A1: CYRILLIC SMALL LETTER BASHKIR KA                       */
            "\xD2\xA2" => "\xD2\xA3"    ,  /* U+04A2: CYRILLIC CAPITAL LETTER EN WITH DESCENDER                -> U+04A3: CYRILLIC SMALL LETTER EN WITH DESCENDER                */
            "\xD2\xA4" => "\xD2\xA5"    ,  /* U+04A4: CYRILLIC CAPITAL LIGATURE EN GHE                         -> U+04A5: CYRILLIC SMALL LIGATURE EN GHE                         */
            "\xD2\xA6" => "\xD2\xA7"    ,  /* U+04A6: CYRILLIC CAPITAL LETTER PE WITH MIDDLE HOOK              -> U+04A7: CYRILLIC SMALL LETTER PE WITH MIDDLE HOOK              */
            "\xD2\xA8" => "\xD2\xA9"    ,  /* U+04A8: CYRILLIC CAPITAL LETTER ABKHASIAN HA                     -> U+04A9: CYRILLIC SMALL LETTER ABKHASIAN HA                     */
            "\xD2\xAA" => "\xD2\xAB"    ,  /* U+04AA: CYRILLIC CAPITAL LETTER ES WITH DESCENDER                -> U+04AB: CYRILLIC SMALL LETTER ES WITH DESCENDER                */
            "\xD2\xAC" => "\xD2\xAD"    ,  /* U+04AC: CYRILLIC CAPITAL LETTER TE WITH DESCENDER                -> U+04AD: CYRILLIC SMALL LETTER TE WITH DESCENDER                */
            "\xD2\xAE" => "\xD2\xAF"    ,  /* U+04AE: CYRILLIC CAPITAL LETTER STRAIGHT U                       -> U+04AF: CYRILLIC SMALL LETTER STRAIGHT U                       */
            "\xD2\xB0" => "\xD2\xB1"    ,  /* U+04B0: CYRILLIC CAPITAL LETTER STRAIGHT U WITH STROKE           -> U+04B1: CYRILLIC SMALL LETTER STRAIGHT U WITH STROKE           */
            "\xD2\xB2" => "\xD2\xB3"    ,  /* U+04B2: CYRILLIC CAPITAL LETTER HA WITH DESCENDER                -> U+04B3: CYRILLIC SMALL LETTER HA WITH DESCENDER                */
            "\xD2\xB4" => "\xD2\xB5"    ,  /* U+04B4: CYRILLIC CAPITAL LIGATURE TE TSE                         -> U+04B5: CYRILLIC SMALL LIGATURE TE TSE                         */
            "\xD2\xB6" => "\xD2\xB7"    ,  /* U+04B6: CYRILLIC CAPITAL LETTER CHE WITH DESCENDER               -> U+04B7: CYRILLIC SMALL LETTER CHE WITH DESCENDER               */
            "\xD2\xB8" => "\xD2\xB9"    ,  /* U+04B8: CYRILLIC CAPITAL LETTER CHE WITH VERTICAL STROKE         -> U+04B9: CYRILLIC SMALL LETTER CHE WITH VERTICAL STROKE         */
            "\xD2\xBA" => "\xD2\xBB"    ,  /* U+04BA: CYRILLIC CAPITAL LETTER SHHA                             -> U+04BB: CYRILLIC SMALL LETTER SHHA                             */
            "\xD2\xBC" => "\xD2\xBD"    ,  /* U+04BC: CYRILLIC CAPITAL LETTER ABKHASIAN CHE                    -> U+04BD: CYRILLIC SMALL LETTER ABKHASIAN CHE                    */
            "\xD2\xBE" => "\xD2\xBF"    ,  /* U+04BE: CYRILLIC CAPITAL LETTER ABKHASIAN CHE WITH DESCENDER     -> U+04BF: CYRILLIC SMALL LETTER ABKHASIAN CHE WITH DESCENDER     */
            "\xD3\x80" => "\xD3\x8F"    ,  /* U+04C0: CYRILLIC LETTER PALOCHKA                                 -> U+04CF: CYRILLIC SMALL LETTER PALOCHKA                         */
            "\xD3\x81" => "\xD3\x82"    ,  /* U+04C1: CYRILLIC CAPITAL LETTER ZHE WITH BREVE                   -> U+04C2: CYRILLIC SMALL LETTER ZHE WITH BREVE                   */
            "\xD3\x83" => "\xD3\x84"    ,  /* U+04C3: CYRILLIC CAPITAL LETTER KA WITH HOOK                     -> U+04C4: CYRILLIC SMALL LETTER KA WITH HOOK                     */
            "\xD3\x85" => "\xD3\x86"    ,  /* U+04C5: CYRILLIC CAPITAL LETTER EL WITH TAIL                     -> U+04C6: CYRILLIC SMALL LETTER EL WITH TAIL                     */
            "\xD3\x87" => "\xD3\x88"    ,  /* U+04C7: CYRILLIC CAPITAL LETTER EN WITH HOOK                     -> U+04C8: CYRILLIC SMALL LETTER EN WITH HOOK                     */
            "\xD3\x89" => "\xD3\x8A"    ,  /* U+04C9: CYRILLIC CAPITAL LETTER EN WITH TAIL                     -> U+04CA: CYRILLIC SMALL LETTER EN WITH TAIL                     */
            "\xD3\x8B" => "\xD3\x8C"    ,  /* U+04CB: CYRILLIC CAPITAL LETTER KHAKASSIAN CHE                   -> U+04CC: CYRILLIC SMALL LETTER KHAKASSIAN CHE                   */
            "\xD3\x8D" => "\xD3\x8E"    ,  /* U+04CD: CYRILLIC CAPITAL LETTER EM WITH TAIL                     -> U+04CE: CYRILLIC SMALL LETTER EM WITH TAIL                     */
            "\xD3\x90" => "\xD3\x91"    ,  /* U+04D0: CYRILLIC CAPITAL LETTER A WITH BREVE                     -> U+04D1: CYRILLIC SMALL LETTER A WITH BREVE                     */
            "\xD3\x92" => "\xD3\x93"    ,  /* U+04D2: CYRILLIC CAPITAL LETTER A WITH DIAERESIS                 -> U+04D3: CYRILLIC SMALL LETTER A WITH DIAERESIS                 */
            "\xD3\x94" => "\xD3\x95"    ,  /* U+04D4: CYRILLIC CAPITAL LIGATURE A IE                           -> U+04D5: CYRILLIC SMALL LIGATURE A IE                           */
            "\xD3\x96" => "\xD3\x97"    ,  /* U+04D6: CYRILLIC CAPITAL LETTER IE WITH BREVE                    -> U+04D7: CYRILLIC SMALL LETTER IE WITH BREVE                    */
            "\xD3\x98" => "\xD3\x99"    ,  /* U+04D8: CYRILLIC CAPITAL LETTER SCHWA                            -> U+04D9: CYRILLIC SMALL LETTER SCHWA                            */
            "\xD3\x9A" => "\xD3\x9B"    ,  /* U+04DA: CYRILLIC CAPITAL LETTER SCHWA WITH DIAERESIS             -> U+04DB: CYRILLIC SMALL LETTER SCHWA WITH DIAERESIS             */
            "\xD3\x9C" => "\xD3\x9D"    ,  /* U+04DC: CYRILLIC CAPITAL LETTER ZHE WITH DIAERESIS               -> U+04DD: CYRILLIC SMALL LETTER ZHE WITH DIAERESIS               */
            "\xD3\x9E" => "\xD3\x9F"    ,  /* U+04DE: CYRILLIC CAPITAL LETTER ZE WITH DIAERESIS                -> U+04DF: CYRILLIC SMALL LETTER ZE WITH DIAERESIS                */
            "\xD3\xA0" => "\xD3\xA1"    ,  /* U+04E0: CYRILLIC CAPITAL LETTER ABKHASIAN DZE                    -> U+04E1: CYRILLIC SMALL LETTER ABKHASIAN DZE                    */
            "\xD3\xA2" => "\xD3\xA3"    ,  /* U+04E2: CYRILLIC CAPITAL LETTER I WITH MACRON                    -> U+04E3: CYRILLIC SMALL LETTER I WITH MACRON                    */
            "\xD3\xA4" => "\xD3\xA5"    ,  /* U+04E4: CYRILLIC CAPITAL LETTER I WITH DIAERESIS                 -> U+04E5: CYRILLIC SMALL LETTER I WITH DIAERESIS                 */
            "\xD3\xA6" => "\xD3\xA7"    ,  /* U+04E6: CYRILLIC CAPITAL LETTER O WITH DIAERESIS                 -> U+04E7: CYRILLIC SMALL LETTER O WITH DIAERESIS                 */
            "\xD3\xA8" => "\xD3\xA9"    ,  /* U+04E8: CYRILLIC CAPITAL LETTER BARRED O                         -> U+04E9: CYRILLIC SMALL LETTER BARRED O                         */
            "\xD3\xAA" => "\xD3\xAB"    ,  /* U+04EA: CYRILLIC CAPITAL LETTER BARRED O WITH DIAERESIS          -> U+04EB: CYRILLIC SMALL LETTER BARRED O WITH DIAERESIS          */
            "\xD3\xAC" => "\xD3\xAD"    ,  /* U+04EC: CYRILLIC CAPITAL LETTER E WITH DIAERESIS                 -> U+04ED: CYRILLIC SMALL LETTER E WITH DIAERESIS                 */
            "\xD3\xAE" => "\xD3\xAF"    ,  /* U+04EE: CYRILLIC CAPITAL LETTER U WITH MACRON                    -> U+04EF: CYRILLIC SMALL LETTER U WITH MACRON                    */
            "\xD3\xB0" => "\xD3\xB1"    ,  /* U+04F0: CYRILLIC CAPITAL LETTER U WITH DIAERESIS                 -> U+04F1: CYRILLIC SMALL LETTER U WITH DIAERESIS                 */
            "\xD3\xB2" => "\xD3\xB3"    ,  /* U+04F2: CYRILLIC CAPITAL LETTER U WITH DOUBLE ACUTE              -> U+04F3: CYRILLIC SMALL LETTER U WITH DOUBLE ACUTE              */
            "\xD3\xB4" => "\xD3\xB5"    ,  /* U+04F4: CYRILLIC CAPITAL LETTER CHE WITH DIAERESIS               -> U+04F5: CYRILLIC SMALL LETTER CHE WITH DIAERESIS               */
            "\xD3\xB6" => "\xD3\xB7"    ,  /* U+04F6: CYRILLIC CAPITAL LETTER GHE WITH DESCENDER               -> U+04F7: CYRILLIC SMALL LETTER GHE WITH DESCENDER               */
            "\xD3\xB8" => "\xD3\xB9"    ,  /* U+04F8: CYRILLIC CAPITAL LETTER YERU WITH DIAERESIS              -> U+04F9: CYRILLIC SMALL LETTER YERU WITH DIAERESIS              */
            "\xD3\xBA" => "\xD3\xBB"    ,  /* U+04FA: CYRILLIC CAPITAL LETTER GHE WITH STROKE AND HOOK         -> U+04FB: CYRILLIC SMALL LETTER GHE WITH STROKE AND HOOK         */
            "\xD3\xBC" => "\xD3\xBD"    ,  /* U+04FC: CYRILLIC CAPITAL LETTER HA WITH HOOK                     -> U+04FD: CYRILLIC SMALL LETTER HA WITH HOOK                     */
            "\xD3\xBE" => "\xD3\xBF"    ,  /* U+04FE: CYRILLIC CAPITAL LETTER HA WITH STROKE                   -> U+04FF: CYRILLIC SMALL LETTER HA WITH STROKE                   */
            "\xD4\x80" => "\xD4\x81"    ,  /* U+0500: CYRILLIC CAPITAL LETTER KOMI DE                          -> U+0501: CYRILLIC SMALL LETTER KOMI DE                          */
            "\xD4\x82" => "\xD4\x83"    ,  /* U+0502: CYRILLIC CAPITAL LETTER KOMI DJE                         -> U+0503: CYRILLIC SMALL LETTER KOMI DJE                         */
            "\xD4\x84" => "\xD4\x85"    ,  /* U+0504: CYRILLIC CAPITAL LETTER KOMI ZJE                         -> U+0505: CYRILLIC SMALL LETTER KOMI ZJE                         */
            "\xD4\x86" => "\xD4\x87"    ,  /* U+0506: CYRILLIC CAPITAL LETTER KOMI DZJE                        -> U+0507: CYRILLIC SMALL LETTER KOMI DZJE                        */
            "\xD4\x88" => "\xD4\x89"    ,  /* U+0508: CYRILLIC CAPITAL LETTER KOMI LJE                         -> U+0509: CYRILLIC SMALL LETTER KOMI LJE                         */
            "\xD4\x8A" => "\xD4\x8B"    ,  /* U+050A: CYRILLIC CAPITAL LETTER KOMI NJE                         -> U+050B: CYRILLIC SMALL LETTER KOMI NJE                         */
            "\xD4\x8C" => "\xD4\x8D"    ,  /* U+050C: CYRILLIC CAPITAL LETTER KOMI SJE                         -> U+050D: CYRILLIC SMALL LETTER KOMI SJE                         */
            "\xD4\x8E" => "\xD4\x8F"    ,  /* U+050E: CYRILLIC CAPITAL LETTER KOMI TJE                         -> U+050F: CYRILLIC SMALL LETTER KOMI TJE                         */
            "\xD4\x90" => "\xD4\x91"    ,  /* U+0510: CYRILLIC CAPITAL LETTER REVERSED ZE                      -> U+0511: CYRILLIC SMALL LETTER REVERSED ZE                      */
            "\xD4\x92" => "\xD4\x93"    ,  /* U+0512: CYRILLIC CAPITAL LETTER EL WITH HOOK                     -> U+0513: CYRILLIC SMALL LETTER EL WITH HOOK                     */
            "\xD4\x94" => "\xD4\x95"    ,  /* U+0514: CYRILLIC CAPITAL LETTER LHA                              -> U+0515: CYRILLIC SMALL LETTER LHA                              */
            "\xD4\x96" => "\xD4\x97"    ,  /* U+0516: CYRILLIC CAPITAL LETTER RHA                              -> U+0517: CYRILLIC SMALL LETTER RHA                              */
            "\xD4\x98" => "\xD4\x99"    ,  /* U+0518: CYRILLIC CAPITAL LETTER YAE                              -> U+0519: CYRILLIC SMALL LETTER YAE                              */
            "\xD4\x9A" => "\xD4\x9B"    ,  /* U+051A: CYRILLIC CAPITAL LETTER QA                               -> U+051B: CYRILLIC SMALL LETTER QA                               */
            "\xD4\x9C" => "\xD4\x9D"    ,  /* U+051C: CYRILLIC CAPITAL LETTER WE                               -> U+051D: CYRILLIC SMALL LETTER WE                               */
            "\xD4\x9E" => "\xD4\x9F"    ,  /* U+051E: CYRILLIC CAPITAL LETTER ALEUT KA                         -> U+051F: CYRILLIC SMALL LETTER ALEUT KA                         */
            "\xD4\xA0" => "\xD4\xA1"    ,  /* U+0520: CYRILLIC CAPITAL LETTER EL WITH MIDDLE HOOK              -> U+0521: CYRILLIC SMALL LETTER EL WITH MIDDLE HOOK              */
            "\xD4\xA2" => "\xD4\xA3"    ,  /* U+0522: CYRILLIC CAPITAL LETTER EN WITH MIDDLE HOOK              -> U+0523: CYRILLIC SMALL LETTER EN WITH MIDDLE HOOK              */
            "\xD4\xA4" => "\xD4\xA5"    ,  /* U+0524: CYRILLIC CAPITAL LETTER PE WITH DESCENDER                -> U+0525: CYRILLIC SMALL LETTER PE WITH DESCENDER                */
            "\xD4\xA6" => "\xD4\xA7"    ,  /* U+0526: CYRILLIC CAPITAL LETTER SHHA WITH DESCENDER              -> U+0527: CYRILLIC SMALL LETTER SHHA WITH DESCENDER              */
            "\xD4\xB1" => "\xD5\xA1"    ,  /* U+0531: ARMENIAN CAPITAL LETTER AYB                              -> U+0561: ARMENIAN SMALL LETTER AYB                              */
            "\xD4\xB2" => "\xD5\xA2"    ,  /* U+0532: ARMENIAN CAPITAL LETTER BEN                              -> U+0562: ARMENIAN SMALL LETTER BEN                              */
            "\xD4\xB3" => "\xD5\xA3"    ,  /* U+0533: ARMENIAN CAPITAL LETTER GIM                              -> U+0563: ARMENIAN SMALL LETTER GIM                              */
            "\xD4\xB4" => "\xD5\xA4"    ,  /* U+0534: ARMENIAN CAPITAL LETTER DA                               -> U+0564: ARMENIAN SMALL LETTER DA                               */
            "\xD4\xB5" => "\xD5\xA5"    ,  /* U+0535: ARMENIAN CAPITAL LETTER ECH                              -> U+0565: ARMENIAN SMALL LETTER ECH                              */
            "\xD4\xB6" => "\xD5\xA6"    ,  /* U+0536: ARMENIAN CAPITAL LETTER ZA                               -> U+0566: ARMENIAN SMALL LETTER ZA                               */
            "\xD4\xB7" => "\xD5\xA7"    ,  /* U+0537: ARMENIAN CAPITAL LETTER EH                               -> U+0567: ARMENIAN SMALL LETTER EH                               */
            "\xD4\xB8" => "\xD5\xA8"    ,  /* U+0538: ARMENIAN CAPITAL LETTER ET                               -> U+0568: ARMENIAN SMALL LETTER ET                               */
            "\xD4\xB9" => "\xD5\xA9"    ,  /* U+0539: ARMENIAN CAPITAL LETTER TO                               -> U+0569: ARMENIAN SMALL LETTER TO                               */
            "\xD4\xBA" => "\xD5\xAA"    ,  /* U+053A: ARMENIAN CAPITAL LETTER ZHE                              -> U+056A: ARMENIAN SMALL LETTER ZHE                              */
            "\xD4\xBB" => "\xD5\xAB"    ,  /* U+053B: ARMENIAN CAPITAL LETTER INI                              -> U+056B: ARMENIAN SMALL LETTER INI                              */
            "\xD4\xBC" => "\xD5\xAC"    ,  /* U+053C: ARMENIAN CAPITAL LETTER LIWN                             -> U+056C: ARMENIAN SMALL LETTER LIWN                             */
            "\xD4\xBD" => "\xD5\xAD"    ,  /* U+053D: ARMENIAN CAPITAL LETTER XEH                              -> U+056D: ARMENIAN SMALL LETTER XEH                              */
            "\xD4\xBE" => "\xD5\xAE"    ,  /* U+053E: ARMENIAN CAPITAL LETTER CA                               -> U+056E: ARMENIAN SMALL LETTER CA                               */
            "\xD4\xBF" => "\xD5\xAF"    ,  /* U+053F: ARMENIAN CAPITAL LETTER KEN                              -> U+056F: ARMENIAN SMALL LETTER KEN                              */
            "\xD5\x80" => "\xD5\xB0"    ,  /* U+0540: ARMENIAN CAPITAL LETTER HO                               -> U+0570: ARMENIAN SMALL LETTER HO                               */
            "\xD5\x81" => "\xD5\xB1"    ,  /* U+0541: ARMENIAN CAPITAL LETTER JA                               -> U+0571: ARMENIAN SMALL LETTER JA                               */
            "\xD5\x82" => "\xD5\xB2"    ,  /* U+0542: ARMENIAN CAPITAL LETTER GHAD                             -> U+0572: ARMENIAN SMALL LETTER GHAD                             */
            "\xD5\x83" => "\xD5\xB3"    ,  /* U+0543: ARMENIAN CAPITAL LETTER CHEH                             -> U+0573: ARMENIAN SMALL LETTER CHEH                             */
            "\xD5\x84" => "\xD5\xB4"    ,  /* U+0544: ARMENIAN CAPITAL LETTER MEN                              -> U+0574: ARMENIAN SMALL LETTER MEN                              */
            "\xD5\x85" => "\xD5\xB5"    ,  /* U+0545: ARMENIAN CAPITAL LETTER YI                               -> U+0575: ARMENIAN SMALL LETTER YI                               */
            "\xD5\x86" => "\xD5\xB6"    ,  /* U+0546: ARMENIAN CAPITAL LETTER NOW                              -> U+0576: ARMENIAN SMALL LETTER NOW                              */
            "\xD5\x87" => "\xD5\xB7"    ,  /* U+0547: ARMENIAN CAPITAL LETTER SHA                              -> U+0577: ARMENIAN SMALL LETTER SHA                              */
            "\xD5\x88" => "\xD5\xB8"    ,  /* U+0548: ARMENIAN CAPITAL LETTER VO                               -> U+0578: ARMENIAN SMALL LETTER VO                               */
            "\xD5\x89" => "\xD5\xB9"    ,  /* U+0549: ARMENIAN CAPITAL LETTER CHA                              -> U+0579: ARMENIAN SMALL LETTER CHA                              */
            "\xD5\x8A" => "\xD5\xBA"    ,  /* U+054A: ARMENIAN CAPITAL LETTER PEH                              -> U+057A: ARMENIAN SMALL LETTER PEH                              */
            "\xD5\x8B" => "\xD5\xBB"    ,  /* U+054B: ARMENIAN CAPITAL LETTER JHEH                             -> U+057B: ARMENIAN SMALL LETTER JHEH                             */
            "\xD5\x8C" => "\xD5\xBC"    ,  /* U+054C: ARMENIAN CAPITAL LETTER RA                               -> U+057C: ARMENIAN SMALL LETTER RA                               */
            "\xD5\x8D" => "\xD5\xBD"    ,  /* U+054D: ARMENIAN CAPITAL LETTER SEH                              -> U+057D: ARMENIAN SMALL LETTER SEH                              */
            "\xD5\x8E" => "\xD5\xBE"    ,  /* U+054E: ARMENIAN CAPITAL LETTER VEW                              -> U+057E: ARMENIAN SMALL LETTER VEW                              */
            "\xD5\x8F" => "\xD5\xBF"    ,  /* U+054F: ARMENIAN CAPITAL LETTER TIWN                             -> U+057F: ARMENIAN SMALL LETTER TIWN                             */
            "\xD5\x90" => "\xD6\x80"    ,  /* U+0550: ARMENIAN CAPITAL LETTER REH                              -> U+0580: ARMENIAN SMALL LETTER REH                              */
            "\xD5\x91" => "\xD6\x81"    ,  /* U+0551: ARMENIAN CAPITAL LETTER CO                               -> U+0581: ARMENIAN SMALL LETTER CO                               */
            "\xD5\x92" => "\xD6\x82"    ,  /* U+0552: ARMENIAN CAPITAL LETTER YIWN                             -> U+0582: ARMENIAN SMALL LETTER YIWN                             */
            "\xD5\x93" => "\xD6\x83"    ,  /* U+0553: ARMENIAN CAPITAL LETTER PIWR                             -> U+0583: ARMENIAN SMALL LETTER PIWR                             */
            "\xD5\x94" => "\xD6\x84"    ,  /* U+0554: ARMENIAN CAPITAL LETTER KEH                              -> U+0584: ARMENIAN SMALL LETTER KEH                              */
            "\xD5\x95" => "\xD6\x85"    ,  /* U+0555: ARMENIAN CAPITAL LETTER OH                               -> U+0585: ARMENIAN SMALL LETTER OH                               */
            "\xD5\x96" => "\xD6\x86"       /* U+0556: ARMENIAN CAPITAL LETTER FEH                              -> U+0586: ARMENIAN SMALL LETTER FEH                              */

        ];
    }

    private function setMap_ND()
    {
        $this->maps[self::CONVERT_TO_ND] = [
            "\xC2\xA0" => "\x20"    ,  /* U+00A0: NO-BREAK SPACE                                           -> U+0020: SPACE                                            */
            "\xC2\xA8" => "\x20"    ,  /* U+00A8: DIAERESIS                                                -> U+0020: SPACE                                            */
            "\xC2\xAA" => "\x61"    ,  /* U+00AA: FEMININE ORDINAL INDICATOR                               -> U+0061: LATIN SMALL LETTER A                             */
            "\xC2\xAF" => "\x20"    ,  /* U+00AF: MACRON                                                   -> U+0020: SPACE                                            */
            "\xC2\xB2" => "\x32"    ,  /* U+00B2: SUPERSCRIPT TWO                                          -> U+0032: DIGIT TWO                                        */
            "\xC2\xB3" => "\x33"    ,  /* U+00B3: SUPERSCRIPT THREE                                        -> U+0033: DIGIT THREE                                      */
            "\xC2\xB4" => "\x20"    ,  /* U+00B4: ACUTE ACCENT                                             -> U+0020: SPACE                                            */
            "\xC2\xB5" => "\xCE\xBC",  /* U+00B5: MICRO SIGN                                               -> U+03BC: GREEK SMALL LETTER MU                            */
            "\xC2\xB8" => "\x20"    ,  /* U+00B8: CEDILLA                                                  -> U+0020: SPACE                                            */
            "\xC2\xB9" => "\x31"    ,  /* U+00B9: SUPERSCRIPT ONE                                          -> U+0031: DIGIT ONE                                        */
            "\xC2\xBA" => "\x6F"    ,  /* U+00BA: MASCULINE ORDINAL INDICATOR                              -> U+006F: LATIN SMALL LETTER O                             */
            "\xC3\x80" => "\x41"    ,  /* U+00C0: LATIN CAPITAL LETTER A WITH GRAVE                        -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC3\x81" => "\x41"    ,  /* U+00C1: LATIN CAPITAL LETTER A WITH ACUTE                        -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC3\x82" => "\x41"    ,  /* U+00C2: LATIN CAPITAL LETTER A WITH CIRCUMFLEX                   -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC3\x83" => "\x41"    ,  /* U+00C3: LATIN CAPITAL LETTER A WITH TILDE                        -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC3\x84" => "\x41"    ,  /* U+00C4: LATIN CAPITAL LETTER A WITH DIAERESIS                    -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC3\x85" => "\x41"    ,  /* U+00C5: LATIN CAPITAL LETTER A WITH RING ABOVE                   -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC3\x87" => "\x43"    ,  /* U+00C7: LATIN CAPITAL LETTER C WITH CEDILLA                      -> U+0043: LATIN CAPITAL LETTER C                           */
            "\xC3\x88" => "\x45"    ,  /* U+00C8: LATIN CAPITAL LETTER E WITH GRAVE                        -> U+0045: LATIN CAPITAL LETTER E                           */
            "\xC3\x89" => "\x45"    ,  /* U+00C9: LATIN CAPITAL LETTER E WITH ACUTE                        -> U+0045: LATIN CAPITAL LETTER E                           */
            "\xC3\x8A" => "\x45"    ,  /* U+00CA: LATIN CAPITAL LETTER E WITH CIRCUMFLEX                   -> U+0045: LATIN CAPITAL LETTER E                           */
            "\xC3\x8B" => "\x45"    ,  /* U+00CB: LATIN CAPITAL LETTER E WITH DIAERESIS                    -> U+0045: LATIN CAPITAL LETTER E                           */
            "\xC3\x8C" => "\x49"    ,  /* U+00CC: LATIN CAPITAL LETTER I WITH GRAVE                        -> U+0049: LATIN CAPITAL LETTER I                           */
            "\xC3\x8D" => "\x49"    ,  /* U+00CD: LATIN CAPITAL LETTER I WITH ACUTE                        -> U+0049: LATIN CAPITAL LETTER I                           */
            "\xC3\x8E" => "\x49"    ,  /* U+00CE: LATIN CAPITAL LETTER I WITH CIRCUMFLEX                   -> U+0049: LATIN CAPITAL LETTER I                           */
            "\xC3\x8F" => "\x49"    ,  /* U+00CF: LATIN CAPITAL LETTER I WITH DIAERESIS                    -> U+0049: LATIN CAPITAL LETTER I                           */
            "\xC3\x91" => "\x4E"    ,  /* U+00D1: LATIN CAPITAL LETTER N WITH TILDE                        -> U+004E: LATIN CAPITAL LETTER N                           */
            "\xC3\x92" => "\x4F"    ,  /* U+00D2: LATIN CAPITAL LETTER O WITH GRAVE                        -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC3\x93" => "\x4F"    ,  /* U+00D3: LATIN CAPITAL LETTER O WITH ACUTE                        -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC3\x94" => "\x4F"    ,  /* U+00D4: LATIN CAPITAL LETTER O WITH CIRCUMFLEX                   -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC3\x95" => "\x4F"    ,  /* U+00D5: LATIN CAPITAL LETTER O WITH TILDE                        -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC3\x96" => "\x4F"    ,  /* U+00D6: LATIN CAPITAL LETTER O WITH DIAERESIS                    -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC3\x99" => "\x55"    ,  /* U+00D9: LATIN CAPITAL LETTER U WITH GRAVE                        -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC3\x9A" => "\x55"    ,  /* U+00DA: LATIN CAPITAL LETTER U WITH ACUTE                        -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC3\x9B" => "\x55"    ,  /* U+00DB: LATIN CAPITAL LETTER U WITH CIRCUMFLEX                   -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC3\x9C" => "\x55"    ,  /* U+00DC: LATIN CAPITAL LETTER U WITH DIAERESIS                    -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC3\x9D" => "\x59"    ,  /* U+00DD: LATIN CAPITAL LETTER Y WITH ACUTE                        -> U+0059: LATIN CAPITAL LETTER Y                           */
            "\xC3\xA0" => "\x61"    ,  /* U+00E0: LATIN SMALL LETTER A WITH GRAVE                          -> U+0061: LATIN SMALL LETTER A                             */
            "\xC3\xA1" => "\x61"    ,  /* U+00E1: LATIN SMALL LETTER A WITH ACUTE                          -> U+0061: LATIN SMALL LETTER A                             */
            "\xC3\xA2" => "\x61"    ,  /* U+00E2: LATIN SMALL LETTER A WITH CIRCUMFLEX                     -> U+0061: LATIN SMALL LETTER A                             */
            "\xC3\xA3" => "\x61"    ,  /* U+00E3: LATIN SMALL LETTER A WITH TILDE                          -> U+0061: LATIN SMALL LETTER A                             */
            "\xC3\xA4" => "\x61"    ,  /* U+00E4: LATIN SMALL LETTER A WITH DIAERESIS                      -> U+0061: LATIN SMALL LETTER A                             */
            "\xC3\xA5" => "\x61"    ,  /* U+00E5: LATIN SMALL LETTER A WITH RING ABOVE                     -> U+0061: LATIN SMALL LETTER A                             */
            "\xC3\xA7" => "\x63"    ,  /* U+00E7: LATIN SMALL LETTER C WITH CEDILLA                        -> U+0063: LATIN SMALL LETTER C                             */
            "\xC3\xA8" => "\x65"    ,  /* U+00E8: LATIN SMALL LETTER E WITH GRAVE                          -> U+0065: LATIN SMALL LETTER E                             */
            "\xC3\xA9" => "\x65"    ,  /* U+00E9: LATIN SMALL LETTER E WITH ACUTE                          -> U+0065: LATIN SMALL LETTER E                             */
            "\xC3\xAA" => "\x65"    ,  /* U+00EA: LATIN SMALL LETTER E WITH CIRCUMFLEX                     -> U+0065: LATIN SMALL LETTER E                             */
            "\xC3\xAB" => "\x65"    ,  /* U+00EB: LATIN SMALL LETTER E WITH DIAERESIS                      -> U+0065: LATIN SMALL LETTER E                             */
            "\xC3\xAC" => "\x69"    ,  /* U+00EC: LATIN SMALL LETTER I WITH GRAVE                          -> U+0069: LATIN SMALL LETTER I                             */
            "\xC3\xAD" => "\x69"    ,  /* U+00ED: LATIN SMALL LETTER I WITH ACUTE                          -> U+0069: LATIN SMALL LETTER I                             */
            "\xC3\xAE" => "\x69"    ,  /* U+00EE: LATIN SMALL LETTER I WITH CIRCUMFLEX                     -> U+0069: LATIN SMALL LETTER I                             */
            "\xC3\xAF" => "\x69"    ,  /* U+00EF: LATIN SMALL LETTER I WITH DIAERESIS                      -> U+0069: LATIN SMALL LETTER I                             */
            "\xC3\xB1" => "\x6E"    ,  /* U+00F1: LATIN SMALL LETTER N WITH TILDE                          -> U+006E: LATIN SMALL LETTER N                             */
            "\xC3\xB2" => "\x6F"    ,  /* U+00F2: LATIN SMALL LETTER O WITH GRAVE                          -> U+006F: LATIN SMALL LETTER O                             */
            "\xC3\xB3" => "\x6F"    ,  /* U+00F3: LATIN SMALL LETTER O WITH ACUTE                          -> U+006F: LATIN SMALL LETTER O                             */
            "\xC3\xB4" => "\x6F"    ,  /* U+00F4: LATIN SMALL LETTER O WITH CIRCUMFLEX                     -> U+006F: LATIN SMALL LETTER O                             */
            "\xC3\xB5" => "\x6F"    ,  /* U+00F5: LATIN SMALL LETTER O WITH TILDE                          -> U+006F: LATIN SMALL LETTER O                             */
            "\xC3\xB6" => "\x6F"    ,  /* U+00F6: LATIN SMALL LETTER O WITH DIAERESIS                      -> U+006F: LATIN SMALL LETTER O                             */
            "\xC3\xB9" => "\x75"    ,  /* U+00F9: LATIN SMALL LETTER U WITH GRAVE                          -> U+0075: LATIN SMALL LETTER U                             */
            "\xC3\xBA" => "\x75"    ,  /* U+00FA: LATIN SMALL LETTER U WITH ACUTE                          -> U+0075: LATIN SMALL LETTER U                             */
            "\xC3\xBB" => "\x75"    ,  /* U+00FB: LATIN SMALL LETTER U WITH CIRCUMFLEX                     -> U+0075: LATIN SMALL LETTER U                             */
            "\xC3\xBC" => "\x75"    ,  /* U+00FC: LATIN SMALL LETTER U WITH DIAERESIS                      -> U+0075: LATIN SMALL LETTER U                             */
            "\xC3\xBD" => "\x79"    ,  /* U+00FD: LATIN SMALL LETTER Y WITH ACUTE                          -> U+0079: LATIN SMALL LETTER Y                             */
            "\xC3\xBF" => "\x79"    ,  /* U+00FF: LATIN SMALL LETTER Y WITH DIAERESIS                      -> U+0079: LATIN SMALL LETTER Y                             */
            "\xC4\x80" => "\x41"    ,  /* U+0100: LATIN CAPITAL LETTER A WITH MACRON                       -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC4\x81" => "\x61"    ,  /* U+0101: LATIN SMALL LETTER A WITH MACRON                         -> U+0061: LATIN SMALL LETTER A                             */
            "\xC4\x82" => "\x41"    ,  /* U+0102: LATIN CAPITAL LETTER A WITH BREVE                        -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC4\x83" => "\x61"    ,  /* U+0103: LATIN SMALL LETTER A WITH BREVE                          -> U+0061: LATIN SMALL LETTER A                             */
            "\xC4\x84" => "\x41"    ,  /* U+0104: LATIN CAPITAL LETTER A WITH OGONEK                       -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC4\x85" => "\x61"    ,  /* U+0105: LATIN SMALL LETTER A WITH OGONEK                         -> U+0061: LATIN SMALL LETTER A                             */
            "\xC4\x86" => "\x43"    ,  /* U+0106: LATIN CAPITAL LETTER C WITH ACUTE                        -> U+0043: LATIN CAPITAL LETTER C                           */
            "\xC4\x87" => "\x63"    ,  /* U+0107: LATIN SMALL LETTER C WITH ACUTE                          -> U+0063: LATIN SMALL LETTER C                             */
            "\xC4\x88" => "\x43"    ,  /* U+0108: LATIN CAPITAL LETTER C WITH CIRCUMFLEX                   -> U+0043: LATIN CAPITAL LETTER C                           */
            "\xC4\x89" => "\x63"    ,  /* U+0109: LATIN SMALL LETTER C WITH CIRCUMFLEX                     -> U+0063: LATIN SMALL LETTER C                             */
            "\xC4\x8A" => "\x43"    ,  /* U+010A: LATIN CAPITAL LETTER C WITH DOT ABOVE                    -> U+0043: LATIN CAPITAL LETTER C                           */
            "\xC4\x8B" => "\x63"    ,  /* U+010B: LATIN SMALL LETTER C WITH DOT ABOVE                      -> U+0063: LATIN SMALL LETTER C                             */
            "\xC4\x8C" => "\x43"    ,  /* U+010C: LATIN CAPITAL LETTER C WITH CARON                        -> U+0043: LATIN CAPITAL LETTER C                           */
            "\xC4\x8D" => "\x63"    ,  /* U+010D: LATIN SMALL LETTER C WITH CARON                          -> U+0063: LATIN SMALL LETTER C                             */
            "\xC4\x8E" => "\x44"    ,  /* U+010E: LATIN CAPITAL LETTER D WITH CARON                        -> U+0044: LATIN CAPITAL LETTER D                           */
            "\xC4\x8F" => "\x64"    ,  /* U+010F: LATIN SMALL LETTER D WITH CARON                          -> U+0064: LATIN SMALL LETTER D                             */
            "\xC4\x92" => "\x45"    ,  /* U+0112: LATIN CAPITAL LETTER E WITH MACRON                       -> U+0045: LATIN CAPITAL LETTER E                           */
            "\xC4\x93" => "\x65"    ,  /* U+0113: LATIN SMALL LETTER E WITH MACRON                         -> U+0065: LATIN SMALL LETTER E                             */
            "\xC4\x94" => "\x45"    ,  /* U+0114: LATIN CAPITAL LETTER E WITH BREVE                        -> U+0045: LATIN CAPITAL LETTER E                           */
            "\xC4\x95" => "\x65"    ,  /* U+0115: LATIN SMALL LETTER E WITH BREVE                          -> U+0065: LATIN SMALL LETTER E                             */
            "\xC4\x96" => "\x45"    ,  /* U+0116: LATIN CAPITAL LETTER E WITH DOT ABOVE                    -> U+0045: LATIN CAPITAL LETTER E                           */
            "\xC4\x97" => "\x65"    ,  /* U+0117: LATIN SMALL LETTER E WITH DOT ABOVE                      -> U+0065: LATIN SMALL LETTER E                             */
            "\xC4\x98" => "\x45"    ,  /* U+0118: LATIN CAPITAL LETTER E WITH OGONEK                       -> U+0045: LATIN CAPITAL LETTER E                           */
            "\xC4\x99" => "\x65"    ,  /* U+0119: LATIN SMALL LETTER E WITH OGONEK                         -> U+0065: LATIN SMALL LETTER E                             */
            "\xC4\x9A" => "\x45"    ,  /* U+011A: LATIN CAPITAL LETTER E WITH CARON                        -> U+0045: LATIN CAPITAL LETTER E                           */
            "\xC4\x9B" => "\x65"    ,  /* U+011B: LATIN SMALL LETTER E WITH CARON                          -> U+0065: LATIN SMALL LETTER E                             */
            "\xC4\x9C" => "\x47"    ,  /* U+011C: LATIN CAPITAL LETTER G WITH CIRCUMFLEX                   -> U+0047: LATIN CAPITAL LETTER G                           */
            "\xC4\x9D" => "\x67"    ,  /* U+011D: LATIN SMALL LETTER G WITH CIRCUMFLEX                     -> U+0067: LATIN SMALL LETTER G                             */
            "\xC4\x9E" => "\x47"    ,  /* U+011E: LATIN CAPITAL LETTER G WITH BREVE                        -> U+0047: LATIN CAPITAL LETTER G                           */
            "\xC4\x9F" => "\x67"    ,  /* U+011F: LATIN SMALL LETTER G WITH BREVE                          -> U+0067: LATIN SMALL LETTER G                             */
            "\xC4\xA0" => "\x47"    ,  /* U+0120: LATIN CAPITAL LETTER G WITH DOT ABOVE                    -> U+0047: LATIN CAPITAL LETTER G                           */
            "\xC4\xA1" => "\x67"    ,  /* U+0121: LATIN SMALL LETTER G WITH DOT ABOVE                      -> U+0067: LATIN SMALL LETTER G                             */
            "\xC4\xA2" => "\x47"    ,  /* U+0122: LATIN CAPITAL LETTER G WITH CEDILLA                      -> U+0047: LATIN CAPITAL LETTER G                           */
            "\xC4\xA3" => "\x67"    ,  /* U+0123: LATIN SMALL LETTER G WITH CEDILLA                        -> U+0067: LATIN SMALL LETTER G                             */
            "\xC4\xA4" => "\x48"    ,  /* U+0124: LATIN CAPITAL LETTER H WITH CIRCUMFLEX                   -> U+0048: LATIN CAPITAL LETTER H                           */
            "\xC4\xA5" => "\x68"    ,  /* U+0125: LATIN SMALL LETTER H WITH CIRCUMFLEX                     -> U+0068: LATIN SMALL LETTER H                             */
            "\xC4\xA8" => "\x49"    ,  /* U+0128: LATIN CAPITAL LETTER I WITH TILDE                        -> U+0049: LATIN CAPITAL LETTER I                           */
            "\xC4\xA9" => "\x69"    ,  /* U+0129: LATIN SMALL LETTER I WITH TILDE                          -> U+0069: LATIN SMALL LETTER I                             */
            "\xC4\xAA" => "\x49"    ,  /* U+012A: LATIN CAPITAL LETTER I WITH MACRON                       -> U+0049: LATIN CAPITAL LETTER I                           */
            "\xC4\xAB" => "\x69"    ,  /* U+012B: LATIN SMALL LETTER I WITH MACRON                         -> U+0069: LATIN SMALL LETTER I                             */
            "\xC4\xAC" => "\x49"    ,  /* U+012C: LATIN CAPITAL LETTER I WITH BREVE                        -> U+0049: LATIN CAPITAL LETTER I                           */
            "\xC4\xAD" => "\x69"    ,  /* U+012D: LATIN SMALL LETTER I WITH BREVE                          -> U+0069: LATIN SMALL LETTER I                             */
            "\xC4\xAE" => "\x49"    ,  /* U+012E: LATIN CAPITAL LETTER I WITH OGONEK                       -> U+0049: LATIN CAPITAL LETTER I                           */
            "\xC4\xAF" => "\x69"    ,  /* U+012F: LATIN SMALL LETTER I WITH OGONEK                         -> U+0069: LATIN SMALL LETTER I                             */
            "\xC4\xB0" => "\x49"    ,  /* U+0130: LATIN CAPITAL LETTER I WITH DOT ABOVE                    -> U+0049: LATIN CAPITAL LETTER I                           */
            "\xC4\xB4" => "\x4A"    ,  /* U+0134: LATIN CAPITAL LETTER J WITH CIRCUMFLEX                   -> U+004A: LATIN CAPITAL LETTER J                           */
            "\xC4\xB5" => "\x6A"    ,  /* U+0135: LATIN SMALL LETTER J WITH CIRCUMFLEX                     -> U+006A: LATIN SMALL LETTER J                             */
            "\xC4\xB6" => "\x4B"    ,  /* U+0136: LATIN CAPITAL LETTER K WITH CEDILLA                      -> U+004B: LATIN CAPITAL LETTER K                           */
            "\xC4\xB7" => "\x6B"    ,  /* U+0137: LATIN SMALL LETTER K WITH CEDILLA                        -> U+006B: LATIN SMALL LETTER K                             */
            "\xC4\xB9" => "\x4C"    ,  /* U+0139: LATIN CAPITAL LETTER L WITH ACUTE                        -> U+004C: LATIN CAPITAL LETTER L                           */
            "\xC4\xBA" => "\x6C"    ,  /* U+013A: LATIN SMALL LETTER L WITH ACUTE                          -> U+006C: LATIN SMALL LETTER L                             */
            "\xC4\xBB" => "\x4C"    ,  /* U+013B: LATIN CAPITAL LETTER L WITH CEDILLA                      -> U+004C: LATIN CAPITAL LETTER L                           */
            "\xC4\xBC" => "\x6C"    ,  /* U+013C: LATIN SMALL LETTER L WITH CEDILLA                        -> U+006C: LATIN SMALL LETTER L                             */
            "\xC4\xBD" => "\x4C"    ,  /* U+013D: LATIN CAPITAL LETTER L WITH CARON                        -> U+004C: LATIN CAPITAL LETTER L                           */
            "\xC4\xBE" => "\x6C"    ,  /* U+013E: LATIN SMALL LETTER L WITH CARON                          -> U+006C: LATIN SMALL LETTER L                             */
            "\xC5\x83" => "\x4E"    ,  /* U+0143: LATIN CAPITAL LETTER N WITH ACUTE                        -> U+004E: LATIN CAPITAL LETTER N                           */
            "\xC5\x84" => "\x6E"    ,  /* U+0144: LATIN SMALL LETTER N WITH ACUTE                          -> U+006E: LATIN SMALL LETTER N                             */
            "\xC5\x85" => "\x4E"    ,  /* U+0145: LATIN CAPITAL LETTER N WITH CEDILLA                      -> U+004E: LATIN CAPITAL LETTER N                           */
            "\xC5\x86" => "\x6E"    ,  /* U+0146: LATIN SMALL LETTER N WITH CEDILLA                        -> U+006E: LATIN SMALL LETTER N                             */
            "\xC5\x87" => "\x4E"    ,  /* U+0147: LATIN CAPITAL LETTER N WITH CARON                        -> U+004E: LATIN CAPITAL LETTER N                           */
            "\xC5\x88" => "\x6E"    ,  /* U+0148: LATIN SMALL LETTER N WITH CARON                          -> U+006E: LATIN SMALL LETTER N                             */
            "\xC5\x8C" => "\x4F"    ,  /* U+014C: LATIN CAPITAL LETTER O WITH MACRON                       -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC5\x8D" => "\x6F"    ,  /* U+014D: LATIN SMALL LETTER O WITH MACRON                         -> U+006F: LATIN SMALL LETTER O                             */
            "\xC5\x8E" => "\x4F"    ,  /* U+014E: LATIN CAPITAL LETTER O WITH BREVE                        -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC5\x8F" => "\x6F"    ,  /* U+014F: LATIN SMALL LETTER O WITH BREVE                          -> U+006F: LATIN SMALL LETTER O                             */
            "\xC5\x90" => "\x4F"    ,  /* U+0150: LATIN CAPITAL LETTER O WITH DOUBLE ACUTE                 -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC5\x91" => "\x6F"    ,  /* U+0151: LATIN SMALL LETTER O WITH DOUBLE ACUTE                   -> U+006F: LATIN SMALL LETTER O                             */
            "\xC5\x94" => "\x52"    ,  /* U+0154: LATIN CAPITAL LETTER R WITH ACUTE                        -> U+0052: LATIN CAPITAL LETTER R                           */
            "\xC5\x95" => "\x72"    ,  /* U+0155: LATIN SMALL LETTER R WITH ACUTE                          -> U+0072: LATIN SMALL LETTER R                             */
            "\xC5\x96" => "\x52"    ,  /* U+0156: LATIN CAPITAL LETTER R WITH CEDILLA                      -> U+0052: LATIN CAPITAL LETTER R                           */
            "\xC5\x97" => "\x72"    ,  /* U+0157: LATIN SMALL LETTER R WITH CEDILLA                        -> U+0072: LATIN SMALL LETTER R                             */
            "\xC5\x98" => "\x52"    ,  /* U+0158: LATIN CAPITAL LETTER R WITH CARON                        -> U+0052: LATIN CAPITAL LETTER R                           */
            "\xC5\x99" => "\x72"    ,  /* U+0159: LATIN SMALL LETTER R WITH CARON                          -> U+0072: LATIN SMALL LETTER R                             */
            "\xC5\x9A" => "\x53"    ,  /* U+015A: LATIN CAPITAL LETTER S WITH ACUTE                        -> U+0053: LATIN CAPITAL LETTER S                           */
            "\xC5\x9B" => "\x73"    ,  /* U+015B: LATIN SMALL LETTER S WITH ACUTE                          -> U+0073: LATIN SMALL LETTER S                             */
            "\xC5\x9C" => "\x53"    ,  /* U+015C: LATIN CAPITAL LETTER S WITH CIRCUMFLEX                   -> U+0053: LATIN CAPITAL LETTER S                           */
            "\xC5\x9D" => "\x73"    ,  /* U+015D: LATIN SMALL LETTER S WITH CIRCUMFLEX                     -> U+0073: LATIN SMALL LETTER S                             */
            "\xC5\x9E" => "\x53"    ,  /* U+015E: LATIN CAPITAL LETTER S WITH CEDILLA                      -> U+0053: LATIN CAPITAL LETTER S                           */
            "\xC5\x9F" => "\x73"    ,  /* U+015F: LATIN SMALL LETTER S WITH CEDILLA                        -> U+0073: LATIN SMALL LETTER S                             */
            "\xC5\xA0" => "\x53"    ,  /* U+0160: LATIN CAPITAL LETTER S WITH CARON                        -> U+0053: LATIN CAPITAL LETTER S                           */
            "\xC5\xA1" => "\x73"    ,  /* U+0161: LATIN SMALL LETTER S WITH CARON                          -> U+0073: LATIN SMALL LETTER S                             */
            "\xC5\xA2" => "\x54"    ,  /* U+0162: LATIN CAPITAL LETTER T WITH CEDILLA                      -> U+0054: LATIN CAPITAL LETTER T                           */
            "\xC5\xA3" => "\x74"    ,  /* U+0163: LATIN SMALL LETTER T WITH CEDILLA                        -> U+0074: LATIN SMALL LETTER T                             */
            "\xC5\xA4" => "\x54"    ,  /* U+0164: LATIN CAPITAL LETTER T WITH CARON                        -> U+0054: LATIN CAPITAL LETTER T                           */
            "\xC5\xA5" => "\x74"    ,  /* U+0165: LATIN SMALL LETTER T WITH CARON                          -> U+0074: LATIN SMALL LETTER T                             */
            "\xC5\xA8" => "\x55"    ,  /* U+0168: LATIN CAPITAL LETTER U WITH TILDE                        -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC5\xA9" => "\x75"    ,  /* U+0169: LATIN SMALL LETTER U WITH TILDE                          -> U+0075: LATIN SMALL LETTER U                             */
            "\xC5\xAA" => "\x55"    ,  /* U+016A: LATIN CAPITAL LETTER U WITH MACRON                       -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC5\xAB" => "\x75"    ,  /* U+016B: LATIN SMALL LETTER U WITH MACRON                         -> U+0075: LATIN SMALL LETTER U                             */
            "\xC5\xAC" => "\x55"    ,  /* U+016C: LATIN CAPITAL LETTER U WITH BREVE                        -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC5\xAD" => "\x75"    ,  /* U+016D: LATIN SMALL LETTER U WITH BREVE                          -> U+0075: LATIN SMALL LETTER U                             */
            "\xC5\xAE" => "\x55"    ,  /* U+016E: LATIN CAPITAL LETTER U WITH RING ABOVE                   -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC5\xAF" => "\x75"    ,  /* U+016F: LATIN SMALL LETTER U WITH RING ABOVE                     -> U+0075: LATIN SMALL LETTER U                             */
            "\xC5\xB0" => "\x55"    ,  /* U+0170: LATIN CAPITAL LETTER U WITH DOUBLE ACUTE                 -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC5\xB1" => "\x75"    ,  /* U+0171: LATIN SMALL LETTER U WITH DOUBLE ACUTE                   -> U+0075: LATIN SMALL LETTER U                             */
            "\xC5\xB2" => "\x55"    ,  /* U+0172: LATIN CAPITAL LETTER U WITH OGONEK                       -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC5\xB3" => "\x75"    ,  /* U+0173: LATIN SMALL LETTER U WITH OGONEK                         -> U+0075: LATIN SMALL LETTER U                             */
            "\xC5\xB4" => "\x57"    ,  /* U+0174: LATIN CAPITAL LETTER W WITH CIRCUMFLEX                   -> U+0057: LATIN CAPITAL LETTER W                           */
            "\xC5\xB5" => "\x77"    ,  /* U+0175: LATIN SMALL LETTER W WITH CIRCUMFLEX                     -> U+0077: LATIN SMALL LETTER W                             */
            "\xC5\xB6" => "\x59"    ,  /* U+0176: LATIN CAPITAL LETTER Y WITH CIRCUMFLEX                   -> U+0059: LATIN CAPITAL LETTER Y                           */
            "\xC5\xB7" => "\x79"    ,  /* U+0177: LATIN SMALL LETTER Y WITH CIRCUMFLEX                     -> U+0079: LATIN SMALL LETTER Y                             */
            "\xC5\xB8" => "\x59"    ,  /* U+0178: LATIN CAPITAL LETTER Y WITH DIAERESIS                    -> U+0059: LATIN CAPITAL LETTER Y                           */
            "\xC5\xB9" => "\x5A"    ,  /* U+0179: LATIN CAPITAL LETTER Z WITH ACUTE                        -> U+005A: LATIN CAPITAL LETTER Z                           */
            "\xC5\xBA" => "\x7A"    ,  /* U+017A: LATIN SMALL LETTER Z WITH ACUTE                          -> U+007A: LATIN SMALL LETTER Z                             */
            "\xC5\xBB" => "\x5A"    ,  /* U+017B: LATIN CAPITAL LETTER Z WITH DOT ABOVE                    -> U+005A: LATIN CAPITAL LETTER Z                           */
            "\xC5\xBC" => "\x7A"    ,  /* U+017C: LATIN SMALL LETTER Z WITH DOT ABOVE                      -> U+007A: LATIN SMALL LETTER Z                             */
            "\xC5\xBD" => "\x5A"    ,  /* U+017D: LATIN CAPITAL LETTER Z WITH CARON                        -> U+005A: LATIN CAPITAL LETTER Z                           */
            "\xC5\xBE" => "\x7A"    ,  /* U+017E: LATIN SMALL LETTER Z WITH CARON                          -> U+007A: LATIN SMALL LETTER Z                             */
            "\xC5\xBF" => "\x73"    ,  /* U+017F: LATIN SMALL LETTER LONG S                                -> U+0073: LATIN SMALL LETTER S                             */
            "\xC6\xA0" => "\x4F"    ,  /* U+01A0: LATIN CAPITAL LETTER O WITH HORN                         -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC6\xA1" => "\x6F"    ,  /* U+01A1: LATIN SMALL LETTER O WITH HORN                           -> U+006F: LATIN SMALL LETTER O                             */
            "\xC6\xAF" => "\x55"    ,  /* U+01AF: LATIN CAPITAL LETTER U WITH HORN                         -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC6\xB0" => "\x75"    ,  /* U+01B0: LATIN SMALL LETTER U WITH HORN                           -> U+0075: LATIN SMALL LETTER U                             */
            "\xC7\x8D" => "\x41"    ,  /* U+01CD: LATIN CAPITAL LETTER A WITH CARON                        -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC7\x8E" => "\x61"    ,  /* U+01CE: LATIN SMALL LETTER A WITH CARON                          -> U+0061: LATIN SMALL LETTER A                             */
            "\xC7\x8F" => "\x49"    ,  /* U+01CF: LATIN CAPITAL LETTER I WITH CARON                        -> U+0049: LATIN CAPITAL LETTER I                           */
            "\xC7\x90" => "\x69"    ,  /* U+01D0: LATIN SMALL LETTER I WITH CARON                          -> U+0069: LATIN SMALL LETTER I                             */
            "\xC7\x91" => "\x4F"    ,  /* U+01D1: LATIN CAPITAL LETTER O WITH CARON                        -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC7\x92" => "\x6F"    ,  /* U+01D2: LATIN SMALL LETTER O WITH CARON                          -> U+006F: LATIN SMALL LETTER O                             */
            "\xC7\x93" => "\x55"    ,  /* U+01D3: LATIN CAPITAL LETTER U WITH CARON                        -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC7\x94" => "\x75"    ,  /* U+01D4: LATIN SMALL LETTER U WITH CARON                          -> U+0075: LATIN SMALL LETTER U                             */
            "\xC7\x95" => "\x55"    ,  /* U+01D5: LATIN CAPITAL LETTER U WITH DIAERESIS AND MACRON         -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC7\x96" => "\x75"    ,  /* U+01D6: LATIN SMALL LETTER U WITH DIAERESIS AND MACRON           -> U+0075: LATIN SMALL LETTER U                             */
            "\xC7\x97" => "\x55"    ,  /* U+01D7: LATIN CAPITAL LETTER U WITH DIAERESIS AND ACUTE          -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC7\x98" => "\x75"    ,  /* U+01D8: LATIN SMALL LETTER U WITH DIAERESIS AND ACUTE            -> U+0075: LATIN SMALL LETTER U                             */
            "\xC7\x99" => "\x55"    ,  /* U+01D9: LATIN CAPITAL LETTER U WITH DIAERESIS AND CARON          -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC7\x9A" => "\x75"    ,  /* U+01DA: LATIN SMALL LETTER U WITH DIAERESIS AND CARON            -> U+0075: LATIN SMALL LETTER U                             */
            "\xC7\x9B" => "\x55"    ,  /* U+01DB: LATIN CAPITAL LETTER U WITH DIAERESIS AND GRAVE          -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC7\x9C" => "\x75"    ,  /* U+01DC: LATIN SMALL LETTER U WITH DIAERESIS AND GRAVE            -> U+0075: LATIN SMALL LETTER U                             */
            "\xC7\x9E" => "\x41"    ,  /* U+01DE: LATIN CAPITAL LETTER A WITH DIAERESIS AND MACRON         -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC7\x9F" => "\x61"    ,  /* U+01DF: LATIN SMALL LETTER A WITH DIAERESIS AND MACRON           -> U+0061: LATIN SMALL LETTER A                             */
            "\xC7\xA0" => "\x41"    ,  /* U+01E0: LATIN CAPITAL LETTER A WITH DOT ABOVE AND MACRON         -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC7\xA1" => "\x61"    ,  /* U+01E1: LATIN SMALL LETTER A WITH DOT ABOVE AND MACRON           -> U+0061: LATIN SMALL LETTER A                             */
            "\xC7\xA2" => "\xC3\x86",  /* U+01E2: LATIN CAPITAL LETTER AE WITH MACRON                      -> U+00C6: LATIN CAPITAL LETTER AE                          */
            "\xC7\xA3" => "\xC3\xA6",  /* U+01E3: LATIN SMALL LETTER AE WITH MACRON                        -> U+00E6: LATIN SMALL LETTER AE                            */
            "\xC7\xA6" => "\x47"    ,  /* U+01E6: LATIN CAPITAL LETTER G WITH CARON                        -> U+0047: LATIN CAPITAL LETTER G                           */
            "\xC7\xA7" => "\x67"    ,  /* U+01E7: LATIN SMALL LETTER G WITH CARON                          -> U+0067: LATIN SMALL LETTER G                             */
            "\xC7\xA8" => "\x4B"    ,  /* U+01E8: LATIN CAPITAL LETTER K WITH CARON                        -> U+004B: LATIN CAPITAL LETTER K                           */
            "\xC7\xA9" => "\x6B"    ,  /* U+01E9: LATIN SMALL LETTER K WITH CARON                          -> U+006B: LATIN SMALL LETTER K                             */
            "\xC7\xAA" => "\x4F"    ,  /* U+01EA: LATIN CAPITAL LETTER O WITH OGONEK                       -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC7\xAB" => "\x6F"    ,  /* U+01EB: LATIN SMALL LETTER O WITH OGONEK                         -> U+006F: LATIN SMALL LETTER O                             */
            "\xC7\xAC" => "\x4F"    ,  /* U+01EC: LATIN CAPITAL LETTER O WITH OGONEK AND MACRON            -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC7\xAD" => "\x6F"    ,  /* U+01ED: LATIN SMALL LETTER O WITH OGONEK AND MACRON              -> U+006F: LATIN SMALL LETTER O                             */
            "\xC7\xAE" => "\xC6\xB7",  /* U+01EE: LATIN CAPITAL LETTER EZH WITH CARON                      -> U+01B7: LATIN CAPITAL LETTER EZH                         */
            "\xC7\xAF" => "\xCA\x92",  /* U+01EF: LATIN SMALL LETTER EZH WITH CARON                        -> U+0292: LATIN SMALL LETTER EZH                           */
            "\xC7\xB0" => "\x6A"    ,  /* U+01F0: LATIN SMALL LETTER J WITH CARON                          -> U+006A: LATIN SMALL LETTER J                             */
            "\xC7\xB4" => "\x47"    ,  /* U+01F4: LATIN CAPITAL LETTER G WITH ACUTE                        -> U+0047: LATIN CAPITAL LETTER G                           */
            "\xC7\xB5" => "\x67"    ,  /* U+01F5: LATIN SMALL LETTER G WITH ACUTE                          -> U+0067: LATIN SMALL LETTER G                             */
            "\xC7\xB8" => "\x4E"    ,  /* U+01F8: LATIN CAPITAL LETTER N WITH GRAVE                        -> U+004E: LATIN CAPITAL LETTER N                           */
            "\xC7\xB9" => "\x6E"    ,  /* U+01F9: LATIN SMALL LETTER N WITH GRAVE                          -> U+006E: LATIN SMALL LETTER N                             */
            "\xC7\xBA" => "\x41"    ,  /* U+01FA: LATIN CAPITAL LETTER A WITH RING ABOVE AND ACUTE         -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC7\xBB" => "\x61"    ,  /* U+01FB: LATIN SMALL LETTER A WITH RING ABOVE AND ACUTE           -> U+0061: LATIN SMALL LETTER A                             */
            "\xC7\xBC" => "\xC3\x86",  /* U+01FC: LATIN CAPITAL LETTER AE WITH ACUTE                       -> U+00C6: LATIN CAPITAL LETTER AE                          */
            "\xC7\xBD" => "\xC3\xA6",  /* U+01FD: LATIN SMALL LETTER AE WITH ACUTE                         -> U+00E6: LATIN SMALL LETTER AE                            */
            "\xC7\xBE" => "\xC3\x98",  /* U+01FE: LATIN CAPITAL LETTER O WITH STROKE AND ACUTE             -> U+00D8: LATIN CAPITAL LETTER O WITH STROKE               */
            "\xC7\xBF" => "\xC3\xB8",  /* U+01FF: LATIN SMALL LETTER O WITH STROKE AND ACUTE               -> U+00F8: LATIN SMALL LETTER O WITH STROKE                 */
            "\xC8\x80" => "\x41"    ,  /* U+0200: LATIN CAPITAL LETTER A WITH DOUBLE GRAVE                 -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC8\x81" => "\x61"    ,  /* U+0201: LATIN SMALL LETTER A WITH DOUBLE GRAVE                   -> U+0061: LATIN SMALL LETTER A                             */
            "\xC8\x82" => "\x41"    ,  /* U+0202: LATIN CAPITAL LETTER A WITH INVERTED BREVE               -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC8\x83" => "\x61"    ,  /* U+0203: LATIN SMALL LETTER A WITH INVERTED BREVE                 -> U+0061: LATIN SMALL LETTER A                             */
            "\xC8\x84" => "\x45"    ,  /* U+0204: LATIN CAPITAL LETTER E WITH DOUBLE GRAVE                 -> U+0045: LATIN CAPITAL LETTER E                           */
            "\xC8\x85" => "\x65"    ,  /* U+0205: LATIN SMALL LETTER E WITH DOUBLE GRAVE                   -> U+0065: LATIN SMALL LETTER E                             */
            "\xC8\x86" => "\x45"    ,  /* U+0206: LATIN CAPITAL LETTER E WITH INVERTED BREVE               -> U+0045: LATIN CAPITAL LETTER E                           */
            "\xC8\x87" => "\x65"    ,  /* U+0207: LATIN SMALL LETTER E WITH INVERTED BREVE                 -> U+0065: LATIN SMALL LETTER E                             */
            "\xC8\x88" => "\x49"    ,  /* U+0208: LATIN CAPITAL LETTER I WITH DOUBLE GRAVE                 -> U+0049: LATIN CAPITAL LETTER I                           */
            "\xC8\x89" => "\x69"    ,  /* U+0209: LATIN SMALL LETTER I WITH DOUBLE GRAVE                   -> U+0069: LATIN SMALL LETTER I                             */
            "\xC8\x8A" => "\x49"    ,  /* U+020A: LATIN CAPITAL LETTER I WITH INVERTED BREVE               -> U+0049: LATIN CAPITAL LETTER I                           */
            "\xC8\x8B" => "\x69"    ,  /* U+020B: LATIN SMALL LETTER I WITH INVERTED BREVE                 -> U+0069: LATIN SMALL LETTER I                             */
            "\xC8\x8C" => "\x4F"    ,  /* U+020C: LATIN CAPITAL LETTER O WITH DOUBLE GRAVE                 -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC8\x8D" => "\x6F"    ,  /* U+020D: LATIN SMALL LETTER O WITH DOUBLE GRAVE                   -> U+006F: LATIN SMALL LETTER O                             */
            "\xC8\x8E" => "\x4F"    ,  /* U+020E: LATIN CAPITAL LETTER O WITH INVERTED BREVE               -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC8\x8F" => "\x6F"    ,  /* U+020F: LATIN SMALL LETTER O WITH INVERTED BREVE                 -> U+006F: LATIN SMALL LETTER O                             */
            "\xC8\x90" => "\x52"    ,  /* U+0210: LATIN CAPITAL LETTER R WITH DOUBLE GRAVE                 -> U+0052: LATIN CAPITAL LETTER R                           */
            "\xC8\x91" => "\x72"    ,  /* U+0211: LATIN SMALL LETTER R WITH DOUBLE GRAVE                   -> U+0072: LATIN SMALL LETTER R                             */
            "\xC8\x92" => "\x52"    ,  /* U+0212: LATIN CAPITAL LETTER R WITH INVERTED BREVE               -> U+0052: LATIN CAPITAL LETTER R                           */
            "\xC8\x93" => "\x72"    ,  /* U+0213: LATIN SMALL LETTER R WITH INVERTED BREVE                 -> U+0072: LATIN SMALL LETTER R                             */
            "\xC8\x94" => "\x55"    ,  /* U+0214: LATIN CAPITAL LETTER U WITH DOUBLE GRAVE                 -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC8\x95" => "\x75"    ,  /* U+0215: LATIN SMALL LETTER U WITH DOUBLE GRAVE                   -> U+0075: LATIN SMALL LETTER U                             */
            "\xC8\x96" => "\x55"    ,  /* U+0216: LATIN CAPITAL LETTER U WITH INVERTED BREVE               -> U+0055: LATIN CAPITAL LETTER U                           */
            "\xC8\x97" => "\x75"    ,  /* U+0217: LATIN SMALL LETTER U WITH INVERTED BREVE                 -> U+0075: LATIN SMALL LETTER U                             */
            "\xC8\x98" => "\x53"    ,  /* U+0218: LATIN CAPITAL LETTER S WITH COMMA BELOW                  -> U+0053: LATIN CAPITAL LETTER S                           */
            "\xC8\x99" => "\x73"    ,  /* U+0219: LATIN SMALL LETTER S WITH COMMA BELOW                    -> U+0073: LATIN SMALL LETTER S                             */
            "\xC8\x9A" => "\x54"    ,  /* U+021A: LATIN CAPITAL LETTER T WITH COMMA BELOW                  -> U+0054: LATIN CAPITAL LETTER T                           */
            "\xC8\x9B" => "\x74"    ,  /* U+021B: LATIN SMALL LETTER T WITH COMMA BELOW                    -> U+0074: LATIN SMALL LETTER T                             */
            "\xC8\x9E" => "\x48"    ,  /* U+021E: LATIN CAPITAL LETTER H WITH CARON                        -> U+0048: LATIN CAPITAL LETTER H                           */
            "\xC8\x9F" => "\x68"    ,  /* U+021F: LATIN SMALL LETTER H WITH CARON                          -> U+0068: LATIN SMALL LETTER H                             */
            "\xC8\xA6" => "\x41"    ,  /* U+0226: LATIN CAPITAL LETTER A WITH DOT ABOVE                    -> U+0041: LATIN CAPITAL LETTER A                           */
            "\xC8\xA7" => "\x61"    ,  /* U+0227: LATIN SMALL LETTER A WITH DOT ABOVE                      -> U+0061: LATIN SMALL LETTER A                             */
            "\xC8\xA8" => "\x45"    ,  /* U+0228: LATIN CAPITAL LETTER E WITH CEDILLA                      -> U+0045: LATIN CAPITAL LETTER E                           */
            "\xC8\xA9" => "\x65"    ,  /* U+0229: LATIN SMALL LETTER E WITH CEDILLA                        -> U+0065: LATIN SMALL LETTER E                             */
            "\xC8\xAA" => "\x4F"    ,  /* U+022A: LATIN CAPITAL LETTER O WITH DIAERESIS AND MACRON         -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC8\xAB" => "\x6F"    ,  /* U+022B: LATIN SMALL LETTER O WITH DIAERESIS AND MACRON           -> U+006F: LATIN SMALL LETTER O                             */
            "\xC8\xAC" => "\x4F"    ,  /* U+022C: LATIN CAPITAL LETTER O WITH TILDE AND MACRON             -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC8\xAD" => "\x6F"    ,  /* U+022D: LATIN SMALL LETTER O WITH TILDE AND MACRON               -> U+006F: LATIN SMALL LETTER O                             */
            "\xC8\xAE" => "\x4F"    ,  /* U+022E: LATIN CAPITAL LETTER O WITH DOT ABOVE                    -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC8\xAF" => "\x6F"    ,  /* U+022F: LATIN SMALL LETTER O WITH DOT ABOVE                      -> U+006F: LATIN SMALL LETTER O                             */
            "\xC8\xB0" => "\x4F"    ,  /* U+0230: LATIN CAPITAL LETTER O WITH DOT ABOVE AND MACRON         -> U+004F: LATIN CAPITAL LETTER O                           */
            "\xC8\xB1" => "\x6F"    ,  /* U+0231: LATIN SMALL LETTER O WITH DOT ABOVE AND MACRON           -> U+006F: LATIN SMALL LETTER O                             */
            "\xC8\xB2" => "\x59"    ,  /* U+0232: LATIN CAPITAL LETTER Y WITH MACRON                       -> U+0059: LATIN CAPITAL LETTER Y                           */
            "\xC8\xB3" => "\x79"    ,  /* U+0233: LATIN SMALL LETTER Y WITH MACRON                         -> U+0079: LATIN SMALL LETTER Y                             */
            "\xCA\xB0" => "\x68"    ,  /* U+02B0: MODIFIER LETTER SMALL H                                  -> U+0068: LATIN SMALL LETTER H                             */
            "\xCA\xB1" => "\xC9\xA6",  /* U+02B1: MODIFIER LETTER SMALL H WITH HOOK                        -> U+0266: LATIN SMALL LETTER H WITH HOOK                   */
            "\xCA\xB2" => "\x6A"    ,  /* U+02B2: MODIFIER LETTER SMALL J                                  -> U+006A: LATIN SMALL LETTER J                             */
            "\xCA\xB3" => "\x72"    ,  /* U+02B3: MODIFIER LETTER SMALL R                                  -> U+0072: LATIN SMALL LETTER R                             */
            "\xCA\xB4" => "\xC9\xB9",  /* U+02B4: MODIFIER LETTER SMALL TURNED R                           -> U+0279: LATIN SMALL LETTER TURNED R                      */
            "\xCA\xB5" => "\xC9\xBB",  /* U+02B5: MODIFIER LETTER SMALL TURNED R WITH HOOK                 -> U+027B: LATIN SMALL LETTER TURNED R WITH HOOK            */
            "\xCA\xB6" => "\xCA\x81",  /* U+02B6: MODIFIER LETTER SMALL CAPITAL INVERTED R                 -> U+0281: LATIN LETTER SMALL CAPITAL INVERTED R            */
            "\xCA\xB7" => "\x77"    ,  /* U+02B7: MODIFIER LETTER SMALL W                                  -> U+0077: LATIN SMALL LETTER W                             */
            "\xCA\xB8" => "\x79"    ,  /* U+02B8: MODIFIER LETTER SMALL Y                                  -> U+0079: LATIN SMALL LETTER Y                             */
            "\xCB\x98" => "\x20"    ,  /* U+02D8: BREVE                                                    -> U+0020: SPACE                                            */
            "\xCB\x99" => "\x20"    ,  /* U+02D9: DOT ABOVE                                                -> U+0020: SPACE                                            */
            "\xCB\x9A" => "\x20"    ,  /* U+02DA: RING ABOVE                                               -> U+0020: SPACE                                            */
            "\xCB\x9B" => "\x20"    ,  /* U+02DB: OGONEK                                                   -> U+0020: SPACE                                            */
            "\xCB\x9C" => "\x20"    ,  /* U+02DC: SMALL TILDE                                              -> U+0020: SPACE                                            */
            "\xCB\x9D" => "\x20"    ,  /* U+02DD: DOUBLE ACUTE ACCENT                                      -> U+0020: SPACE                                            */
            "\xCB\xA0" => "\xC9\xA3",  /* U+02E0: MODIFIER LETTER SMALL GAMMA                              -> U+0263: LATIN SMALL LETTER GAMMA                         */
            "\xCB\xA1" => "\x6C"    ,  /* U+02E1: MODIFIER LETTER SMALL L                                  -> U+006C: LATIN SMALL LETTER L                             */
            "\xCB\xA2" => "\x73"    ,  /* U+02E2: MODIFIER LETTER SMALL S                                  -> U+0073: LATIN SMALL LETTER S                             */
            "\xCB\xA3" => "\x78"    ,  /* U+02E3: MODIFIER LETTER SMALL X                                  -> U+0078: LATIN SMALL LETTER X                             */
            "\xCB\xA4" => "\xCA\x95",  /* U+02E4: MODIFIER LETTER SMALL REVERSED GLOTTAL STOP              -> U+0295: LATIN LETTER PHARYNGEAL VOICED FRICATIVE         */
            "\xCD\xB4" => "\xCA\xB9",  /* U+0374: GREEK NUMERAL SIGN                                       -> U+02B9: MODIFIER LETTER PRIME                            */
            "\xCD\xBA" => "\x20"    ,  /* U+037A: GREEK YPOGEGRAMMENI                                      -> U+0020: SPACE                                            */
            "\xCD\xBE" => "\x3B"    ,  /* U+037E: GREEK QUESTION MARK                                      -> U+003B: SEMICOLON                                        */
            "\xCE\x84" => "\x20"    ,  /* U+0384: GREEK TONOS                                              -> U+0020: SPACE                                            */
            "\xCE\x85" => "\x20"    ,  /* U+0385: GREEK DIALYTIKA TONOS                                    -> U+0020: SPACE                                            */
            "\xCE\x86" => "\xCE\x91",  /* U+0386: GREEK CAPITAL LETTER ALPHA WITH TONOS                    -> U+0391: GREEK CAPITAL LETTER ALPHA                       */
            "\xCE\x87" => "\xC2\xB7",  /* U+0387: GREEK ANO TELEIA                                         -> U+00B7: MIDDLE DOT                                       */
            "\xCE\x88" => "\xCE\x95",  /* U+0388: GREEK CAPITAL LETTER EPSILON WITH TONOS                  -> U+0395: GREEK CAPITAL LETTER EPSILON                     */
            "\xCE\x89" => "\xCE\x97",  /* U+0389: GREEK CAPITAL LETTER ETA WITH TONOS                      -> U+0397: GREEK CAPITAL LETTER ETA                         */
            "\xCE\x8A" => "\xCE\x99",  /* U+038A: GREEK CAPITAL LETTER IOTA WITH TONOS                     -> U+0399: GREEK CAPITAL LETTER IOTA                        */
            "\xCE\x8C" => "\xCE\x9F",  /* U+038C: GREEK CAPITAL LETTER OMICRON WITH TONOS                  -> U+039F: GREEK CAPITAL LETTER OMICRON                     */
            "\xCE\x8E" => "\xCE\xA5",  /* U+038E: GREEK CAPITAL LETTER UPSILON WITH TONOS                  -> U+03A5: GREEK CAPITAL LETTER UPSILON                     */
            "\xCE\x8F" => "\xCE\xA9",  /* U+038F: GREEK CAPITAL LETTER OMEGA WITH TONOS                    -> U+03A9: GREEK CAPITAL LETTER OMEGA                       */
            "\xCE\x90" => "\xCE\xB9",  /* U+0390: GREEK SMALL LETTER IOTA WITH DIALYTIKA AND TONOS         -> U+03B9: GREEK SMALL LETTER IOTA                          */
            "\xCE\xAA" => "\xCE\x99",  /* U+03AA: GREEK CAPITAL LETTER IOTA WITH DIALYTIKA                 -> U+0399: GREEK CAPITAL LETTER IOTA                        */
            "\xCE\xAB" => "\xCE\xA5",  /* U+03AB: GREEK CAPITAL LETTER UPSILON WITH DIALYTIKA              -> U+03A5: GREEK CAPITAL LETTER UPSILON                     */
            "\xCE\xAC" => "\xCE\xB1",  /* U+03AC: GREEK SMALL LETTER ALPHA WITH TONOS                      -> U+03B1: GREEK SMALL LETTER ALPHA                         */
            "\xCE\xAD" => "\xCE\xB5",  /* U+03AD: GREEK SMALL LETTER EPSILON WITH TONOS                    -> U+03B5: GREEK SMALL LETTER EPSILON                       */
            "\xCE\xAE" => "\xCE\xB7",  /* U+03AE: GREEK SMALL LETTER ETA WITH TONOS                        -> U+03B7: GREEK SMALL LETTER ETA                           */
            "\xCE\xAF" => "\xCE\xB9",  /* U+03AF: GREEK SMALL LETTER IOTA WITH TONOS                       -> U+03B9: GREEK SMALL LETTER IOTA                          */
            "\xCE\xB0" => "\xCF\x85",  /* U+03B0: GREEK SMALL LETTER UPSILON WITH DIALYTIKA AND TONOS      -> U+03C5: GREEK SMALL LETTER UPSILON                       */
            "\xCF\x8A" => "\xCE\xB9",  /* U+03CA: GREEK SMALL LETTER IOTA WITH DIALYTIKA                   -> U+03B9: GREEK SMALL LETTER IOTA                          */
            "\xCF\x8B" => "\xCF\x85",  /* U+03CB: GREEK SMALL LETTER UPSILON WITH DIALYTIKA                -> U+03C5: GREEK SMALL LETTER UPSILON                       */
            "\xCF\x8C" => "\xCE\xBF",  /* U+03CC: GREEK SMALL LETTER OMICRON WITH TONOS                    -> U+03BF: GREEK SMALL LETTER OMICRON                       */
            "\xCF\x8D" => "\xCF\x85",  /* U+03CD: GREEK SMALL LETTER UPSILON WITH TONOS                    -> U+03C5: GREEK SMALL LETTER UPSILON                       */
            "\xCF\x8E" => "\xCF\x89",  /* U+03CE: GREEK SMALL LETTER OMEGA WITH TONOS                      -> U+03C9: GREEK SMALL LETTER OMEGA                         */
            "\xCF\x90" => "\xCE\xB2",  /* U+03D0: GREEK BETA SYMBOL                                        -> U+03B2: GREEK SMALL LETTER BETA                          */
            "\xCF\x91" => "\xCE\xB8",  /* U+03D1: GREEK THETA SYMBOL                                       -> U+03B8: GREEK SMALL LETTER THETA                         */
            "\xCF\x92" => "\xCE\xA5",  /* U+03D2: GREEK UPSILON WITH HOOK SYMBOL                           -> U+03A5: GREEK CAPITAL LETTER UPSILON                     */
            "\xCF\x93" => "\xCE\xA5",  /* U+03D3: GREEK UPSILON WITH ACUTE AND HOOK SYMBOL                 -> U+03A5: GREEK CAPITAL LETTER UPSILON                     */
            "\xCF\x94" => "\xCE\xA5",  /* U+03D4: GREEK UPSILON WITH DIAERESIS AND HOOK SYMBOL             -> U+03A5: GREEK CAPITAL LETTER UPSILON                     */
            "\xCF\x95" => "\xCF\x86",  /* U+03D5: GREEK PHI SYMBOL                                         -> U+03C6: GREEK SMALL LETTER PHI                           */
            "\xCF\x96" => "\xCF\x80",  /* U+03D6: GREEK PI SYMBOL                                          -> U+03C0: GREEK SMALL LETTER PI                            */
            "\xCF\xB0" => "\xCE\xBA",  /* U+03F0: GREEK KAPPA SYMBOL                                       -> U+03BA: GREEK SMALL LETTER KAPPA                         */
            "\xCF\xB1" => "\xCF\x81",  /* U+03F1: GREEK RHO SYMBOL                                         -> U+03C1: GREEK SMALL LETTER RHO                           */
            "\xCF\xB2" => "\xCF\x82",  /* U+03F2: GREEK LUNATE SIGMA SYMBOL                                -> U+03C2: GREEK SMALL LETTER FINAL SIGMA                   */
            "\xCF\xB4" => "\xCE\x98",  /* U+03F4: GREEK CAPITAL THETA SYMBOL                               -> U+0398: GREEK CAPITAL LETTER THETA                       */
            "\xCF\xB5" => "\xCE\xB5",  /* U+03F5: GREEK LUNATE EPSILON SYMBOL                              -> U+03B5: GREEK SMALL LETTER EPSILON                       */
            "\xCF\xB9" => "\xCE\xA3",  /* U+03F9: GREEK CAPITAL LUNATE SIGMA SYMBOL                        -> U+03A3: GREEK CAPITAL LETTER SIGMA                       */
            "\xD0\x80" => "\xD0\x95",  /* U+0400: CYRILLIC CAPITAL LETTER IE WITH GRAVE                    -> U+0415: CYRILLIC CAPITAL LETTER IE                       */
            "\xD0\x81" => "\xD0\x95",  /* U+0401: CYRILLIC CAPITAL LETTER IO                               -> U+0415: CYRILLIC CAPITAL LETTER IE                       */
            "\xD0\x83" => "\xD0\x93",  /* U+0403: CYRILLIC CAPITAL LETTER GJE                              -> U+0413: CYRILLIC CAPITAL LETTER GHE                      */
            "\xD0\x87" => "\xD0\x86",  /* U+0407: CYRILLIC CAPITAL LETTER YI                               -> U+0406: CYRILLIC CAPITAL LETTER BYELORUSSIAN-UKRAINIAN I */
            "\xD0\x8C" => "\xD0\x9A",  /* U+040C: CYRILLIC CAPITAL LETTER KJE                              -> U+041A: CYRILLIC CAPITAL LETTER KA                       */
            "\xD0\x8D" => "\xD0\x98",  /* U+040D: CYRILLIC CAPITAL LETTER I WITH GRAVE                     -> U+0418: CYRILLIC CAPITAL LETTER I                        */
            "\xD0\x8E" => "\xD0\xA3",  /* U+040E: CYRILLIC CAPITAL LETTER SHORT U                          -> U+0423: CYRILLIC CAPITAL LETTER U                        */
            "\xD0\x99" => "\xD0\x98",  /* U+0419: CYRILLIC CAPITAL LETTER SHORT I                          -> U+0418: CYRILLIC CAPITAL LETTER I                        */
            "\xD0\xB9" => "\xD0\xB8",  /* U+0439: CYRILLIC SMALL LETTER SHORT I                            -> U+0438: CYRILLIC SMALL LETTER I                          */
            "\xD1\x90" => "\xD0\xB5",  /* U+0450: CYRILLIC SMALL LETTER IE WITH GRAVE                      -> U+0435: CYRILLIC SMALL LETTER IE                         */
            "\xD1\x91" => "\xD0\xB5",  /* U+0451: CYRILLIC SMALL LETTER IO                                 -> U+0435: CYRILLIC SMALL LETTER IE                         */
            "\xD1\x93" => "\xD0\xB3",  /* U+0453: CYRILLIC SMALL LETTER GJE                                -> U+0433: CYRILLIC SMALL LETTER GHE                        */
            "\xD1\x97" => "\xD1\x96",  /* U+0457: CYRILLIC SMALL LETTER YI                                 -> U+0456: CYRILLIC SMALL LETTER BYELORUSSIAN-UKRAINIAN I   */
            "\xD1\x9C" => "\xD0\xBA",  /* U+045C: CYRILLIC SMALL LETTER KJE                                -> U+043A: CYRILLIC SMALL LETTER KA                         */
            "\xD1\x9D" => "\xD0\xB8",  /* U+045D: CYRILLIC SMALL LETTER I WITH GRAVE                       -> U+0438: CYRILLIC SMALL LETTER I                          */
            "\xD1\x9E" => "\xD1\x83",  /* U+045E: CYRILLIC SMALL LETTER SHORT U                            -> U+0443: CYRILLIC SMALL LETTER U                          */
            "\xD1\xB6" => "\xD1\xB4",  /* U+0476: CYRILLIC CAPITAL LETTER IZHITSA WITH DOUBLE GRAVE ACCENT -> U+0474: CYRILLIC CAPITAL LETTER IZHITSA                  */
            "\xD1\xB7" => "\xD1\xB5",  /* U+0477: CYRILLIC SMALL LETTER IZHITSA WITH DOUBLE GRAVE ACCENT   -> U+0475: CYRILLIC SMALL LETTER IZHITSA                    */
            "\xD3\x81" => "\xD0\x96",  /* U+04C1: CYRILLIC CAPITAL LETTER ZHE WITH BREVE                   -> U+0416: CYRILLIC CAPITAL LETTER ZHE                      */
            "\xD3\x82" => "\xD0\xB6",  /* U+04C2: CYRILLIC SMALL LETTER ZHE WITH BREVE                     -> U+0436: CYRILLIC SMALL LETTER ZHE                        */
            "\xD3\x90" => "\xD0\x90",  /* U+04D0: CYRILLIC CAPITAL LETTER A WITH BREVE                     -> U+0410: CYRILLIC CAPITAL LETTER A                        */
            "\xD3\x91" => "\xD0\xB0",  /* U+04D1: CYRILLIC SMALL LETTER A WITH BREVE                       -> U+0430: CYRILLIC SMALL LETTER A                          */
            "\xD3\x92" => "\xD0\x90",  /* U+04D2: CYRILLIC CAPITAL LETTER A WITH DIAERESIS                 -> U+0410: CYRILLIC CAPITAL LETTER A                        */
            "\xD3\x93" => "\xD0\xB0",  /* U+04D3: CYRILLIC SMALL LETTER A WITH DIAERESIS                   -> U+0430: CYRILLIC SMALL LETTER A                          */
            "\xD3\x96" => "\xD0\x95",  /* U+04D6: CYRILLIC CAPITAL LETTER IE WITH BREVE                    -> U+0415: CYRILLIC CAPITAL LETTER IE                       */
            "\xD3\x97" => "\xD0\xB5",  /* U+04D7: CYRILLIC SMALL LETTER IE WITH BREVE                      -> U+0435: CYRILLIC SMALL LETTER IE                         */
            "\xD3\x9A" => "\xD3\x98",  /* U+04DA: CYRILLIC CAPITAL LETTER SCHWA WITH DIAERESIS             -> U+04D8: CYRILLIC CAPITAL LETTER SCHWA                    */
            "\xD3\x9B" => "\xD3\x99",  /* U+04DB: CYRILLIC SMALL LETTER SCHWA WITH DIAERESIS               -> U+04D9: CYRILLIC SMALL LETTER SCHWA                      */
            "\xD3\x9C" => "\xD0\x96",  /* U+04DC: CYRILLIC CAPITAL LETTER ZHE WITH DIAERESIS               -> U+0416: CYRILLIC CAPITAL LETTER ZHE                      */
            "\xD3\x9D" => "\xD0\xB6",  /* U+04DD: CYRILLIC SMALL LETTER ZHE WITH DIAERESIS                 -> U+0436: CYRILLIC SMALL LETTER ZHE                        */
            "\xD3\x9E" => "\xD0\x97",  /* U+04DE: CYRILLIC CAPITAL LETTER ZE WITH DIAERESIS                -> U+0417: CYRILLIC CAPITAL LETTER ZE                       */
            "\xD3\x9F" => "\xD0\xB7",  /* U+04DF: CYRILLIC SMALL LETTER ZE WITH DIAERESIS                  -> U+0437: CYRILLIC SMALL LETTER ZE                         */
            "\xD3\xA2" => "\xD0\x98",  /* U+04E2: CYRILLIC CAPITAL LETTER I WITH MACRON                    -> U+0418: CYRILLIC CAPITAL LETTER I                        */
            "\xD3\xA3" => "\xD0\xB8",  /* U+04E3: CYRILLIC SMALL LETTER I WITH MACRON                      -> U+0438: CYRILLIC SMALL LETTER I                          */
            "\xD3\xA4" => "\xD0\x98",  /* U+04E4: CYRILLIC CAPITAL LETTER I WITH DIAERESIS                 -> U+0418: CYRILLIC CAPITAL LETTER I                        */
            "\xD3\xA5" => "\xD0\xB8",  /* U+04E5: CYRILLIC SMALL LETTER I WITH DIAERESIS                   -> U+0438: CYRILLIC SMALL LETTER I                          */
            "\xD3\xA6" => "\xD0\x9E",  /* U+04E6: CYRILLIC CAPITAL LETTER O WITH DIAERESIS                 -> U+041E: CYRILLIC CAPITAL LETTER O                        */
            "\xD3\xA7" => "\xD0\xBE",  /* U+04E7: CYRILLIC SMALL LETTER O WITH DIAERESIS                   -> U+043E: CYRILLIC SMALL LETTER O                          */
            "\xD3\xAA" => "\xD3\xA8",  /* U+04EA: CYRILLIC CAPITAL LETTER BARRED O WITH DIAERESIS          -> U+04E8: CYRILLIC CAPITAL LETTER BARRED O                 */
            "\xD3\xAB" => "\xD3\xA9",  /* U+04EB: CYRILLIC SMALL LETTER BARRED O WITH DIAERESIS            -> U+04E9: CYRILLIC SMALL LETTER BARRED O                   */
            "\xD3\xAC" => "\xD0\xAD",  /* U+04EC: CYRILLIC CAPITAL LETTER E WITH DIAERESIS                 -> U+042D: CYRILLIC CAPITAL LETTER E                        */
            "\xD3\xAD" => "\xD1\x8D",  /* U+04ED: CYRILLIC SMALL LETTER E WITH DIAERESIS                   -> U+044D: CYRILLIC SMALL LETTER E                          */
            "\xD3\xAE" => "\xD0\xA3",  /* U+04EE: CYRILLIC CAPITAL LETTER U WITH MACRON                    -> U+0423: CYRILLIC CAPITAL LETTER U                        */
            "\xD3\xAF" => "\xD1\x83",  /* U+04EF: CYRILLIC SMALL LETTER U WITH MACRON                      -> U+0443: CYRILLIC SMALL LETTER U                          */
            "\xD3\xB0" => "\xD0\xA3",  /* U+04F0: CYRILLIC CAPITAL LETTER U WITH DIAERESIS                 -> U+0423: CYRILLIC CAPITAL LETTER U                        */
            "\xD3\xB1" => "\xD1\x83",  /* U+04F1: CYRILLIC SMALL LETTER U WITH DIAERESIS                   -> U+0443: CYRILLIC SMALL LETTER U                          */
            "\xD3\xB2" => "\xD0\xA3",  /* U+04F2: CYRILLIC CAPITAL LETTER U WITH DOUBLE ACUTE              -> U+0423: CYRILLIC CAPITAL LETTER U                        */
            "\xD3\xB3" => "\xD1\x83",  /* U+04F3: CYRILLIC SMALL LETTER U WITH DOUBLE ACUTE                -> U+0443: CYRILLIC SMALL LETTER U                          */
            "\xD3\xB4" => "\xD0\xA7",  /* U+04F4: CYRILLIC CAPITAL LETTER CHE WITH DIAERESIS               -> U+0427: CYRILLIC CAPITAL LETTER CHE                      */
            "\xD3\xB5" => "\xD1\x87",  /* U+04F5: CYRILLIC SMALL LETTER CHE WITH DIAERESIS                 -> U+0447: CYRILLIC SMALL LETTER CHE                        */
            "\xD3\xB8" => "\xD0\xAB",  /* U+04F8: CYRILLIC CAPITAL LETTER YERU WITH DIAERESIS              -> U+042B: CYRILLIC CAPITAL LETTER YERU                     */
            "\xD3\xB9" => "\xD1\x8B"   /* U+04F9: CYRILLIC SMALL LETTER YERU WITH DIAERESIS                -> U+044B: CYRILLIC SMALL LETTER YERU                       */

        ];
    }

    private function setMap_LCND()
    {
        $this->maps[self::CONVERT_TO_LCND] = [
            "\x41"     => "\x61"        ,  /* U+0041: LATIN CAPITAL LETTER A                                   -> U+0061: LATIN SMALL LETTER A                               */
            "\x42"     => "\x62"        ,  /* U+0042: LATIN CAPITAL LETTER B                                   -> U+0062: LATIN SMALL LETTER B                               */
            "\x43"     => "\x63"        ,  /* U+0043: LATIN CAPITAL LETTER C                                   -> U+0063: LATIN SMALL LETTER C                               */
            "\x44"     => "\x64"        ,  /* U+0044: LATIN CAPITAL LETTER D                                   -> U+0064: LATIN SMALL LETTER D                               */
            "\x45"     => "\x65"        ,  /* U+0045: LATIN CAPITAL LETTER E                                   -> U+0065: LATIN SMALL LETTER E                               */
            "\x46"     => "\x66"        ,  /* U+0046: LATIN CAPITAL LETTER F                                   -> U+0066: LATIN SMALL LETTER F                               */
            "\x47"     => "\x67"        ,  /* U+0047: LATIN CAPITAL LETTER G                                   -> U+0067: LATIN SMALL LETTER G                               */
            "\x48"     => "\x68"        ,  /* U+0048: LATIN CAPITAL LETTER H                                   -> U+0068: LATIN SMALL LETTER H                               */
            "\x49"     => "\x69"        ,  /* U+0049: LATIN CAPITAL LETTER I                                   -> U+0069: LATIN SMALL LETTER I                               */
            "\x4A"     => "\x6A"        ,  /* U+004A: LATIN CAPITAL LETTER J                                   -> U+006A: LATIN SMALL LETTER J                               */
            "\x4B"     => "\x6B"        ,  /* U+004B: LATIN CAPITAL LETTER K                                   -> U+006B: LATIN SMALL LETTER K                               */
            "\x4C"     => "\x6C"        ,  /* U+004C: LATIN CAPITAL LETTER L                                   -> U+006C: LATIN SMALL LETTER L                               */
            "\x4D"     => "\x6D"        ,  /* U+004D: LATIN CAPITAL LETTER M                                   -> U+006D: LATIN SMALL LETTER M                               */
            "\x4E"     => "\x6E"        ,  /* U+004E: LATIN CAPITAL LETTER N                                   -> U+006E: LATIN SMALL LETTER N                               */
            "\x4F"     => "\x6F"        ,  /* U+004F: LATIN CAPITAL LETTER O                                   -> U+006F: LATIN SMALL LETTER O                               */
            "\x50"     => "\x70"        ,  /* U+0050: LATIN CAPITAL LETTER P                                   -> U+0070: LATIN SMALL LETTER P                               */
            "\x51"     => "\x71"        ,  /* U+0051: LATIN CAPITAL LETTER Q                                   -> U+0071: LATIN SMALL LETTER Q                               */
            "\x52"     => "\x72"        ,  /* U+0052: LATIN CAPITAL LETTER R                                   -> U+0072: LATIN SMALL LETTER R                               */
            "\x53"     => "\x73"        ,  /* U+0053: LATIN CAPITAL LETTER S                                   -> U+0073: LATIN SMALL LETTER S                               */
            "\x54"     => "\x74"        ,  /* U+0054: LATIN CAPITAL LETTER T                                   -> U+0074: LATIN SMALL LETTER T                               */
            "\x55"     => "\x75"        ,  /* U+0055: LATIN CAPITAL LETTER U                                   -> U+0075: LATIN SMALL LETTER U                               */
            "\x56"     => "\x76"        ,  /* U+0056: LATIN CAPITAL LETTER V                                   -> U+0076: LATIN SMALL LETTER V                               */
            "\x57"     => "\x77"        ,  /* U+0057: LATIN CAPITAL LETTER W                                   -> U+0077: LATIN SMALL LETTER W                               */
            "\x58"     => "\x78"        ,  /* U+0058: LATIN CAPITAL LETTER X                                   -> U+0078: LATIN SMALL LETTER X                               */
            "\x59"     => "\x79"        ,  /* U+0059: LATIN CAPITAL LETTER Y                                   -> U+0079: LATIN SMALL LETTER Y                               */
            "\x5A"     => "\x7A"        ,  /* U+005A: LATIN CAPITAL LETTER Z                                   -> U+007A: LATIN SMALL LETTER Z                               */
            "\xC2\xA0" => "\x20"        ,  /* U+00A0: NO-BREAK SPACE                                           -> U+0020: SPACE                                              */
            "\xC2\xA8" => "\x20"        ,  /* U+00A8: DIAERESIS                                                -> U+0020: SPACE                                              */
            "\xC2\xAA" => "\x61"        ,  /* U+00AA: FEMININE ORDINAL INDICATOR                               -> U+0061: LATIN SMALL LETTER A                               */
            "\xC2\xAF" => "\x20"        ,  /* U+00AF: MACRON                                                   -> U+0020: SPACE                                              */
            "\xC2\xB2" => "\x32"        ,  /* U+00B2: SUPERSCRIPT TWO                                          -> U+0032: DIGIT TWO                                          */
            "\xC2\xB3" => "\x33"        ,  /* U+00B3: SUPERSCRIPT THREE                                        -> U+0033: DIGIT THREE                                        */
            "\xC2\xB4" => "\x20"        ,  /* U+00B4: ACUTE ACCENT                                             -> U+0020: SPACE                                              */
            "\xC2\xB5" => "\xCE\xBC"    ,  /* U+00B5: MICRO SIGN                                               -> U+03BC: GREEK SMALL LETTER MU                              */
            "\xC2\xB8" => "\x20"        ,  /* U+00B8: CEDILLA                                                  -> U+0020: SPACE                                              */
            "\xC2\xB9" => "\x31"        ,  /* U+00B9: SUPERSCRIPT ONE                                          -> U+0031: DIGIT ONE                                          */
            "\xC2\xBA" => "\x6F"        ,  /* U+00BA: MASCULINE ORDINAL INDICATOR                              -> U+006F: LATIN SMALL LETTER O                               */
            "\xC3\x80" => "\x61"        ,  /* U+00C0: LATIN CAPITAL LETTER A WITH GRAVE                        -> U+0061: LATIN SMALL LETTER A                               */
            "\xC3\x81" => "\x61"        ,  /* U+00C1: LATIN CAPITAL LETTER A WITH ACUTE                        -> U+0061: LATIN SMALL LETTER A                               */
            "\xC3\x82" => "\x61"        ,  /* U+00C2: LATIN CAPITAL LETTER A WITH CIRCUMFLEX                   -> U+0061: LATIN SMALL LETTER A                               */
            "\xC3\x83" => "\x61"        ,  /* U+00C3: LATIN CAPITAL LETTER A WITH TILDE                        -> U+0061: LATIN SMALL LETTER A                               */
            "\xC3\x84" => "\x61"        ,  /* U+00C4: LATIN CAPITAL LETTER A WITH DIAERESIS                    -> U+0061: LATIN SMALL LETTER A                               */
            "\xC3\x85" => "\x61"        ,  /* U+00C5: LATIN CAPITAL LETTER A WITH RING ABOVE                   -> U+0061: LATIN SMALL LETTER A                               */
            "\xC3\x86" => "\xC3\xA6"    ,  /* U+00C6: LATIN CAPITAL LETTER AE                                  -> U+00E6: LATIN SMALL LETTER AE                              */
            "\xC3\x87" => "\x63"        ,  /* U+00C7: LATIN CAPITAL LETTER C WITH CEDILLA                      -> U+0063: LATIN SMALL LETTER C                               */
            "\xC3\x88" => "\x65"        ,  /* U+00C8: LATIN CAPITAL LETTER E WITH GRAVE                        -> U+0065: LATIN SMALL LETTER E                               */
            "\xC3\x89" => "\x65"        ,  /* U+00C9: LATIN CAPITAL LETTER E WITH ACUTE                        -> U+0065: LATIN SMALL LETTER E                               */
            "\xC3\x8A" => "\x65"        ,  /* U+00CA: LATIN CAPITAL LETTER E WITH CIRCUMFLEX                   -> U+0065: LATIN SMALL LETTER E                               */
            "\xC3\x8B" => "\x65"        ,  /* U+00CB: LATIN CAPITAL LETTER E WITH DIAERESIS                    -> U+0065: LATIN SMALL LETTER E                               */
            "\xC3\x8C" => "\x69"        ,  /* U+00CC: LATIN CAPITAL LETTER I WITH GRAVE                        -> U+0069: LATIN SMALL LETTER I                               */
            "\xC3\x8D" => "\x69"        ,  /* U+00CD: LATIN CAPITAL LETTER I WITH ACUTE                        -> U+0069: LATIN SMALL LETTER I                               */
            "\xC3\x8E" => "\x69"        ,  /* U+00CE: LATIN CAPITAL LETTER I WITH CIRCUMFLEX                   -> U+0069: LATIN SMALL LETTER I                               */
            "\xC3\x8F" => "\x69"        ,  /* U+00CF: LATIN CAPITAL LETTER I WITH DIAERESIS                    -> U+0069: LATIN SMALL LETTER I                               */
            "\xC3\x90" => "\xC3\xB0"    ,  /* U+00D0: LATIN CAPITAL LETTER ETH                                 -> U+00F0: LATIN SMALL LETTER ETH                             */
            "\xC3\x91" => "\x6E"        ,  /* U+00D1: LATIN CAPITAL LETTER N WITH TILDE                        -> U+006E: LATIN SMALL LETTER N                               */
            "\xC3\x92" => "\x6F"        ,  /* U+00D2: LATIN CAPITAL LETTER O WITH GRAVE                        -> U+006F: LATIN SMALL LETTER O                               */
            "\xC3\x93" => "\x6F"        ,  /* U+00D3: LATIN CAPITAL LETTER O WITH ACUTE                        -> U+006F: LATIN SMALL LETTER O                               */
            "\xC3\x94" => "\x6F"        ,  /* U+00D4: LATIN CAPITAL LETTER O WITH CIRCUMFLEX                   -> U+006F: LATIN SMALL LETTER O                               */
            "\xC3\x95" => "\x6F"        ,  /* U+00D5: LATIN CAPITAL LETTER O WITH TILDE                        -> U+006F: LATIN SMALL LETTER O                               */
            "\xC3\x96" => "\x6F"        ,  /* U+00D6: LATIN CAPITAL LETTER O WITH DIAERESIS                    -> U+006F: LATIN SMALL LETTER O                               */
            "\xC3\x98" => "\xC3\xB8"    ,  /* U+00D8: LATIN CAPITAL LETTER O WITH STROKE                       -> U+00F8: LATIN SMALL LETTER O WITH STROKE                   */
            "\xC3\x99" => "\x75"        ,  /* U+00D9: LATIN CAPITAL LETTER U WITH GRAVE                        -> U+0075: LATIN SMALL LETTER U                               */
            "\xC3\x9A" => "\x75"        ,  /* U+00DA: LATIN CAPITAL LETTER U WITH ACUTE                        -> U+0075: LATIN SMALL LETTER U                               */
            "\xC3\x9B" => "\x75"        ,  /* U+00DB: LATIN CAPITAL LETTER U WITH CIRCUMFLEX                   -> U+0075: LATIN SMALL LETTER U                               */
            "\xC3\x9C" => "\x75"        ,  /* U+00DC: LATIN CAPITAL LETTER U WITH DIAERESIS                    -> U+0075: LATIN SMALL LETTER U                               */
            "\xC3\x9D" => "\x79"        ,  /* U+00DD: LATIN CAPITAL LETTER Y WITH ACUTE                        -> U+0079: LATIN SMALL LETTER Y                               */
            "\xC3\x9E" => "\xC3\xBE"    ,  /* U+00DE: LATIN CAPITAL LETTER THORN                               -> U+00FE: LATIN SMALL LETTER THORN                           */
            "\xC3\xA0" => "\x61"        ,  /* U+00E0: LATIN SMALL LETTER A WITH GRAVE                          -> U+0061: LATIN SMALL LETTER A                               */
            "\xC3\xA1" => "\x61"        ,  /* U+00E1: LATIN SMALL LETTER A WITH ACUTE                          -> U+0061: LATIN SMALL LETTER A                               */
            "\xC3\xA2" => "\x61"        ,  /* U+00E2: LATIN SMALL LETTER A WITH CIRCUMFLEX                     -> U+0061: LATIN SMALL LETTER A                               */
            "\xC3\xA3" => "\x61"        ,  /* U+00E3: LATIN SMALL LETTER A WITH TILDE                          -> U+0061: LATIN SMALL LETTER A                               */
            "\xC3\xA4" => "\x61"        ,  /* U+00E4: LATIN SMALL LETTER A WITH DIAERESIS                      -> U+0061: LATIN SMALL LETTER A                               */
            "\xC3\xA5" => "\x61"        ,  /* U+00E5: LATIN SMALL LETTER A WITH RING ABOVE                     -> U+0061: LATIN SMALL LETTER A                               */
            "\xC3\xA7" => "\x63"        ,  /* U+00E7: LATIN SMALL LETTER C WITH CEDILLA                        -> U+0063: LATIN SMALL LETTER C                               */
            "\xC3\xA8" => "\x65"        ,  /* U+00E8: LATIN SMALL LETTER E WITH GRAVE                          -> U+0065: LATIN SMALL LETTER E                               */
            "\xC3\xA9" => "\x65"        ,  /* U+00E9: LATIN SMALL LETTER E WITH ACUTE                          -> U+0065: LATIN SMALL LETTER E                               */
            "\xC3\xAA" => "\x65"        ,  /* U+00EA: LATIN SMALL LETTER E WITH CIRCUMFLEX                     -> U+0065: LATIN SMALL LETTER E                               */
            "\xC3\xAB" => "\x65"        ,  /* U+00EB: LATIN SMALL LETTER E WITH DIAERESIS                      -> U+0065: LATIN SMALL LETTER E                               */
            "\xC3\xAC" => "\x69"        ,  /* U+00EC: LATIN SMALL LETTER I WITH GRAVE                          -> U+0069: LATIN SMALL LETTER I                               */
            "\xC3\xAD" => "\x69"        ,  /* U+00ED: LATIN SMALL LETTER I WITH ACUTE                          -> U+0069: LATIN SMALL LETTER I                               */
            "\xC3\xAE" => "\x69"        ,  /* U+00EE: LATIN SMALL LETTER I WITH CIRCUMFLEX                     -> U+0069: LATIN SMALL LETTER I                               */
            "\xC3\xAF" => "\x69"        ,  /* U+00EF: LATIN SMALL LETTER I WITH DIAERESIS                      -> U+0069: LATIN SMALL LETTER I                               */
            "\xC3\xB1" => "\x6E"        ,  /* U+00F1: LATIN SMALL LETTER N WITH TILDE                          -> U+006E: LATIN SMALL LETTER N                               */
            "\xC3\xB2" => "\x6F"        ,  /* U+00F2: LATIN SMALL LETTER O WITH GRAVE                          -> U+006F: LATIN SMALL LETTER O                               */
            "\xC3\xB3" => "\x6F"        ,  /* U+00F3: LATIN SMALL LETTER O WITH ACUTE                          -> U+006F: LATIN SMALL LETTER O                               */
            "\xC3\xB4" => "\x6F"        ,  /* U+00F4: LATIN SMALL LETTER O WITH CIRCUMFLEX                     -> U+006F: LATIN SMALL LETTER O                               */
            "\xC3\xB5" => "\x6F"        ,  /* U+00F5: LATIN SMALL LETTER O WITH TILDE                          -> U+006F: LATIN SMALL LETTER O                               */
            "\xC3\xB6" => "\x6F"        ,  /* U+00F6: LATIN SMALL LETTER O WITH DIAERESIS                      -> U+006F: LATIN SMALL LETTER O                               */
            "\xC3\xB9" => "\x75"        ,  /* U+00F9: LATIN SMALL LETTER U WITH GRAVE                          -> U+0075: LATIN SMALL LETTER U                               */
            "\xC3\xBA" => "\x75"        ,  /* U+00FA: LATIN SMALL LETTER U WITH ACUTE                          -> U+0075: LATIN SMALL LETTER U                               */
            "\xC3\xBB" => "\x75"        ,  /* U+00FB: LATIN SMALL LETTER U WITH CIRCUMFLEX                     -> U+0075: LATIN SMALL LETTER U                               */
            "\xC3\xBC" => "\x75"        ,  /* U+00FC: LATIN SMALL LETTER U WITH DIAERESIS                      -> U+0075: LATIN SMALL LETTER U                               */
            "\xC3\xBD" => "\x79"        ,  /* U+00FD: LATIN SMALL LETTER Y WITH ACUTE                          -> U+0079: LATIN SMALL LETTER Y                               */
            "\xC3\xBF" => "\x79"        ,  /* U+00FF: LATIN SMALL LETTER Y WITH DIAERESIS                      -> U+0079: LATIN SMALL LETTER Y                               */
            "\xC4\x80" => "\x61"        ,  /* U+0100: LATIN CAPITAL LETTER A WITH MACRON                       -> U+0061: LATIN SMALL LETTER A                               */
            "\xC4\x81" => "\x61"        ,  /* U+0101: LATIN SMALL LETTER A WITH MACRON                         -> U+0061: LATIN SMALL LETTER A                               */
            "\xC4\x82" => "\x61"        ,  /* U+0102: LATIN CAPITAL LETTER A WITH BREVE                        -> U+0061: LATIN SMALL LETTER A                               */
            "\xC4\x83" => "\x61"        ,  /* U+0103: LATIN SMALL LETTER A WITH BREVE                          -> U+0061: LATIN SMALL LETTER A                               */
            "\xC4\x84" => "\x61"        ,  /* U+0104: LATIN CAPITAL LETTER A WITH OGONEK                       -> U+0061: LATIN SMALL LETTER A                               */
            "\xC4\x85" => "\x61"        ,  /* U+0105: LATIN SMALL LETTER A WITH OGONEK                         -> U+0061: LATIN SMALL LETTER A                               */
            "\xC4\x86" => "\x63"        ,  /* U+0106: LATIN CAPITAL LETTER C WITH ACUTE                        -> U+0063: LATIN SMALL LETTER C                               */
            "\xC4\x87" => "\x63"        ,  /* U+0107: LATIN SMALL LETTER C WITH ACUTE                          -> U+0063: LATIN SMALL LETTER C                               */
            "\xC4\x88" => "\x63"        ,  /* U+0108: LATIN CAPITAL LETTER C WITH CIRCUMFLEX                   -> U+0063: LATIN SMALL LETTER C                               */
            "\xC4\x89" => "\x63"        ,  /* U+0109: LATIN SMALL LETTER C WITH CIRCUMFLEX                     -> U+0063: LATIN SMALL LETTER C                               */
            "\xC4\x8A" => "\x63"        ,  /* U+010A: LATIN CAPITAL LETTER C WITH DOT ABOVE                    -> U+0063: LATIN SMALL LETTER C                               */
            "\xC4\x8B" => "\x63"        ,  /* U+010B: LATIN SMALL LETTER C WITH DOT ABOVE                      -> U+0063: LATIN SMALL LETTER C                               */
            "\xC4\x8C" => "\x63"        ,  /* U+010C: LATIN CAPITAL LETTER C WITH CARON                        -> U+0063: LATIN SMALL LETTER C                               */
            "\xC4\x8D" => "\x63"        ,  /* U+010D: LATIN SMALL LETTER C WITH CARON                          -> U+0063: LATIN SMALL LETTER C                               */
            "\xC4\x8E" => "\x64"        ,  /* U+010E: LATIN CAPITAL LETTER D WITH CARON                        -> U+0064: LATIN SMALL LETTER D                               */
            "\xC4\x8F" => "\x64"        ,  /* U+010F: LATIN SMALL LETTER D WITH CARON                          -> U+0064: LATIN SMALL LETTER D                               */
            "\xC4\x90" => "\xC4\x91"    ,  /* U+0110: LATIN CAPITAL LETTER D WITH STROKE                       -> U+0111: LATIN SMALL LETTER D WITH STROKE                   */
            "\xC4\x92" => "\x65"        ,  /* U+0112: LATIN CAPITAL LETTER E WITH MACRON                       -> U+0065: LATIN SMALL LETTER E                               */
            "\xC4\x93" => "\x65"        ,  /* U+0113: LATIN SMALL LETTER E WITH MACRON                         -> U+0065: LATIN SMALL LETTER E                               */
            "\xC4\x94" => "\x65"        ,  /* U+0114: LATIN CAPITAL LETTER E WITH BREVE                        -> U+0065: LATIN SMALL LETTER E                               */
            "\xC4\x95" => "\x65"        ,  /* U+0115: LATIN SMALL LETTER E WITH BREVE                          -> U+0065: LATIN SMALL LETTER E                               */
            "\xC4\x96" => "\x65"        ,  /* U+0116: LATIN CAPITAL LETTER E WITH DOT ABOVE                    -> U+0065: LATIN SMALL LETTER E                               */
            "\xC4\x97" => "\x65"        ,  /* U+0117: LATIN SMALL LETTER E WITH DOT ABOVE                      -> U+0065: LATIN SMALL LETTER E                               */
            "\xC4\x98" => "\x65"        ,  /* U+0118: LATIN CAPITAL LETTER E WITH OGONEK                       -> U+0065: LATIN SMALL LETTER E                               */
            "\xC4\x99" => "\x65"        ,  /* U+0119: LATIN SMALL LETTER E WITH OGONEK                         -> U+0065: LATIN SMALL LETTER E                               */
            "\xC4\x9A" => "\x65"        ,  /* U+011A: LATIN CAPITAL LETTER E WITH CARON                        -> U+0065: LATIN SMALL LETTER E                               */
            "\xC4\x9B" => "\x65"        ,  /* U+011B: LATIN SMALL LETTER E WITH CARON                          -> U+0065: LATIN SMALL LETTER E                               */
            "\xC4\x9C" => "\x67"        ,  /* U+011C: LATIN CAPITAL LETTER G WITH CIRCUMFLEX                   -> U+0067: LATIN SMALL LETTER G                               */
            "\xC4\x9D" => "\x67"        ,  /* U+011D: LATIN SMALL LETTER G WITH CIRCUMFLEX                     -> U+0067: LATIN SMALL LETTER G                               */
            "\xC4\x9E" => "\x67"        ,  /* U+011E: LATIN CAPITAL LETTER G WITH BREVE                        -> U+0067: LATIN SMALL LETTER G                               */
            "\xC4\x9F" => "\x67"        ,  /* U+011F: LATIN SMALL LETTER G WITH BREVE                          -> U+0067: LATIN SMALL LETTER G                               */
            "\xC4\xA0" => "\x67"        ,  /* U+0120: LATIN CAPITAL LETTER G WITH DOT ABOVE                    -> U+0067: LATIN SMALL LETTER G                               */
            "\xC4\xA1" => "\x67"        ,  /* U+0121: LATIN SMALL LETTER G WITH DOT ABOVE                      -> U+0067: LATIN SMALL LETTER G                               */
            "\xC4\xA2" => "\x67"        ,  /* U+0122: LATIN CAPITAL LETTER G WITH CEDILLA                      -> U+0067: LATIN SMALL LETTER G                               */
            "\xC4\xA3" => "\x67"        ,  /* U+0123: LATIN SMALL LETTER G WITH CEDILLA                        -> U+0067: LATIN SMALL LETTER G                               */
            "\xC4\xA4" => "\x68"        ,  /* U+0124: LATIN CAPITAL LETTER H WITH CIRCUMFLEX                   -> U+0068: LATIN SMALL LETTER H                               */
            "\xC4\xA5" => "\x68"        ,  /* U+0125: LATIN SMALL LETTER H WITH CIRCUMFLEX                     -> U+0068: LATIN SMALL LETTER H                               */
            "\xC4\xA6" => "\xC4\xA7"    ,  /* U+0126: LATIN CAPITAL LETTER H WITH STROKE                       -> U+0127: LATIN SMALL LETTER H WITH STROKE                   */
            "\xC4\xA8" => "\x69"        ,  /* U+0128: LATIN CAPITAL LETTER I WITH TILDE                        -> U+0069: LATIN SMALL LETTER I                               */
            "\xC4\xA9" => "\x69"        ,  /* U+0129: LATIN SMALL LETTER I WITH TILDE                          -> U+0069: LATIN SMALL LETTER I                               */
            "\xC4\xAA" => "\x69"        ,  /* U+012A: LATIN CAPITAL LETTER I WITH MACRON                       -> U+0069: LATIN SMALL LETTER I                               */
            "\xC4\xAB" => "\x69"        ,  /* U+012B: LATIN SMALL LETTER I WITH MACRON                         -> U+0069: LATIN SMALL LETTER I                               */
            "\xC4\xAC" => "\x69"        ,  /* U+012C: LATIN CAPITAL LETTER I WITH BREVE                        -> U+0069: LATIN SMALL LETTER I                               */
            "\xC4\xAD" => "\x69"        ,  /* U+012D: LATIN SMALL LETTER I WITH BREVE                          -> U+0069: LATIN SMALL LETTER I                               */
            "\xC4\xAE" => "\x69"        ,  /* U+012E: LATIN CAPITAL LETTER I WITH OGONEK                       -> U+0069: LATIN SMALL LETTER I                               */
            "\xC4\xAF" => "\x69"        ,  /* U+012F: LATIN SMALL LETTER I WITH OGONEK                         -> U+0069: LATIN SMALL LETTER I                               */
            "\xC4\xB0" => "\x69"        ,  /* U+0130: LATIN CAPITAL LETTER I WITH DOT ABOVE                    -> U+0069: LATIN SMALL LETTER I                               */
            "\xC4\xB2" => "\xC4\xB3"    ,  /* U+0132: LATIN CAPITAL LIGATURE IJ                                -> U+0133: LATIN SMALL LIGATURE IJ                            */
            "\xC4\xB4" => "\x6A"        ,  /* U+0134: LATIN CAPITAL LETTER J WITH CIRCUMFLEX                   -> U+006A: LATIN SMALL LETTER J                               */
            "\xC4\xB5" => "\x6A"        ,  /* U+0135: LATIN SMALL LETTER J WITH CIRCUMFLEX                     -> U+006A: LATIN SMALL LETTER J                               */
            "\xC4\xB6" => "\x6B"        ,  /* U+0136: LATIN CAPITAL LETTER K WITH CEDILLA                      -> U+006B: LATIN SMALL LETTER K                               */
            "\xC4\xB7" => "\x6B"        ,  /* U+0137: LATIN SMALL LETTER K WITH CEDILLA                        -> U+006B: LATIN SMALL LETTER K                               */
            "\xC4\xB9" => "\x6C"        ,  /* U+0139: LATIN CAPITAL LETTER L WITH ACUTE                        -> U+006C: LATIN SMALL LETTER L                               */
            "\xC4\xBA" => "\x6C"        ,  /* U+013A: LATIN SMALL LETTER L WITH ACUTE                          -> U+006C: LATIN SMALL LETTER L                               */
            "\xC4\xBB" => "\x6C"        ,  /* U+013B: LATIN CAPITAL LETTER L WITH CEDILLA                      -> U+006C: LATIN SMALL LETTER L                               */
            "\xC4\xBC" => "\x6C"        ,  /* U+013C: LATIN SMALL LETTER L WITH CEDILLA                        -> U+006C: LATIN SMALL LETTER L                               */
            "\xC4\xBD" => "\x6C"        ,  /* U+013D: LATIN CAPITAL LETTER L WITH CARON                        -> U+006C: LATIN SMALL LETTER L                               */
            "\xC4\xBE" => "\x6C"        ,  /* U+013E: LATIN SMALL LETTER L WITH CARON                          -> U+006C: LATIN SMALL LETTER L                               */
            "\xC4\xBF" => "\xC5\x80"    ,  /* U+013F: LATIN CAPITAL LETTER L WITH MIDDLE DOT                   -> U+0140: LATIN SMALL LETTER L WITH MIDDLE DOT               */
            "\xC5\x81" => "\xC5\x82"    ,  /* U+0141: LATIN CAPITAL LETTER L WITH STROKE                       -> U+0142: LATIN SMALL LETTER L WITH STROKE                   */
            "\xC5\x83" => "\x6E"        ,  /* U+0143: LATIN CAPITAL LETTER N WITH ACUTE                        -> U+006E: LATIN SMALL LETTER N                               */
            "\xC5\x84" => "\x6E"        ,  /* U+0144: LATIN SMALL LETTER N WITH ACUTE                          -> U+006E: LATIN SMALL LETTER N                               */
            "\xC5\x85" => "\x6E"        ,  /* U+0145: LATIN CAPITAL LETTER N WITH CEDILLA                      -> U+006E: LATIN SMALL LETTER N                               */
            "\xC5\x86" => "\x6E"        ,  /* U+0146: LATIN SMALL LETTER N WITH CEDILLA                        -> U+006E: LATIN SMALL LETTER N                               */
            "\xC5\x87" => "\x6E"        ,  /* U+0147: LATIN CAPITAL LETTER N WITH CARON                        -> U+006E: LATIN SMALL LETTER N                               */
            "\xC5\x88" => "\x6E"        ,  /* U+0148: LATIN SMALL LETTER N WITH CARON                          -> U+006E: LATIN SMALL LETTER N                               */
            "\xC5\x8A" => "\xC5\x8B"    ,  /* U+014A: LATIN CAPITAL LETTER ENG                                 -> U+014B: LATIN SMALL LETTER ENG                             */
            "\xC5\x8C" => "\x6F"        ,  /* U+014C: LATIN CAPITAL LETTER O WITH MACRON                       -> U+006F: LATIN SMALL LETTER O                               */
            "\xC5\x8D" => "\x6F"        ,  /* U+014D: LATIN SMALL LETTER O WITH MACRON                         -> U+006F: LATIN SMALL LETTER O                               */
            "\xC5\x8E" => "\x6F"        ,  /* U+014E: LATIN CAPITAL LETTER O WITH BREVE                        -> U+006F: LATIN SMALL LETTER O                               */
            "\xC5\x8F" => "\x6F"        ,  /* U+014F: LATIN SMALL LETTER O WITH BREVE                          -> U+006F: LATIN SMALL LETTER O                               */
            "\xC5\x90" => "\x6F"        ,  /* U+0150: LATIN CAPITAL LETTER O WITH DOUBLE ACUTE                 -> U+006F: LATIN SMALL LETTER O                               */
            "\xC5\x91" => "\x6F"        ,  /* U+0151: LATIN SMALL LETTER O WITH DOUBLE ACUTE                   -> U+006F: LATIN SMALL LETTER O                               */
            "\xC5\x92" => "\xC5\x93"    ,  /* U+0152: LATIN CAPITAL LIGATURE OE                                -> U+0153: LATIN SMALL LIGATURE OE                            */
            "\xC5\x94" => "\x72"        ,  /* U+0154: LATIN CAPITAL LETTER R WITH ACUTE                        -> U+0072: LATIN SMALL LETTER R                               */
            "\xC5\x95" => "\x72"        ,  /* U+0155: LATIN SMALL LETTER R WITH ACUTE                          -> U+0072: LATIN SMALL LETTER R                               */
            "\xC5\x96" => "\x72"        ,  /* U+0156: LATIN CAPITAL LETTER R WITH CEDILLA                      -> U+0072: LATIN SMALL LETTER R                               */
            "\xC5\x97" => "\x72"        ,  /* U+0157: LATIN SMALL LETTER R WITH CEDILLA                        -> U+0072: LATIN SMALL LETTER R                               */
            "\xC5\x98" => "\x72"        ,  /* U+0158: LATIN CAPITAL LETTER R WITH CARON                        -> U+0072: LATIN SMALL LETTER R                               */
            "\xC5\x99" => "\x72"        ,  /* U+0159: LATIN SMALL LETTER R WITH CARON                          -> U+0072: LATIN SMALL LETTER R                               */
            "\xC5\x9A" => "\x73"        ,  /* U+015A: LATIN CAPITAL LETTER S WITH ACUTE                        -> U+0073: LATIN SMALL LETTER S                               */
            "\xC5\x9B" => "\x73"        ,  /* U+015B: LATIN SMALL LETTER S WITH ACUTE                          -> U+0073: LATIN SMALL LETTER S                               */
            "\xC5\x9C" => "\x73"        ,  /* U+015C: LATIN CAPITAL LETTER S WITH CIRCUMFLEX                   -> U+0073: LATIN SMALL LETTER S                               */
            "\xC5\x9D" => "\x73"        ,  /* U+015D: LATIN SMALL LETTER S WITH CIRCUMFLEX                     -> U+0073: LATIN SMALL LETTER S                               */
            "\xC5\x9E" => "\x73"        ,  /* U+015E: LATIN CAPITAL LETTER S WITH CEDILLA                      -> U+0073: LATIN SMALL LETTER S                               */
            "\xC5\x9F" => "\x73"        ,  /* U+015F: LATIN SMALL LETTER S WITH CEDILLA                        -> U+0073: LATIN SMALL LETTER S                               */
            "\xC5\xA0" => "\x73"        ,  /* U+0160: LATIN CAPITAL LETTER S WITH CARON                        -> U+0073: LATIN SMALL LETTER S                               */
            "\xC5\xA1" => "\x73"        ,  /* U+0161: LATIN SMALL LETTER S WITH CARON                          -> U+0073: LATIN SMALL LETTER S                               */
            "\xC5\xA2" => "\x74"        ,  /* U+0162: LATIN CAPITAL LETTER T WITH CEDILLA                      -> U+0074: LATIN SMALL LETTER T                               */
            "\xC5\xA3" => "\x74"        ,  /* U+0163: LATIN SMALL LETTER T WITH CEDILLA                        -> U+0074: LATIN SMALL LETTER T                               */
            "\xC5\xA4" => "\x74"        ,  /* U+0164: LATIN CAPITAL LETTER T WITH CARON                        -> U+0074: LATIN SMALL LETTER T                               */
            "\xC5\xA5" => "\x74"        ,  /* U+0165: LATIN SMALL LETTER T WITH CARON                          -> U+0074: LATIN SMALL LETTER T                               */
            "\xC5\xA6" => "\xC5\xA7"    ,  /* U+0166: LATIN CAPITAL LETTER T WITH STROKE                       -> U+0167: LATIN SMALL LETTER T WITH STROKE                   */
            "\xC5\xA8" => "\x75"        ,  /* U+0168: LATIN CAPITAL LETTER U WITH TILDE                        -> U+0075: LATIN SMALL LETTER U                               */
            "\xC5\xA9" => "\x75"        ,  /* U+0169: LATIN SMALL LETTER U WITH TILDE                          -> U+0075: LATIN SMALL LETTER U                               */
            "\xC5\xAA" => "\x75"        ,  /* U+016A: LATIN CAPITAL LETTER U WITH MACRON                       -> U+0075: LATIN SMALL LETTER U                               */
            "\xC5\xAB" => "\x75"        ,  /* U+016B: LATIN SMALL LETTER U WITH MACRON                         -> U+0075: LATIN SMALL LETTER U                               */
            "\xC5\xAC" => "\x75"        ,  /* U+016C: LATIN CAPITAL LETTER U WITH BREVE                        -> U+0075: LATIN SMALL LETTER U                               */
            "\xC5\xAD" => "\x75"        ,  /* U+016D: LATIN SMALL LETTER U WITH BREVE                          -> U+0075: LATIN SMALL LETTER U                               */
            "\xC5\xAE" => "\x75"        ,  /* U+016E: LATIN CAPITAL LETTER U WITH RING ABOVE                   -> U+0075: LATIN SMALL LETTER U                               */
            "\xC5\xAF" => "\x75"        ,  /* U+016F: LATIN SMALL LETTER U WITH RING ABOVE                     -> U+0075: LATIN SMALL LETTER U                               */
            "\xC5\xB0" => "\x75"        ,  /* U+0170: LATIN CAPITAL LETTER U WITH DOUBLE ACUTE                 -> U+0075: LATIN SMALL LETTER U                               */
            "\xC5\xB1" => "\x75"        ,  /* U+0171: LATIN SMALL LETTER U WITH DOUBLE ACUTE                   -> U+0075: LATIN SMALL LETTER U                               */
            "\xC5\xB2" => "\x75"        ,  /* U+0172: LATIN CAPITAL LETTER U WITH OGONEK                       -> U+0075: LATIN SMALL LETTER U                               */
            "\xC5\xB3" => "\x75"        ,  /* U+0173: LATIN SMALL LETTER U WITH OGONEK                         -> U+0075: LATIN SMALL LETTER U                               */
            "\xC5\xB4" => "\x77"        ,  /* U+0174: LATIN CAPITAL LETTER W WITH CIRCUMFLEX                   -> U+0077: LATIN SMALL LETTER W                               */
            "\xC5\xB5" => "\x77"        ,  /* U+0175: LATIN SMALL LETTER W WITH CIRCUMFLEX                     -> U+0077: LATIN SMALL LETTER W                               */
            "\xC5\xB6" => "\x79"        ,  /* U+0176: LATIN CAPITAL LETTER Y WITH CIRCUMFLEX                   -> U+0079: LATIN SMALL LETTER Y                               */
            "\xC5\xB7" => "\x79"        ,  /* U+0177: LATIN SMALL LETTER Y WITH CIRCUMFLEX                     -> U+0079: LATIN SMALL LETTER Y                               */
            "\xC5\xB8" => "\x79"        ,  /* U+0178: LATIN CAPITAL LETTER Y WITH DIAERESIS                    -> U+0079: LATIN SMALL LETTER Y                               */
            "\xC5\xB9" => "\x7A"        ,  /* U+0179: LATIN CAPITAL LETTER Z WITH ACUTE                        -> U+007A: LATIN SMALL LETTER Z                               */
            "\xC5\xBA" => "\x7A"        ,  /* U+017A: LATIN SMALL LETTER Z WITH ACUTE                          -> U+007A: LATIN SMALL LETTER Z                               */
            "\xC5\xBB" => "\x7A"        ,  /* U+017B: LATIN CAPITAL LETTER Z WITH DOT ABOVE                    -> U+007A: LATIN SMALL LETTER Z                               */
            "\xC5\xBC" => "\x7A"        ,  /* U+017C: LATIN SMALL LETTER Z WITH DOT ABOVE                      -> U+007A: LATIN SMALL LETTER Z                               */
            "\xC5\xBD" => "\x7A"        ,  /* U+017D: LATIN CAPITAL LETTER Z WITH CARON                        -> U+007A: LATIN SMALL LETTER Z                               */
            "\xC5\xBE" => "\x7A"        ,  /* U+017E: LATIN SMALL LETTER Z WITH CARON                          -> U+007A: LATIN SMALL LETTER Z                               */
            "\xC5\xBF" => "\x73"        ,  /* U+017F: LATIN SMALL LETTER LONG S                                -> U+0073: LATIN SMALL LETTER S                               */
            "\xC6\x81" => "\xC9\x93"    ,  /* U+0181: LATIN CAPITAL LETTER B WITH HOOK                         -> U+0253: LATIN SMALL LETTER B WITH HOOK                     */
            "\xC6\x82" => "\xC6\x83"    ,  /* U+0182: LATIN CAPITAL LETTER B WITH TOPBAR                       -> U+0183: LATIN SMALL LETTER B WITH TOPBAR                   */
            "\xC6\x84" => "\xC6\x85"    ,  /* U+0184: LATIN CAPITAL LETTER TONE SIX                            -> U+0185: LATIN SMALL LETTER TONE SIX                        */
            "\xC6\x86" => "\xC9\x94"    ,  /* U+0186: LATIN CAPITAL LETTER OPEN O                              -> U+0254: LATIN SMALL LETTER OPEN O                          */
            "\xC6\x87" => "\xC6\x88"    ,  /* U+0187: LATIN CAPITAL LETTER C WITH HOOK                         -> U+0188: LATIN SMALL LETTER C WITH HOOK                     */
            "\xC6\x89" => "\xC9\x96"    ,  /* U+0189: LATIN CAPITAL LETTER AFRICAN D                           -> U+0256: LATIN SMALL LETTER D WITH TAIL                     */
            "\xC6\x8A" => "\xC9\x97"    ,  /* U+018A: LATIN CAPITAL LETTER D WITH HOOK                         -> U+0257: LATIN SMALL LETTER D WITH HOOK                     */
            "\xC6\x8B" => "\xC6\x8C"    ,  /* U+018B: LATIN CAPITAL LETTER D WITH TOPBAR                       -> U+018C: LATIN SMALL LETTER D WITH TOPBAR                   */
            "\xC6\x8E" => "\xC7\x9D"    ,  /* U+018E: LATIN CAPITAL LETTER REVERSED E                          -> U+01DD: LATIN SMALL LETTER TURNED E                        */
            "\xC6\x8F" => "\xC9\x99"    ,  /* U+018F: LATIN CAPITAL LETTER SCHWA                               -> U+0259: LATIN SMALL LETTER SCHWA                           */
            "\xC6\x90" => "\xC9\x9B"    ,  /* U+0190: LATIN CAPITAL LETTER OPEN E                              -> U+025B: LATIN SMALL LETTER OPEN E                          */
            "\xC6\x91" => "\xC6\x92"    ,  /* U+0191: LATIN CAPITAL LETTER F WITH HOOK                         -> U+0192: LATIN SMALL LETTER F WITH HOOK                     */
            "\xC6\x93" => "\xC9\xA0"    ,  /* U+0193: LATIN CAPITAL LETTER G WITH HOOK                         -> U+0260: LATIN SMALL LETTER G WITH HOOK                     */
            "\xC6\x94" => "\xC9\xA3"    ,  /* U+0194: LATIN CAPITAL LETTER GAMMA                               -> U+0263: LATIN SMALL LETTER GAMMA                           */
            "\xC6\x96" => "\xC9\xA9"    ,  /* U+0196: LATIN CAPITAL LETTER IOTA                                -> U+0269: LATIN SMALL LETTER IOTA                            */
            "\xC6\x97" => "\xC9\xA8"    ,  /* U+0197: LATIN CAPITAL LETTER I WITH STROKE                       -> U+0268: LATIN SMALL LETTER I WITH STROKE                   */
            "\xC6\x98" => "\xC6\x99"    ,  /* U+0198: LATIN CAPITAL LETTER K WITH HOOK                         -> U+0199: LATIN SMALL LETTER K WITH HOOK                     */
            "\xC6\x9C" => "\xC9\xAF"    ,  /* U+019C: LATIN CAPITAL LETTER TURNED M                            -> U+026F: LATIN SMALL LETTER TURNED M                        */
            "\xC6\x9D" => "\xC9\xB2"    ,  /* U+019D: LATIN CAPITAL LETTER N WITH LEFT HOOK                    -> U+0272: LATIN SMALL LETTER N WITH LEFT HOOK                */
            "\xC6\x9F" => "\xC9\xB5"    ,  /* U+019F: LATIN CAPITAL LETTER O WITH MIDDLE TILDE                 -> U+0275: LATIN SMALL LETTER BARRED O                        */
            "\xC6\xA0" => "\x6F"        ,  /* U+01A0: LATIN CAPITAL LETTER O WITH HORN                         -> U+006F: LATIN SMALL LETTER O                               */
            "\xC6\xA1" => "\x6F"        ,  /* U+01A1: LATIN SMALL LETTER O WITH HORN                           -> U+006F: LATIN SMALL LETTER O                               */
            "\xC6\xA2" => "\xC6\xA3"    ,  /* U+01A2: LATIN CAPITAL LETTER OI                                  -> U+01A3: LATIN SMALL LETTER OI                              */
            "\xC6\xA4" => "\xC6\xA5"    ,  /* U+01A4: LATIN CAPITAL LETTER P WITH HOOK                         -> U+01A5: LATIN SMALL LETTER P WITH HOOK                     */
            "\xC6\xA6" => "\xCA\x80"    ,  /* U+01A6: LATIN LETTER YR                                          -> U+0280: LATIN LETTER SMALL CAPITAL R                       */
            "\xC6\xA7" => "\xC6\xA8"    ,  /* U+01A7: LATIN CAPITAL LETTER TONE TWO                            -> U+01A8: LATIN SMALL LETTER TONE TWO                        */
            "\xC6\xA9" => "\xCA\x83"    ,  /* U+01A9: LATIN CAPITAL LETTER ESH                                 -> U+0283: LATIN SMALL LETTER ESH                             */
            "\xC6\xAC" => "\xC6\xAD"    ,  /* U+01AC: LATIN CAPITAL LETTER T WITH HOOK                         -> U+01AD: LATIN SMALL LETTER T WITH HOOK                     */
            "\xC6\xAE" => "\xCA\x88"    ,  /* U+01AE: LATIN CAPITAL LETTER T WITH RETROFLEX HOOK               -> U+0288: LATIN SMALL LETTER T WITH RETROFLEX HOOK           */
            "\xC6\xAF" => "\x75"        ,  /* U+01AF: LATIN CAPITAL LETTER U WITH HORN                         -> U+0075: LATIN SMALL LETTER U                               */
            "\xC6\xB0" => "\x75"        ,  /* U+01B0: LATIN SMALL LETTER U WITH HORN                           -> U+0075: LATIN SMALL LETTER U                               */
            "\xC6\xB1" => "\xCA\x8A"    ,  /* U+01B1: LATIN CAPITAL LETTER UPSILON                             -> U+028A: LATIN SMALL LETTER UPSILON                         */
            "\xC6\xB2" => "\xCA\x8B"    ,  /* U+01B2: LATIN CAPITAL LETTER V WITH HOOK                         -> U+028B: LATIN SMALL LETTER V WITH HOOK                     */
            "\xC6\xB3" => "\xC6\xB4"    ,  /* U+01B3: LATIN CAPITAL LETTER Y WITH HOOK                         -> U+01B4: LATIN SMALL LETTER Y WITH HOOK                     */
            "\xC6\xB5" => "\xC6\xB6"    ,  /* U+01B5: LATIN CAPITAL LETTER Z WITH STROKE                       -> U+01B6: LATIN SMALL LETTER Z WITH STROKE                   */
            "\xC6\xB7" => "\xCA\x92"    ,  /* U+01B7: LATIN CAPITAL LETTER EZH                                 -> U+0292: LATIN SMALL LETTER EZH                             */
            "\xC6\xB8" => "\xC6\xB9"    ,  /* U+01B8: LATIN CAPITAL LETTER EZH REVERSED                        -> U+01B9: LATIN SMALL LETTER EZH REVERSED                    */
            "\xC6\xBC" => "\xC6\xBD"    ,  /* U+01BC: LATIN CAPITAL LETTER TONE FIVE                           -> U+01BD: LATIN SMALL LETTER TONE FIVE                       */
            "\xC7\x84" => "\xC7\x86"    ,  /* U+01C4: LATIN CAPITAL LETTER DZ WITH CARON                       -> U+01C6: LATIN SMALL LETTER DZ WITH CARON                   */
            "\xC7\x85" => "\xC7\x86"    ,  /* U+01C5: LATIN CAPITAL LETTER D WITH SMALL LETTER Z WITH CARON    -> U+01C6: LATIN SMALL LETTER DZ WITH CARON                   */
            "\xC7\x87" => "\xC7\x89"    ,  /* U+01C7: LATIN CAPITAL LETTER LJ                                  -> U+01C9: LATIN SMALL LETTER LJ                              */
            "\xC7\x88" => "\xC7\x89"    ,  /* U+01C8: LATIN CAPITAL LETTER L WITH SMALL LETTER J               -> U+01C9: LATIN SMALL LETTER LJ                              */
            "\xC7\x8A" => "\xC7\x8C"    ,  /* U+01CA: LATIN CAPITAL LETTER NJ                                  -> U+01CC: LATIN SMALL LETTER NJ                              */
            "\xC7\x8B" => "\xC7\x8C"    ,  /* U+01CB: LATIN CAPITAL LETTER N WITH SMALL LETTER J               -> U+01CC: LATIN SMALL LETTER NJ                              */
            "\xC7\x8D" => "\x61"        ,  /* U+01CD: LATIN CAPITAL LETTER A WITH CARON                        -> U+0061: LATIN SMALL LETTER A                               */
            "\xC7\x8E" => "\x61"        ,  /* U+01CE: LATIN SMALL LETTER A WITH CARON                          -> U+0061: LATIN SMALL LETTER A                               */
            "\xC7\x8F" => "\x69"        ,  /* U+01CF: LATIN CAPITAL LETTER I WITH CARON                        -> U+0069: LATIN SMALL LETTER I                               */
            "\xC7\x90" => "\x69"        ,  /* U+01D0: LATIN SMALL LETTER I WITH CARON                          -> U+0069: LATIN SMALL LETTER I                               */
            "\xC7\x91" => "\x6F"        ,  /* U+01D1: LATIN CAPITAL LETTER O WITH CARON                        -> U+006F: LATIN SMALL LETTER O                               */
            "\xC7\x92" => "\x6F"        ,  /* U+01D2: LATIN SMALL LETTER O WITH CARON                          -> U+006F: LATIN SMALL LETTER O                               */
            "\xC7\x93" => "\x75"        ,  /* U+01D3: LATIN CAPITAL LETTER U WITH CARON                        -> U+0075: LATIN SMALL LETTER U                               */
            "\xC7\x94" => "\x75"        ,  /* U+01D4: LATIN SMALL LETTER U WITH CARON                          -> U+0075: LATIN SMALL LETTER U                               */
            "\xC7\x95" => "\x75"        ,  /* U+01D5: LATIN CAPITAL LETTER U WITH DIAERESIS AND MACRON         -> U+0075: LATIN SMALL LETTER U                               */
            "\xC7\x96" => "\x75"        ,  /* U+01D6: LATIN SMALL LETTER U WITH DIAERESIS AND MACRON           -> U+0075: LATIN SMALL LETTER U                               */
            "\xC7\x97" => "\x75"        ,  /* U+01D7: LATIN CAPITAL LETTER U WITH DIAERESIS AND ACUTE          -> U+0075: LATIN SMALL LETTER U                               */
            "\xC7\x98" => "\x75"        ,  /* U+01D8: LATIN SMALL LETTER U WITH DIAERESIS AND ACUTE            -> U+0075: LATIN SMALL LETTER U                               */
            "\xC7\x99" => "\x75"        ,  /* U+01D9: LATIN CAPITAL LETTER U WITH DIAERESIS AND CARON          -> U+0075: LATIN SMALL LETTER U                               */
            "\xC7\x9A" => "\x75"        ,  /* U+01DA: LATIN SMALL LETTER U WITH DIAERESIS AND CARON            -> U+0075: LATIN SMALL LETTER U                               */
            "\xC7\x9B" => "\x75"        ,  /* U+01DB: LATIN CAPITAL LETTER U WITH DIAERESIS AND GRAVE          -> U+0075: LATIN SMALL LETTER U                               */
            "\xC7\x9C" => "\x75"        ,  /* U+01DC: LATIN SMALL LETTER U WITH DIAERESIS AND GRAVE            -> U+0075: LATIN SMALL LETTER U                               */
            "\xC7\x9E" => "\x61"        ,  /* U+01DE: LATIN CAPITAL LETTER A WITH DIAERESIS AND MACRON         -> U+0061: LATIN SMALL LETTER A                               */
            "\xC7\x9F" => "\x61"        ,  /* U+01DF: LATIN SMALL LETTER A WITH DIAERESIS AND MACRON           -> U+0061: LATIN SMALL LETTER A                               */
            "\xC7\xA0" => "\x61"        ,  /* U+01E0: LATIN CAPITAL LETTER A WITH DOT ABOVE AND MACRON         -> U+0061: LATIN SMALL LETTER A                               */
            "\xC7\xA1" => "\x61"        ,  /* U+01E1: LATIN SMALL LETTER A WITH DOT ABOVE AND MACRON           -> U+0061: LATIN SMALL LETTER A                               */
            "\xC7\xA2" => "\xC3\xA6"    ,  /* U+01E2: LATIN CAPITAL LETTER AE WITH MACRON                      -> U+00E6: LATIN SMALL LETTER AE                              */
            "\xC7\xA3" => "\xC3\xA6"    ,  /* U+01E3: LATIN SMALL LETTER AE WITH MACRON                        -> U+00E6: LATIN SMALL LETTER AE                              */
            "\xC7\xA4" => "\xC7\xA5"    ,  /* U+01E4: LATIN CAPITAL LETTER G WITH STROKE                       -> U+01E5: LATIN SMALL LETTER G WITH STROKE                   */
            "\xC7\xA6" => "\x67"        ,  /* U+01E6: LATIN CAPITAL LETTER G WITH CARON                        -> U+0067: LATIN SMALL LETTER G                               */
            "\xC7\xA7" => "\x67"        ,  /* U+01E7: LATIN SMALL LETTER G WITH CARON                          -> U+0067: LATIN SMALL LETTER G                               */
            "\xC7\xA8" => "\x6B"        ,  /* U+01E8: LATIN CAPITAL LETTER K WITH CARON                        -> U+006B: LATIN SMALL LETTER K                               */
            "\xC7\xA9" => "\x6B"        ,  /* U+01E9: LATIN SMALL LETTER K WITH CARON                          -> U+006B: LATIN SMALL LETTER K                               */
            "\xC7\xAA" => "\x6F"        ,  /* U+01EA: LATIN CAPITAL LETTER O WITH OGONEK                       -> U+006F: LATIN SMALL LETTER O                               */
            "\xC7\xAB" => "\x6F"        ,  /* U+01EB: LATIN SMALL LETTER O WITH OGONEK                         -> U+006F: LATIN SMALL LETTER O                               */
            "\xC7\xAC" => "\x6F"        ,  /* U+01EC: LATIN CAPITAL LETTER O WITH OGONEK AND MACRON            -> U+006F: LATIN SMALL LETTER O                               */
            "\xC7\xAD" => "\x6F"        ,  /* U+01ED: LATIN SMALL LETTER O WITH OGONEK AND MACRON              -> U+006F: LATIN SMALL LETTER O                               */
            "\xC7\xAE" => "\xCA\x92"    ,  /* U+01EE: LATIN CAPITAL LETTER EZH WITH CARON                      -> U+0292: LATIN SMALL LETTER EZH                             */
            "\xC7\xAF" => "\xCA\x92"    ,  /* U+01EF: LATIN SMALL LETTER EZH WITH CARON                        -> U+0292: LATIN SMALL LETTER EZH                             */
            "\xC7\xB0" => "\x6A"        ,  /* U+01F0: LATIN SMALL LETTER J WITH CARON                          -> U+006A: LATIN SMALL LETTER J                               */
            "\xC7\xB1" => "\xC7\xB3"    ,  /* U+01F1: LATIN CAPITAL LETTER DZ                                  -> U+01F3: LATIN SMALL LETTER DZ                              */
            "\xC7\xB2" => "\xC7\xB3"    ,  /* U+01F2: LATIN CAPITAL LETTER D WITH SMALL LETTER Z               -> U+01F3: LATIN SMALL LETTER DZ                              */
            "\xC7\xB4" => "\x67"        ,  /* U+01F4: LATIN CAPITAL LETTER G WITH ACUTE                        -> U+0067: LATIN SMALL LETTER G                               */
            "\xC7\xB5" => "\x67"        ,  /* U+01F5: LATIN SMALL LETTER G WITH ACUTE                          -> U+0067: LATIN SMALL LETTER G                               */
            "\xC7\xB6" => "\xC6\x95"    ,  /* U+01F6: LATIN CAPITAL LETTER HWAIR                               -> U+0195: LATIN SMALL LETTER HV                              */
            "\xC7\xB7" => "\xC6\xBF"    ,  /* U+01F7: LATIN CAPITAL LETTER WYNN                                -> U+01BF: LATIN LETTER WYNN                                  */
            "\xC7\xB8" => "\x6E"        ,  /* U+01F8: LATIN CAPITAL LETTER N WITH GRAVE                        -> U+006E: LATIN SMALL LETTER N                               */
            "\xC7\xB9" => "\x6E"        ,  /* U+01F9: LATIN SMALL LETTER N WITH GRAVE                          -> U+006E: LATIN SMALL LETTER N                               */
            "\xC7\xBA" => "\x61"        ,  /* U+01FA: LATIN CAPITAL LETTER A WITH RING ABOVE AND ACUTE         -> U+0061: LATIN SMALL LETTER A                               */
            "\xC7\xBB" => "\x61"        ,  /* U+01FB: LATIN SMALL LETTER A WITH RING ABOVE AND ACUTE           -> U+0061: LATIN SMALL LETTER A                               */
            "\xC7\xBC" => "\xC3\xA6"    ,  /* U+01FC: LATIN CAPITAL LETTER AE WITH ACUTE                       -> U+00E6: LATIN SMALL LETTER AE                              */
            "\xC7\xBD" => "\xC3\xA6"    ,  /* U+01FD: LATIN SMALL LETTER AE WITH ACUTE                         -> U+00E6: LATIN SMALL LETTER AE                              */
            "\xC7\xBE" => "\xC3\xB8"    ,  /* U+01FE: LATIN CAPITAL LETTER O WITH STROKE AND ACUTE             -> U+00F8: LATIN SMALL LETTER O WITH STROKE                   */
            "\xC7\xBF" => "\xC3\xB8"    ,  /* U+01FF: LATIN SMALL LETTER O WITH STROKE AND ACUTE               -> U+00F8: LATIN SMALL LETTER O WITH STROKE                   */
            "\xC8\x80" => "\x61"        ,  /* U+0200: LATIN CAPITAL LETTER A WITH DOUBLE GRAVE                 -> U+0061: LATIN SMALL LETTER A                               */
            "\xC8\x81" => "\x61"        ,  /* U+0201: LATIN SMALL LETTER A WITH DOUBLE GRAVE                   -> U+0061: LATIN SMALL LETTER A                               */
            "\xC8\x82" => "\x61"        ,  /* U+0202: LATIN CAPITAL LETTER A WITH INVERTED BREVE               -> U+0061: LATIN SMALL LETTER A                               */
            "\xC8\x83" => "\x61"        ,  /* U+0203: LATIN SMALL LETTER A WITH INVERTED BREVE                 -> U+0061: LATIN SMALL LETTER A                               */
            "\xC8\x84" => "\x65"        ,  /* U+0204: LATIN CAPITAL LETTER E WITH DOUBLE GRAVE                 -> U+0065: LATIN SMALL LETTER E                               */
            "\xC8\x85" => "\x65"        ,  /* U+0205: LATIN SMALL LETTER E WITH DOUBLE GRAVE                   -> U+0065: LATIN SMALL LETTER E                               */
            "\xC8\x86" => "\x65"        ,  /* U+0206: LATIN CAPITAL LETTER E WITH INVERTED BREVE               -> U+0065: LATIN SMALL LETTER E                               */
            "\xC8\x87" => "\x65"        ,  /* U+0207: LATIN SMALL LETTER E WITH INVERTED BREVE                 -> U+0065: LATIN SMALL LETTER E                               */
            "\xC8\x88" => "\x69"        ,  /* U+0208: LATIN CAPITAL LETTER I WITH DOUBLE GRAVE                 -> U+0069: LATIN SMALL LETTER I                               */
            "\xC8\x89" => "\x69"        ,  /* U+0209: LATIN SMALL LETTER I WITH DOUBLE GRAVE                   -> U+0069: LATIN SMALL LETTER I                               */
            "\xC8\x8A" => "\x69"        ,  /* U+020A: LATIN CAPITAL LETTER I WITH INVERTED BREVE               -> U+0069: LATIN SMALL LETTER I                               */
            "\xC8\x8B" => "\x69"        ,  /* U+020B: LATIN SMALL LETTER I WITH INVERTED BREVE                 -> U+0069: LATIN SMALL LETTER I                               */
            "\xC8\x8C" => "\x6F"        ,  /* U+020C: LATIN CAPITAL LETTER O WITH DOUBLE GRAVE                 -> U+006F: LATIN SMALL LETTER O                               */
            "\xC8\x8D" => "\x6F"        ,  /* U+020D: LATIN SMALL LETTER O WITH DOUBLE GRAVE                   -> U+006F: LATIN SMALL LETTER O                               */
            "\xC8\x8E" => "\x6F"        ,  /* U+020E: LATIN CAPITAL LETTER O WITH INVERTED BREVE               -> U+006F: LATIN SMALL LETTER O                               */
            "\xC8\x8F" => "\x6F"        ,  /* U+020F: LATIN SMALL LETTER O WITH INVERTED BREVE                 -> U+006F: LATIN SMALL LETTER O                               */
            "\xC8\x90" => "\x72"        ,  /* U+0210: LATIN CAPITAL LETTER R WITH DOUBLE GRAVE                 -> U+0072: LATIN SMALL LETTER R                               */
            "\xC8\x91" => "\x72"        ,  /* U+0211: LATIN SMALL LETTER R WITH DOUBLE GRAVE                   -> U+0072: LATIN SMALL LETTER R                               */
            "\xC8\x92" => "\x72"        ,  /* U+0212: LATIN CAPITAL LETTER R WITH INVERTED BREVE               -> U+0072: LATIN SMALL LETTER R                               */
            "\xC8\x93" => "\x72"        ,  /* U+0213: LATIN SMALL LETTER R WITH INVERTED BREVE                 -> U+0072: LATIN SMALL LETTER R                               */
            "\xC8\x94" => "\x75"        ,  /* U+0214: LATIN CAPITAL LETTER U WITH DOUBLE GRAVE                 -> U+0075: LATIN SMALL LETTER U                               */
            "\xC8\x95" => "\x75"        ,  /* U+0215: LATIN SMALL LETTER U WITH DOUBLE GRAVE                   -> U+0075: LATIN SMALL LETTER U                               */
            "\xC8\x96" => "\x75"        ,  /* U+0216: LATIN CAPITAL LETTER U WITH INVERTED BREVE               -> U+0075: LATIN SMALL LETTER U                               */
            "\xC8\x97" => "\x75"        ,  /* U+0217: LATIN SMALL LETTER U WITH INVERTED BREVE                 -> U+0075: LATIN SMALL LETTER U                               */
            "\xC8\x98" => "\x73"        ,  /* U+0218: LATIN CAPITAL LETTER S WITH COMMA BELOW                  -> U+0073: LATIN SMALL LETTER S                               */
            "\xC8\x99" => "\x73"        ,  /* U+0219: LATIN SMALL LETTER S WITH COMMA BELOW                    -> U+0073: LATIN SMALL LETTER S                               */
            "\xC8\x9A" => "\x74"        ,  /* U+021A: LATIN CAPITAL LETTER T WITH COMMA BELOW                  -> U+0074: LATIN SMALL LETTER T                               */
            "\xC8\x9B" => "\x74"        ,  /* U+021B: LATIN SMALL LETTER T WITH COMMA BELOW                    -> U+0074: LATIN SMALL LETTER T                               */
            "\xC8\x9C" => "\xC8\x9D"    ,  /* U+021C: LATIN CAPITAL LETTER YOGH                                -> U+021D: LATIN SMALL LETTER YOGH                            */
            "\xC8\x9E" => "\x68"        ,  /* U+021E: LATIN CAPITAL LETTER H WITH CARON                        -> U+0068: LATIN SMALL LETTER H                               */
            "\xC8\x9F" => "\x68"        ,  /* U+021F: LATIN SMALL LETTER H WITH CARON                          -> U+0068: LATIN SMALL LETTER H                               */
            "\xC8\xA0" => "\xC6\x9E"    ,  /* U+0220: LATIN CAPITAL LETTER N WITH LONG RIGHT LEG               -> U+019E: LATIN SMALL LETTER N WITH LONG RIGHT LEG           */
            "\xC8\xA2" => "\xC8\xA3"    ,  /* U+0222: LATIN CAPITAL LETTER OU                                  -> U+0223: LATIN SMALL LETTER OU                              */
            "\xC8\xA4" => "\xC8\xA5"    ,  /* U+0224: LATIN CAPITAL LETTER Z WITH HOOK                         -> U+0225: LATIN SMALL LETTER Z WITH HOOK                     */
            "\xC8\xA6" => "\x61"        ,  /* U+0226: LATIN CAPITAL LETTER A WITH DOT ABOVE                    -> U+0061: LATIN SMALL LETTER A                               */
            "\xC8\xA7" => "\x61"        ,  /* U+0227: LATIN SMALL LETTER A WITH DOT ABOVE                      -> U+0061: LATIN SMALL LETTER A                               */
            "\xC8\xA8" => "\x65"        ,  /* U+0228: LATIN CAPITAL LETTER E WITH CEDILLA                      -> U+0065: LATIN SMALL LETTER E                               */
            "\xC8\xA9" => "\x65"        ,  /* U+0229: LATIN SMALL LETTER E WITH CEDILLA                        -> U+0065: LATIN SMALL LETTER E                               */
            "\xC8\xAA" => "\x6F"        ,  /* U+022A: LATIN CAPITAL LETTER O WITH DIAERESIS AND MACRON         -> U+006F: LATIN SMALL LETTER O                               */
            "\xC8\xAB" => "\x6F"        ,  /* U+022B: LATIN SMALL LETTER O WITH DIAERESIS AND MACRON           -> U+006F: LATIN SMALL LETTER O                               */
            "\xC8\xAC" => "\x6F"        ,  /* U+022C: LATIN CAPITAL LETTER O WITH TILDE AND MACRON             -> U+006F: LATIN SMALL LETTER O                               */
            "\xC8\xAD" => "\x6F"        ,  /* U+022D: LATIN SMALL LETTER O WITH TILDE AND MACRON               -> U+006F: LATIN SMALL LETTER O                               */
            "\xC8\xAE" => "\x6F"        ,  /* U+022E: LATIN CAPITAL LETTER O WITH DOT ABOVE                    -> U+006F: LATIN SMALL LETTER O                               */
            "\xC8\xAF" => "\x6F"        ,  /* U+022F: LATIN SMALL LETTER O WITH DOT ABOVE                      -> U+006F: LATIN SMALL LETTER O                               */
            "\xC8\xB0" => "\x6F"        ,  /* U+0230: LATIN CAPITAL LETTER O WITH DOT ABOVE AND MACRON         -> U+006F: LATIN SMALL LETTER O                               */
            "\xC8\xB1" => "\x6F"        ,  /* U+0231: LATIN SMALL LETTER O WITH DOT ABOVE AND MACRON           -> U+006F: LATIN SMALL LETTER O                               */
            "\xC8\xB2" => "\x79"        ,  /* U+0232: LATIN CAPITAL LETTER Y WITH MACRON                       -> U+0079: LATIN SMALL LETTER Y                               */
            "\xC8\xB3" => "\x79"        ,  /* U+0233: LATIN SMALL LETTER Y WITH MACRON                         -> U+0079: LATIN SMALL LETTER Y                               */
            "\xC8\xBA" => "\xE2\xB1\xA5",  /* U+023A: LATIN CAPITAL LETTER A WITH STROKE                       -> U+2C65: LATIN SMALL LETTER A WITH STROKE                   */
            "\xC8\xBB" => "\xC8\xBC"    ,  /* U+023B: LATIN CAPITAL LETTER C WITH STROKE                       -> U+023C: LATIN SMALL LETTER C WITH STROKE                   */
            "\xC8\xBD" => "\xC6\x9A"    ,  /* U+023D: LATIN CAPITAL LETTER L WITH BAR                          -> U+019A: LATIN SMALL LETTER L WITH BAR                      */
            "\xC8\xBE" => "\xE2\xB1\xA6",  /* U+023E: LATIN CAPITAL LETTER T WITH DIAGONAL STROKE              -> U+2C66: LATIN SMALL LETTER T WITH DIAGONAL STROKE          */
            "\xC9\x81" => "\xC9\x82"    ,  /* U+0241: LATIN CAPITAL LETTER GLOTTAL STOP                        -> U+0242: LATIN SMALL LETTER GLOTTAL STOP                    */
            "\xC9\x83" => "\xC6\x80"    ,  /* U+0243: LATIN CAPITAL LETTER B WITH STROKE                       -> U+0180: LATIN SMALL LETTER B WITH STROKE                   */
            "\xC9\x84" => "\xCA\x89"    ,  /* U+0244: LATIN CAPITAL LETTER U BAR                               -> U+0289: LATIN SMALL LETTER U BAR                           */
            "\xC9\x85" => "\xCA\x8C"    ,  /* U+0245: LATIN CAPITAL LETTER TURNED V                            -> U+028C: LATIN SMALL LETTER TURNED V                        */
            "\xC9\x86" => "\xC9\x87"    ,  /* U+0246: LATIN CAPITAL LETTER E WITH STROKE                       -> U+0247: LATIN SMALL LETTER E WITH STROKE                   */
            "\xC9\x88" => "\xC9\x89"    ,  /* U+0248: LATIN CAPITAL LETTER J WITH STROKE                       -> U+0249: LATIN SMALL LETTER J WITH STROKE                   */
            "\xC9\x8A" => "\xC9\x8B"    ,  /* U+024A: LATIN CAPITAL LETTER SMALL Q WITH HOOK TAIL              -> U+024B: LATIN SMALL LETTER Q WITH HOOK TAIL                */
            "\xC9\x8C" => "\xC9\x8D"    ,  /* U+024C: LATIN CAPITAL LETTER R WITH STROKE                       -> U+024D: LATIN SMALL LETTER R WITH STROKE                   */
            "\xC9\x8E" => "\xC9\x8F"    ,  /* U+024E: LATIN CAPITAL LETTER Y WITH STROKE                       -> U+024F: LATIN SMALL LETTER Y WITH STROKE                   */
            "\xCA\xB0" => "\x68"        ,  /* U+02B0: MODIFIER LETTER SMALL H                                  -> U+0068: LATIN SMALL LETTER H                               */
            "\xCA\xB1" => "\xC9\xA6"    ,  /* U+02B1: MODIFIER LETTER SMALL H WITH HOOK                        -> U+0266: LATIN SMALL LETTER H WITH HOOK                     */
            "\xCA\xB2" => "\x6A"        ,  /* U+02B2: MODIFIER LETTER SMALL J                                  -> U+006A: LATIN SMALL LETTER J                               */
            "\xCA\xB3" => "\x72"        ,  /* U+02B3: MODIFIER LETTER SMALL R                                  -> U+0072: LATIN SMALL LETTER R                               */
            "\xCA\xB4" => "\xC9\xB9"    ,  /* U+02B4: MODIFIER LETTER SMALL TURNED R                           -> U+0279: LATIN SMALL LETTER TURNED R                        */
            "\xCA\xB5" => "\xC9\xBB"    ,  /* U+02B5: MODIFIER LETTER SMALL TURNED R WITH HOOK                 -> U+027B: LATIN SMALL LETTER TURNED R WITH HOOK              */
            "\xCA\xB6" => "\xCA\x81"    ,  /* U+02B6: MODIFIER LETTER SMALL CAPITAL INVERTED R                 -> U+0281: LATIN LETTER SMALL CAPITAL INVERTED R              */
            "\xCA\xB7" => "\x77"        ,  /* U+02B7: MODIFIER LETTER SMALL W                                  -> U+0077: LATIN SMALL LETTER W                               */
            "\xCA\xB8" => "\x79"        ,  /* U+02B8: MODIFIER LETTER SMALL Y                                  -> U+0079: LATIN SMALL LETTER Y                               */
            "\xCB\x98" => "\x20"        ,  /* U+02D8: BREVE                                                    -> U+0020: SPACE                                              */
            "\xCB\x99" => "\x20"        ,  /* U+02D9: DOT ABOVE                                                -> U+0020: SPACE                                              */
            "\xCB\x9A" => "\x20"        ,  /* U+02DA: RING ABOVE                                               -> U+0020: SPACE                                              */
            "\xCB\x9B" => "\x20"        ,  /* U+02DB: OGONEK                                                   -> U+0020: SPACE                                              */
            "\xCB\x9C" => "\x20"        ,  /* U+02DC: SMALL TILDE                                              -> U+0020: SPACE                                              */
            "\xCB\x9D" => "\x20"        ,  /* U+02DD: DOUBLE ACUTE ACCENT                                      -> U+0020: SPACE                                              */
            "\xCB\xA0" => "\xC9\xA3"    ,  /* U+02E0: MODIFIER LETTER SMALL GAMMA                              -> U+0263: LATIN SMALL LETTER GAMMA                           */
            "\xCB\xA1" => "\x6C"        ,  /* U+02E1: MODIFIER LETTER SMALL L                                  -> U+006C: LATIN SMALL LETTER L                               */
            "\xCB\xA2" => "\x73"        ,  /* U+02E2: MODIFIER LETTER SMALL S                                  -> U+0073: LATIN SMALL LETTER S                               */
            "\xCB\xA3" => "\x78"        ,  /* U+02E3: MODIFIER LETTER SMALL X                                  -> U+0078: LATIN SMALL LETTER X                               */
            "\xCB\xA4" => "\xCA\x95"    ,  /* U+02E4: MODIFIER LETTER SMALL REVERSED GLOTTAL STOP              -> U+0295: LATIN LETTER PHARYNGEAL VOICED FRICATIVE           */
            "\xCD\xB0" => "\xCD\xB1"    ,  /* U+0370: GREEK CAPITAL LETTER HETA                                -> U+0371: GREEK SMALL LETTER HETA                            */
            "\xCD\xB2" => "\xCD\xB3"    ,  /* U+0372: GREEK CAPITAL LETTER ARCHAIC SAMPI                       -> U+0373: GREEK SMALL LETTER ARCHAIC SAMPI                   */
            "\xCD\xB4" => "\xCA\xB9"    ,  /* U+0374: GREEK NUMERAL SIGN                                       -> U+02B9: MODIFIER LETTER PRIME                              */
            "\xCD\xB6" => "\xCD\xB7"    ,  /* U+0376: GREEK CAPITAL LETTER PAMPHYLIAN DIGAMMA                  -> U+0377: GREEK SMALL LETTER PAMPHYLIAN DIGAMMA              */
            "\xCD\xBA" => "\x20"        ,  /* U+037A: GREEK YPOGEGRAMMENI                                      -> U+0020: SPACE                                              */
            "\xCD\xBE" => "\x3B"        ,  /* U+037E: GREEK QUESTION MARK                                      -> U+003B: SEMICOLON                                          */
            "\xCE\x84" => "\x20"        ,  /* U+0384: GREEK TONOS                                              -> U+0020: SPACE                                              */
            "\xCE\x85" => "\x20"        ,  /* U+0385: GREEK DIALYTIKA TONOS                                    -> U+0020: SPACE                                              */
            "\xCE\x86" => "\xCE\xB1"    ,  /* U+0386: GREEK CAPITAL LETTER ALPHA WITH TONOS                    -> U+03B1: GREEK SMALL LETTER ALPHA                           */
            "\xCE\x87" => "\xC2\xB7"    ,  /* U+0387: GREEK ANO TELEIA                                         -> U+00B7: MIDDLE DOT                                         */
            "\xCE\x88" => "\xCE\xB5"    ,  /* U+0388: GREEK CAPITAL LETTER EPSILON WITH TONOS                  -> U+03B5: GREEK SMALL LETTER EPSILON                         */
            "\xCE\x89" => "\xCE\xB7"    ,  /* U+0389: GREEK CAPITAL LETTER ETA WITH TONOS                      -> U+03B7: GREEK SMALL LETTER ETA                             */
            "\xCE\x8A" => "\xCE\xB9"    ,  /* U+038A: GREEK CAPITAL LETTER IOTA WITH TONOS                     -> U+03B9: GREEK SMALL LETTER IOTA                            */
            "\xCE\x8C" => "\xCE\xBF"    ,  /* U+038C: GREEK CAPITAL LETTER OMICRON WITH TONOS                  -> U+03BF: GREEK SMALL LETTER OMICRON                         */
            "\xCE\x8E" => "\xCF\x85"    ,  /* U+038E: GREEK CAPITAL LETTER UPSILON WITH TONOS                  -> U+03C5: GREEK SMALL LETTER UPSILON                         */
            "\xCE\x8F" => "\xCF\x89"    ,  /* U+038F: GREEK CAPITAL LETTER OMEGA WITH TONOS                    -> U+03C9: GREEK SMALL LETTER OMEGA                           */
            "\xCE\x90" => "\xCE\xB9"    ,  /* U+0390: GREEK SMALL LETTER IOTA WITH DIALYTIKA AND TONOS         -> U+03B9: GREEK SMALL LETTER IOTA                            */
            "\xCE\x91" => "\xCE\xB1"    ,  /* U+0391: GREEK CAPITAL LETTER ALPHA                               -> U+03B1: GREEK SMALL LETTER ALPHA                           */
            "\xCE\x92" => "\xCE\xB2"    ,  /* U+0392: GREEK CAPITAL LETTER BETA                                -> U+03B2: GREEK SMALL LETTER BETA                            */
            "\xCE\x93" => "\xCE\xB3"    ,  /* U+0393: GREEK CAPITAL LETTER GAMMA                               -> U+03B3: GREEK SMALL LETTER GAMMA                           */
            "\xCE\x94" => "\xCE\xB4"    ,  /* U+0394: GREEK CAPITAL LETTER DELTA                               -> U+03B4: GREEK SMALL LETTER DELTA                           */
            "\xCE\x95" => "\xCE\xB5"    ,  /* U+0395: GREEK CAPITAL LETTER EPSILON                             -> U+03B5: GREEK SMALL LETTER EPSILON                         */
            "\xCE\x96" => "\xCE\xB6"    ,  /* U+0396: GREEK CAPITAL LETTER ZETA                                -> U+03B6: GREEK SMALL LETTER ZETA                            */
            "\xCE\x97" => "\xCE\xB7"    ,  /* U+0397: GREEK CAPITAL LETTER ETA                                 -> U+03B7: GREEK SMALL LETTER ETA                             */
            "\xCE\x98" => "\xCE\xB8"    ,  /* U+0398: GREEK CAPITAL LETTER THETA                               -> U+03B8: GREEK SMALL LETTER THETA                           */
            "\xCE\x99" => "\xCE\xB9"    ,  /* U+0399: GREEK CAPITAL LETTER IOTA                                -> U+03B9: GREEK SMALL LETTER IOTA                            */
            "\xCE\x9A" => "\xCE\xBA"    ,  /* U+039A: GREEK CAPITAL LETTER KAPPA                               -> U+03BA: GREEK SMALL LETTER KAPPA                           */
            "\xCE\x9B" => "\xCE\xBB"    ,  /* U+039B: GREEK CAPITAL LETTER LAMDA                               -> U+03BB: GREEK SMALL LETTER LAMDA                           */
            "\xCE\x9C" => "\xCE\xBC"    ,  /* U+039C: GREEK CAPITAL LETTER MU                                  -> U+03BC: GREEK SMALL LETTER MU                              */
            "\xCE\x9D" => "\xCE\xBD"    ,  /* U+039D: GREEK CAPITAL LETTER NU                                  -> U+03BD: GREEK SMALL LETTER NU                              */
            "\xCE\x9E" => "\xCE\xBE"    ,  /* U+039E: GREEK CAPITAL LETTER XI                                  -> U+03BE: GREEK SMALL LETTER XI                              */
            "\xCE\x9F" => "\xCE\xBF"    ,  /* U+039F: GREEK CAPITAL LETTER OMICRON                             -> U+03BF: GREEK SMALL LETTER OMICRON                         */
            "\xCE\xA0" => "\xCF\x80"    ,  /* U+03A0: GREEK CAPITAL LETTER PI                                  -> U+03C0: GREEK SMALL LETTER PI                              */
            "\xCE\xA1" => "\xCF\x81"    ,  /* U+03A1: GREEK CAPITAL LETTER RHO                                 -> U+03C1: GREEK SMALL LETTER RHO                             */
            "\xCE\xA3" => "\xCF\x83"    ,  /* U+03A3: GREEK CAPITAL LETTER SIGMA                               -> U+03C3: GREEK SMALL LETTER SIGMA                           */
            "\xCE\xA4" => "\xCF\x84"    ,  /* U+03A4: GREEK CAPITAL LETTER TAU                                 -> U+03C4: GREEK SMALL LETTER TAU                             */
            "\xCE\xA5" => "\xCF\x85"    ,  /* U+03A5: GREEK CAPITAL LETTER UPSILON                             -> U+03C5: GREEK SMALL LETTER UPSILON                         */
            "\xCE\xA6" => "\xCF\x86"    ,  /* U+03A6: GREEK CAPITAL LETTER PHI                                 -> U+03C6: GREEK SMALL LETTER PHI                             */
            "\xCE\xA7" => "\xCF\x87"    ,  /* U+03A7: GREEK CAPITAL LETTER CHI                                 -> U+03C7: GREEK SMALL LETTER CHI                             */
            "\xCE\xA8" => "\xCF\x88"    ,  /* U+03A8: GREEK CAPITAL LETTER PSI                                 -> U+03C8: GREEK SMALL LETTER PSI                             */
            "\xCE\xA9" => "\xCF\x89"    ,  /* U+03A9: GREEK CAPITAL LETTER OMEGA                               -> U+03C9: GREEK SMALL LETTER OMEGA                           */
            "\xCE\xAA" => "\xCE\xB9"    ,  /* U+03AA: GREEK CAPITAL LETTER IOTA WITH DIALYTIKA                 -> U+03B9: GREEK SMALL LETTER IOTA                            */
            "\xCE\xAB" => "\xCF\x85"    ,  /* U+03AB: GREEK CAPITAL LETTER UPSILON WITH DIALYTIKA              -> U+03C5: GREEK SMALL LETTER UPSILON                         */
            "\xCE\xAC" => "\xCE\xB1"    ,  /* U+03AC: GREEK SMALL LETTER ALPHA WITH TONOS                      -> U+03B1: GREEK SMALL LETTER ALPHA                           */
            "\xCE\xAD" => "\xCE\xB5"    ,  /* U+03AD: GREEK SMALL LETTER EPSILON WITH TONOS                    -> U+03B5: GREEK SMALL LETTER EPSILON                         */
            "\xCE\xAE" => "\xCE\xB7"    ,  /* U+03AE: GREEK SMALL LETTER ETA WITH TONOS                        -> U+03B7: GREEK SMALL LETTER ETA                             */
            "\xCE\xAF" => "\xCE\xB9"    ,  /* U+03AF: GREEK SMALL LETTER IOTA WITH TONOS                       -> U+03B9: GREEK SMALL LETTER IOTA                            */
            "\xCE\xB0" => "\xCF\x85"    ,  /* U+03B0: GREEK SMALL LETTER UPSILON WITH DIALYTIKA AND TONOS      -> U+03C5: GREEK SMALL LETTER UPSILON                         */
            "\xCF\x8A" => "\xCE\xB9"    ,  /* U+03CA: GREEK SMALL LETTER IOTA WITH DIALYTIKA                   -> U+03B9: GREEK SMALL LETTER IOTA                            */
            "\xCF\x8B" => "\xCF\x85"    ,  /* U+03CB: GREEK SMALL LETTER UPSILON WITH DIALYTIKA                -> U+03C5: GREEK SMALL LETTER UPSILON                         */
            "\xCF\x8C" => "\xCE\xBF"    ,  /* U+03CC: GREEK SMALL LETTER OMICRON WITH TONOS                    -> U+03BF: GREEK SMALL LETTER OMICRON                         */
            "\xCF\x8D" => "\xCF\x85"    ,  /* U+03CD: GREEK SMALL LETTER UPSILON WITH TONOS                    -> U+03C5: GREEK SMALL LETTER UPSILON                         */
            "\xCF\x8E" => "\xCF\x89"    ,  /* U+03CE: GREEK SMALL LETTER OMEGA WITH TONOS                      -> U+03C9: GREEK SMALL LETTER OMEGA                           */
            "\xCF\x8F" => "\xCF\x97"    ,  /* U+03CF: GREEK CAPITAL KAI SYMBOL                                 -> U+03D7: GREEK KAI SYMBOL                                   */
            "\xCF\x90" => "\xCE\xB2"    ,  /* U+03D0: GREEK BETA SYMBOL                                        -> U+03B2: GREEK SMALL LETTER BETA                            */
            "\xCF\x91" => "\xCE\xB8"    ,  /* U+03D1: GREEK THETA SYMBOL                                       -> U+03B8: GREEK SMALL LETTER THETA                           */
            "\xCF\x92" => "\xCF\x85"    ,  /* U+03D2: GREEK UPSILON WITH HOOK SYMBOL                           -> U+03C5: GREEK SMALL LETTER UPSILON                         */
            "\xCF\x93" => "\xCF\x85"    ,  /* U+03D3: GREEK UPSILON WITH ACUTE AND HOOK SYMBOL                 -> U+03C5: GREEK SMALL LETTER UPSILON                         */
            "\xCF\x94" => "\xCF\x85"    ,  /* U+03D4: GREEK UPSILON WITH DIAERESIS AND HOOK SYMBOL             -> U+03C5: GREEK SMALL LETTER UPSILON                         */
            "\xCF\x95" => "\xCF\x86"    ,  /* U+03D5: GREEK PHI SYMBOL                                         -> U+03C6: GREEK SMALL LETTER PHI                             */
            "\xCF\x96" => "\xCF\x80"    ,  /* U+03D6: GREEK PI SYMBOL                                          -> U+03C0: GREEK SMALL LETTER PI                              */
            "\xCF\x98" => "\xCF\x99"    ,  /* U+03D8: GREEK LETTER ARCHAIC KOPPA                               -> U+03D9: GREEK SMALL LETTER ARCHAIC KOPPA                   */
            "\xCF\x9A" => "\xCF\x9B"    ,  /* U+03DA: GREEK LETTER STIGMA                                      -> U+03DB: GREEK SMALL LETTER STIGMA                          */
            "\xCF\x9C" => "\xCF\x9D"    ,  /* U+03DC: GREEK LETTER DIGAMMA                                     -> U+03DD: GREEK SMALL LETTER DIGAMMA                         */
            "\xCF\x9E" => "\xCF\x9F"    ,  /* U+03DE: GREEK LETTER KOPPA                                       -> U+03DF: GREEK SMALL LETTER KOPPA                           */
            "\xCF\xA0" => "\xCF\xA1"    ,  /* U+03E0: GREEK LETTER SAMPI                                       -> U+03E1: GREEK SMALL LETTER SAMPI                           */
            "\xCF\xA2" => "\xCF\xA3"    ,  /* U+03E2: COPTIC CAPITAL LETTER SHEI                               -> U+03E3: COPTIC SMALL LETTER SHEI                           */
            "\xCF\xA4" => "\xCF\xA5"    ,  /* U+03E4: COPTIC CAPITAL LETTER FEI                                -> U+03E5: COPTIC SMALL LETTER FEI                            */
            "\xCF\xA6" => "\xCF\xA7"    ,  /* U+03E6: COPTIC CAPITAL LETTER KHEI                               -> U+03E7: COPTIC SMALL LETTER KHEI                           */
            "\xCF\xA8" => "\xCF\xA9"    ,  /* U+03E8: COPTIC CAPITAL LETTER HORI                               -> U+03E9: COPTIC SMALL LETTER HORI                           */
            "\xCF\xAA" => "\xCF\xAB"    ,  /* U+03EA: COPTIC CAPITAL LETTER GANGIA                             -> U+03EB: COPTIC SMALL LETTER GANGIA                         */
            "\xCF\xAC" => "\xCF\xAD"    ,  /* U+03EC: COPTIC CAPITAL LETTER SHIMA                              -> U+03ED: COPTIC SMALL LETTER SHIMA                          */
            "\xCF\xAE" => "\xCF\xAF"    ,  /* U+03EE: COPTIC CAPITAL LETTER DEI                                -> U+03EF: COPTIC SMALL LETTER DEI                            */
            "\xCF\xB0" => "\xCE\xBA"    ,  /* U+03F0: GREEK KAPPA SYMBOL                                       -> U+03BA: GREEK SMALL LETTER KAPPA                           */
            "\xCF\xB1" => "\xCF\x81"    ,  /* U+03F1: GREEK RHO SYMBOL                                         -> U+03C1: GREEK SMALL LETTER RHO                             */
            "\xCF\xB2" => "\xCF\x82"    ,  /* U+03F2: GREEK LUNATE SIGMA SYMBOL                                -> U+03C2: GREEK SMALL LETTER FINAL SIGMA                     */
            "\xCF\xB4" => "\xCE\xB8"    ,  /* U+03F4: GREEK CAPITAL THETA SYMBOL                               -> U+03B8: GREEK SMALL LETTER THETA                           */
            "\xCF\xB5" => "\xCE\xB5"    ,  /* U+03F5: GREEK LUNATE EPSILON SYMBOL                              -> U+03B5: GREEK SMALL LETTER EPSILON                         */
            "\xCF\xB7" => "\xCF\xB8"    ,  /* U+03F7: GREEK CAPITAL LETTER SHO                                 -> U+03F8: GREEK SMALL LETTER SHO                             */
            "\xCF\xB9" => "\xCF\x83"    ,  /* U+03F9: GREEK CAPITAL LUNATE SIGMA SYMBOL                        -> U+03C3: GREEK SMALL LETTER SIGMA                           */
            "\xCF\xBA" => "\xCF\xBB"    ,  /* U+03FA: GREEK CAPITAL LETTER SAN                                 -> U+03FB: GREEK SMALL LETTER SAN                             */
            "\xCF\xBD" => "\xCD\xBB"    ,  /* U+03FD: GREEK CAPITAL REVERSED LUNATE SIGMA SYMBOL               -> U+037B: GREEK SMALL REVERSED LUNATE SIGMA SYMBOL           */
            "\xCF\xBE" => "\xCD\xBC"    ,  /* U+03FE: GREEK CAPITAL DOTTED LUNATE SIGMA SYMBOL                 -> U+037C: GREEK SMALL DOTTED LUNATE SIGMA SYMBOL             */
            "\xCF\xBF" => "\xCD\xBD"    ,  /* U+03FF: GREEK CAPITAL REVERSED DOTTED LUNATE SIGMA SYMBOL        -> U+037D: GREEK SMALL REVERSED DOTTED LUNATE SIGMA SYMBOL    */
            "\xD0\x80" => "\xD0\xB5"    ,  /* U+0400: CYRILLIC CAPITAL LETTER IE WITH GRAVE                    -> U+0435: CYRILLIC SMALL LETTER IE                           */
            "\xD0\x81" => "\xD0\xB5"    ,  /* U+0401: CYRILLIC CAPITAL LETTER IO                               -> U+0435: CYRILLIC SMALL LETTER IE                           */
            "\xD0\x82" => "\xD1\x92"    ,  /* U+0402: CYRILLIC CAPITAL LETTER DJE                              -> U+0452: CYRILLIC SMALL LETTER DJE                          */
            "\xD0\x83" => "\xD0\xB3"    ,  /* U+0403: CYRILLIC CAPITAL LETTER GJE                              -> U+0433: CYRILLIC SMALL LETTER GHE                          */
            "\xD0\x84" => "\xD1\x94"    ,  /* U+0404: CYRILLIC CAPITAL LETTER UKRAINIAN IE                     -> U+0454: CYRILLIC SMALL LETTER UKRAINIAN IE                 */
            "\xD0\x85" => "\xD1\x95"    ,  /* U+0405: CYRILLIC CAPITAL LETTER DZE                              -> U+0455: CYRILLIC SMALL LETTER DZE                          */
            "\xD0\x86" => "\xD1\x96"    ,  /* U+0406: CYRILLIC CAPITAL LETTER BYELORUSSIAN-UKRAINIAN I         -> U+0456: CYRILLIC SMALL LETTER BYELORUSSIAN-UKRAINIAN I     */
            "\xD0\x87" => "\xD1\x96"    ,  /* U+0407: CYRILLIC CAPITAL LETTER YI                               -> U+0456: CYRILLIC SMALL LETTER BYELORUSSIAN-UKRAINIAN I     */
            "\xD0\x88" => "\xD1\x98"    ,  /* U+0408: CYRILLIC CAPITAL LETTER JE                               -> U+0458: CYRILLIC SMALL LETTER JE                           */
            "\xD0\x89" => "\xD1\x99"    ,  /* U+0409: CYRILLIC CAPITAL LETTER LJE                              -> U+0459: CYRILLIC SMALL LETTER LJE                          */
            "\xD0\x8A" => "\xD1\x9A"    ,  /* U+040A: CYRILLIC CAPITAL LETTER NJE                              -> U+045A: CYRILLIC SMALL LETTER NJE                          */
            "\xD0\x8B" => "\xD1\x9B"    ,  /* U+040B: CYRILLIC CAPITAL LETTER TSHE                             -> U+045B: CYRILLIC SMALL LETTER TSHE                         */
            "\xD0\x8C" => "\xD0\xBA"    ,  /* U+040C: CYRILLIC CAPITAL LETTER KJE                              -> U+043A: CYRILLIC SMALL LETTER KA                           */
            "\xD0\x8D" => "\xD0\xB8"    ,  /* U+040D: CYRILLIC CAPITAL LETTER I WITH GRAVE                     -> U+0438: CYRILLIC SMALL LETTER I                            */
            "\xD0\x8E" => "\xD1\x83"    ,  /* U+040E: CYRILLIC CAPITAL LETTER SHORT U                          -> U+0443: CYRILLIC SMALL LETTER U                            */
            "\xD0\x8F" => "\xD1\x9F"    ,  /* U+040F: CYRILLIC CAPITAL LETTER DZHE                             -> U+045F: CYRILLIC SMALL LETTER DZHE                         */
            "\xD0\x90" => "\xD0\xB0"    ,  /* U+0410: CYRILLIC CAPITAL LETTER A                                -> U+0430: CYRILLIC SMALL LETTER A                            */
            "\xD0\x91" => "\xD0\xB1"    ,  /* U+0411: CYRILLIC CAPITAL LETTER BE                               -> U+0431: CYRILLIC SMALL LETTER BE                           */
            "\xD0\x92" => "\xD0\xB2"    ,  /* U+0412: CYRILLIC CAPITAL LETTER VE                               -> U+0432: CYRILLIC SMALL LETTER VE                           */
            "\xD0\x93" => "\xD0\xB3"    ,  /* U+0413: CYRILLIC CAPITAL LETTER GHE                              -> U+0433: CYRILLIC SMALL LETTER GHE                          */
            "\xD0\x94" => "\xD0\xB4"    ,  /* U+0414: CYRILLIC CAPITAL LETTER DE                               -> U+0434: CYRILLIC SMALL LETTER DE                           */
            "\xD0\x95" => "\xD0\xB5"    ,  /* U+0415: CYRILLIC CAPITAL LETTER IE                               -> U+0435: CYRILLIC SMALL LETTER IE                           */
            "\xD0\x96" => "\xD0\xB6"    ,  /* U+0416: CYRILLIC CAPITAL LETTER ZHE                              -> U+0436: CYRILLIC SMALL LETTER ZHE                          */
            "\xD0\x97" => "\xD0\xB7"    ,  /* U+0417: CYRILLIC CAPITAL LETTER ZE                               -> U+0437: CYRILLIC SMALL LETTER ZE                           */
            "\xD0\x98" => "\xD0\xB8"    ,  /* U+0418: CYRILLIC CAPITAL LETTER I                                -> U+0438: CYRILLIC SMALL LETTER I                            */
            "\xD0\x99" => "\xD0\xB8"    ,  /* U+0419: CYRILLIC CAPITAL LETTER SHORT I                          -> U+0438: CYRILLIC SMALL LETTER I                            */
            "\xD0\x9A" => "\xD0\xBA"    ,  /* U+041A: CYRILLIC CAPITAL LETTER KA                               -> U+043A: CYRILLIC SMALL LETTER KA                           */
            "\xD0\x9B" => "\xD0\xBB"    ,  /* U+041B: CYRILLIC CAPITAL LETTER EL                               -> U+043B: CYRILLIC SMALL LETTER EL                           */
            "\xD0\x9C" => "\xD0\xBC"    ,  /* U+041C: CYRILLIC CAPITAL LETTER EM                               -> U+043C: CYRILLIC SMALL LETTER EM                           */
            "\xD0\x9D" => "\xD0\xBD"    ,  /* U+041D: CYRILLIC CAPITAL LETTER EN                               -> U+043D: CYRILLIC SMALL LETTER EN                           */
            "\xD0\x9E" => "\xD0\xBE"    ,  /* U+041E: CYRILLIC CAPITAL LETTER O                                -> U+043E: CYRILLIC SMALL LETTER O                            */
            "\xD0\x9F" => "\xD0\xBF"    ,  /* U+041F: CYRILLIC CAPITAL LETTER PE                               -> U+043F: CYRILLIC SMALL LETTER PE                           */
            "\xD0\xA0" => "\xD1\x80"    ,  /* U+0420: CYRILLIC CAPITAL LETTER ER                               -> U+0440: CYRILLIC SMALL LETTER ER                           */
            "\xD0\xA1" => "\xD1\x81"    ,  /* U+0421: CYRILLIC CAPITAL LETTER ES                               -> U+0441: CYRILLIC SMALL LETTER ES                           */
            "\xD0\xA2" => "\xD1\x82"    ,  /* U+0422: CYRILLIC CAPITAL LETTER TE                               -> U+0442: CYRILLIC SMALL LETTER TE                           */
            "\xD0\xA3" => "\xD1\x83"    ,  /* U+0423: CYRILLIC CAPITAL LETTER U                                -> U+0443: CYRILLIC SMALL LETTER U                            */
            "\xD0\xA4" => "\xD1\x84"    ,  /* U+0424: CYRILLIC CAPITAL LETTER EF                               -> U+0444: CYRILLIC SMALL LETTER EF                           */
            "\xD0\xA5" => "\xD1\x85"    ,  /* U+0425: CYRILLIC CAPITAL LETTER HA                               -> U+0445: CYRILLIC SMALL LETTER HA                           */
            "\xD0\xA6" => "\xD1\x86"    ,  /* U+0426: CYRILLIC CAPITAL LETTER TSE                              -> U+0446: CYRILLIC SMALL LETTER TSE                          */
            "\xD0\xA7" => "\xD1\x87"    ,  /* U+0427: CYRILLIC CAPITAL LETTER CHE                              -> U+0447: CYRILLIC SMALL LETTER CHE                          */
            "\xD0\xA8" => "\xD1\x88"    ,  /* U+0428: CYRILLIC CAPITAL LETTER SHA                              -> U+0448: CYRILLIC SMALL LETTER SHA                          */
            "\xD0\xA9" => "\xD1\x89"    ,  /* U+0429: CYRILLIC CAPITAL LETTER SHCHA                            -> U+0449: CYRILLIC SMALL LETTER SHCHA                        */
            "\xD0\xAA" => "\xD1\x8A"    ,  /* U+042A: CYRILLIC CAPITAL LETTER HARD SIGN                        -> U+044A: CYRILLIC SMALL LETTER HARD SIGN                    */
            "\xD0\xAB" => "\xD1\x8B"    ,  /* U+042B: CYRILLIC CAPITAL LETTER YERU                             -> U+044B: CYRILLIC SMALL LETTER YERU                         */
            "\xD0\xAC" => "\xD1\x8C"    ,  /* U+042C: CYRILLIC CAPITAL LETTER SOFT SIGN                        -> U+044C: CYRILLIC SMALL LETTER SOFT SIGN                    */
            "\xD0\xAD" => "\xD1\x8D"    ,  /* U+042D: CYRILLIC CAPITAL LETTER E                                -> U+044D: CYRILLIC SMALL LETTER E                            */
            "\xD0\xAE" => "\xD1\x8E"    ,  /* U+042E: CYRILLIC CAPITAL LETTER YU                               -> U+044E: CYRILLIC SMALL LETTER YU                           */
            "\xD0\xAF" => "\xD1\x8F"    ,  /* U+042F: CYRILLIC CAPITAL LETTER YA                               -> U+044F: CYRILLIC SMALL LETTER YA                           */
            "\xD0\xB9" => "\xD0\xB8"    ,  /* U+0439: CYRILLIC SMALL LETTER SHORT I                            -> U+0438: CYRILLIC SMALL LETTER I                            */
            "\xD1\x90" => "\xD0\xB5"    ,  /* U+0450: CYRILLIC SMALL LETTER IE WITH GRAVE                      -> U+0435: CYRILLIC SMALL LETTER IE                           */
            "\xD1\x91" => "\xD0\xB5"    ,  /* U+0451: CYRILLIC SMALL LETTER IO                                 -> U+0435: CYRILLIC SMALL LETTER IE                           */
            "\xD1\x93" => "\xD0\xB3"    ,  /* U+0453: CYRILLIC SMALL LETTER GJE                                -> U+0433: CYRILLIC SMALL LETTER GHE                          */
            "\xD1\x97" => "\xD1\x96"    ,  /* U+0457: CYRILLIC SMALL LETTER YI                                 -> U+0456: CYRILLIC SMALL LETTER BYELORUSSIAN-UKRAINIAN I     */
            "\xD1\x9C" => "\xD0\xBA"    ,  /* U+045C: CYRILLIC SMALL LETTER KJE                                -> U+043A: CYRILLIC SMALL LETTER KA                           */
            "\xD1\x9D" => "\xD0\xB8"    ,  /* U+045D: CYRILLIC SMALL LETTER I WITH GRAVE                       -> U+0438: CYRILLIC SMALL LETTER I                            */
            "\xD1\x9E" => "\xD1\x83"    ,  /* U+045E: CYRILLIC SMALL LETTER SHORT U                            -> U+0443: CYRILLIC SMALL LETTER U                            */
            "\xD1\xA0" => "\xD1\xA1"    ,  /* U+0460: CYRILLIC CAPITAL LETTER OMEGA                            -> U+0461: CYRILLIC SMALL LETTER OMEGA                        */
            "\xD1\xA2" => "\xD1\xA3"    ,  /* U+0462: CYRILLIC CAPITAL LETTER YAT                              -> U+0463: CYRILLIC SMALL LETTER YAT                          */
            "\xD1\xA4" => "\xD1\xA5"    ,  /* U+0464: CYRILLIC CAPITAL LETTER IOTIFIED E                       -> U+0465: CYRILLIC SMALL LETTER IOTIFIED E                   */
            "\xD1\xA6" => "\xD1\xA7"    ,  /* U+0466: CYRILLIC CAPITAL LETTER LITTLE YUS                       -> U+0467: CYRILLIC SMALL LETTER LITTLE YUS                   */
            "\xD1\xA8" => "\xD1\xA9"    ,  /* U+0468: CYRILLIC CAPITAL LETTER IOTIFIED LITTLE YUS              -> U+0469: CYRILLIC SMALL LETTER IOTIFIED LITTLE YUS          */
            "\xD1\xAA" => "\xD1\xAB"    ,  /* U+046A: CYRILLIC CAPITAL LETTER BIG YUS                          -> U+046B: CYRILLIC SMALL LETTER BIG YUS                      */
            "\xD1\xAC" => "\xD1\xAD"    ,  /* U+046C: CYRILLIC CAPITAL LETTER IOTIFIED BIG YUS                 -> U+046D: CYRILLIC SMALL LETTER IOTIFIED BIG YUS             */
            "\xD1\xAE" => "\xD1\xAF"    ,  /* U+046E: CYRILLIC CAPITAL LETTER KSI                              -> U+046F: CYRILLIC SMALL LETTER KSI                          */
            "\xD1\xB0" => "\xD1\xB1"    ,  /* U+0470: CYRILLIC CAPITAL LETTER PSI                              -> U+0471: CYRILLIC SMALL LETTER PSI                          */
            "\xD1\xB2" => "\xD1\xB3"    ,  /* U+0472: CYRILLIC CAPITAL LETTER FITA                             -> U+0473: CYRILLIC SMALL LETTER FITA                         */
            "\xD1\xB4" => "\xD1\xB5"    ,  /* U+0474: CYRILLIC CAPITAL LETTER IZHITSA                          -> U+0475: CYRILLIC SMALL LETTER IZHITSA                      */
            "\xD1\xB6" => "\xD1\xB5"    ,  /* U+0476: CYRILLIC CAPITAL LETTER IZHITSA WITH DOUBLE GRAVE ACCENT -> U+0475: CYRILLIC SMALL LETTER IZHITSA                      */
            "\xD1\xB7" => "\xD1\xB5"    ,  /* U+0477: CYRILLIC SMALL LETTER IZHITSA WITH DOUBLE GRAVE ACCENT   -> U+0475: CYRILLIC SMALL LETTER IZHITSA                      */
            "\xD1\xB8" => "\xD1\xB9"    ,  /* U+0478: CYRILLIC CAPITAL LETTER UK                               -> U+0479: CYRILLIC SMALL LETTER UK                           */
            "\xD1\xBA" => "\xD1\xBB"    ,  /* U+047A: CYRILLIC CAPITAL LETTER ROUND OMEGA                      -> U+047B: CYRILLIC SMALL LETTER ROUND OMEGA                  */
            "\xD1\xBC" => "\xD1\xBD"    ,  /* U+047C: CYRILLIC CAPITAL LETTER OMEGA WITH TITLO                 -> U+047D: CYRILLIC SMALL LETTER OMEGA WITH TITLO             */
            "\xD1\xBE" => "\xD1\xBF"    ,  /* U+047E: CYRILLIC CAPITAL LETTER OT                               -> U+047F: CYRILLIC SMALL LETTER OT                           */
            "\xD2\x80" => "\xD2\x81"    ,  /* U+0480: CYRILLIC CAPITAL LETTER KOPPA                            -> U+0481: CYRILLIC SMALL LETTER KOPPA                        */
            "\xD2\x8A" => "\xD2\x8B"    ,  /* U+048A: CYRILLIC CAPITAL LETTER SHORT I WITH TAIL                -> U+048B: CYRILLIC SMALL LETTER SHORT I WITH TAIL            */
            "\xD2\x8C" => "\xD2\x8D"    ,  /* U+048C: CYRILLIC CAPITAL LETTER SEMISOFT SIGN                    -> U+048D: CYRILLIC SMALL LETTER SEMISOFT SIGN                */
            "\xD2\x8E" => "\xD2\x8F"    ,  /* U+048E: CYRILLIC CAPITAL LETTER ER WITH TICK                     -> U+048F: CYRILLIC SMALL LETTER ER WITH TICK                 */
            "\xD2\x90" => "\xD2\x91"    ,  /* U+0490: CYRILLIC CAPITAL LETTER GHE WITH UPTURN                  -> U+0491: CYRILLIC SMALL LETTER GHE WITH UPTURN              */
            "\xD2\x92" => "\xD2\x93"    ,  /* U+0492: CYRILLIC CAPITAL LETTER GHE WITH STROKE                  -> U+0493: CYRILLIC SMALL LETTER GHE WITH STROKE              */
            "\xD2\x94" => "\xD2\x95"    ,  /* U+0494: CYRILLIC CAPITAL LETTER GHE WITH MIDDLE HOOK             -> U+0495: CYRILLIC SMALL LETTER GHE WITH MIDDLE HOOK         */
            "\xD2\x96" => "\xD2\x97"    ,  /* U+0496: CYRILLIC CAPITAL LETTER ZHE WITH DESCENDER               -> U+0497: CYRILLIC SMALL LETTER ZHE WITH DESCENDER           */
            "\xD2\x98" => "\xD2\x99"    ,  /* U+0498: CYRILLIC CAPITAL LETTER ZE WITH DESCENDER                -> U+0499: CYRILLIC SMALL LETTER ZE WITH DESCENDER            */
            "\xD2\x9A" => "\xD2\x9B"    ,  /* U+049A: CYRILLIC CAPITAL LETTER KA WITH DESCENDER                -> U+049B: CYRILLIC SMALL LETTER KA WITH DESCENDER            */
            "\xD2\x9C" => "\xD2\x9D"    ,  /* U+049C: CYRILLIC CAPITAL LETTER KA WITH VERTICAL STROKE          -> U+049D: CYRILLIC SMALL LETTER KA WITH VERTICAL STROKE      */
            "\xD2\x9E" => "\xD2\x9F"    ,  /* U+049E: CYRILLIC CAPITAL LETTER KA WITH STROKE                   -> U+049F: CYRILLIC SMALL LETTER KA WITH STROKE               */
            "\xD2\xA0" => "\xD2\xA1"    ,  /* U+04A0: CYRILLIC CAPITAL LETTER BASHKIR KA                       -> U+04A1: CYRILLIC SMALL LETTER BASHKIR KA                   */
            "\xD2\xA2" => "\xD2\xA3"    ,  /* U+04A2: CYRILLIC CAPITAL LETTER EN WITH DESCENDER                -> U+04A3: CYRILLIC SMALL LETTER EN WITH DESCENDER            */
            "\xD2\xA4" => "\xD2\xA5"    ,  /* U+04A4: CYRILLIC CAPITAL LIGATURE EN GHE                         -> U+04A5: CYRILLIC SMALL LIGATURE EN GHE                     */
            "\xD2\xA6" => "\xD2\xA7"    ,  /* U+04A6: CYRILLIC CAPITAL LETTER PE WITH MIDDLE HOOK              -> U+04A7: CYRILLIC SMALL LETTER PE WITH MIDDLE HOOK          */
            "\xD2\xA8" => "\xD2\xA9"    ,  /* U+04A8: CYRILLIC CAPITAL LETTER ABKHASIAN HA                     -> U+04A9: CYRILLIC SMALL LETTER ABKHASIAN HA                 */
            "\xD2\xAA" => "\xD2\xAB"    ,  /* U+04AA: CYRILLIC CAPITAL LETTER ES WITH DESCENDER                -> U+04AB: CYRILLIC SMALL LETTER ES WITH DESCENDER            */
            "\xD2\xAC" => "\xD2\xAD"    ,  /* U+04AC: CYRILLIC CAPITAL LETTER TE WITH DESCENDER                -> U+04AD: CYRILLIC SMALL LETTER TE WITH DESCENDER            */
            "\xD2\xAE" => "\xD2\xAF"    ,  /* U+04AE: CYRILLIC CAPITAL LETTER STRAIGHT U                       -> U+04AF: CYRILLIC SMALL LETTER STRAIGHT U                   */
            "\xD2\xB0" => "\xD2\xB1"    ,  /* U+04B0: CYRILLIC CAPITAL LETTER STRAIGHT U WITH STROKE           -> U+04B1: CYRILLIC SMALL LETTER STRAIGHT U WITH STROKE       */
            "\xD2\xB2" => "\xD2\xB3"    ,  /* U+04B2: CYRILLIC CAPITAL LETTER HA WITH DESCENDER                -> U+04B3: CYRILLIC SMALL LETTER HA WITH DESCENDER            */
            "\xD2\xB4" => "\xD2\xB5"    ,  /* U+04B4: CYRILLIC CAPITAL LIGATURE TE TSE                         -> U+04B5: CYRILLIC SMALL LIGATURE TE TSE                     */
            "\xD2\xB6" => "\xD2\xB7"    ,  /* U+04B6: CYRILLIC CAPITAL LETTER CHE WITH DESCENDER               -> U+04B7: CYRILLIC SMALL LETTER CHE WITH DESCENDER           */
            "\xD2\xB8" => "\xD2\xB9"    ,  /* U+04B8: CYRILLIC CAPITAL LETTER CHE WITH VERTICAL STROKE         -> U+04B9: CYRILLIC SMALL LETTER CHE WITH VERTICAL STROKE     */
            "\xD2\xBA" => "\xD2\xBB"    ,  /* U+04BA: CYRILLIC CAPITAL LETTER SHHA                             -> U+04BB: CYRILLIC SMALL LETTER SHHA                         */
            "\xD2\xBC" => "\xD2\xBD"    ,  /* U+04BC: CYRILLIC CAPITAL LETTER ABKHASIAN CHE                    -> U+04BD: CYRILLIC SMALL LETTER ABKHASIAN CHE                */
            "\xD2\xBE" => "\xD2\xBF"    ,  /* U+04BE: CYRILLIC CAPITAL LETTER ABKHASIAN CHE WITH DESCENDER     -> U+04BF: CYRILLIC SMALL LETTER ABKHASIAN CHE WITH DESCENDER */
            "\xD3\x80" => "\xD3\x8F"    ,  /* U+04C0: CYRILLIC LETTER PALOCHKA                                 -> U+04CF: CYRILLIC SMALL LETTER PALOCHKA                     */
            "\xD3\x81" => "\xD0\xB6"    ,  /* U+04C1: CYRILLIC CAPITAL LETTER ZHE WITH BREVE                   -> U+0436: CYRILLIC SMALL LETTER ZHE                          */
            "\xD3\x82" => "\xD0\xB6"    ,  /* U+04C2: CYRILLIC SMALL LETTER ZHE WITH BREVE                     -> U+0436: CYRILLIC SMALL LETTER ZHE                          */
            "\xD3\x83" => "\xD3\x84"    ,  /* U+04C3: CYRILLIC CAPITAL LETTER KA WITH HOOK                     -> U+04C4: CYRILLIC SMALL LETTER KA WITH HOOK                 */
            "\xD3\x85" => "\xD3\x86"    ,  /* U+04C5: CYRILLIC CAPITAL LETTER EL WITH TAIL                     -> U+04C6: CYRILLIC SMALL LETTER EL WITH TAIL                 */
            "\xD3\x87" => "\xD3\x88"    ,  /* U+04C7: CYRILLIC CAPITAL LETTER EN WITH HOOK                     -> U+04C8: CYRILLIC SMALL LETTER EN WITH HOOK                 */
            "\xD3\x89" => "\xD3\x8A"    ,  /* U+04C9: CYRILLIC CAPITAL LETTER EN WITH TAIL                     -> U+04CA: CYRILLIC SMALL LETTER EN WITH TAIL                 */
            "\xD3\x8B" => "\xD3\x8C"    ,  /* U+04CB: CYRILLIC CAPITAL LETTER KHAKASSIAN CHE                   -> U+04CC: CYRILLIC SMALL LETTER KHAKASSIAN CHE               */
            "\xD3\x8D" => "\xD3\x8E"    ,  /* U+04CD: CYRILLIC CAPITAL LETTER EM WITH TAIL                     -> U+04CE: CYRILLIC SMALL LETTER EM WITH TAIL                 */
            "\xD3\x90" => "\xD0\xB0"    ,  /* U+04D0: CYRILLIC CAPITAL LETTER A WITH BREVE                     -> U+0430: CYRILLIC SMALL LETTER A                            */
            "\xD3\x91" => "\xD0\xB0"    ,  /* U+04D1: CYRILLIC SMALL LETTER A WITH BREVE                       -> U+0430: CYRILLIC SMALL LETTER A                            */
            "\xD3\x92" => "\xD0\xB0"    ,  /* U+04D2: CYRILLIC CAPITAL LETTER A WITH DIAERESIS                 -> U+0430: CYRILLIC SMALL LETTER A                            */
            "\xD3\x93" => "\xD0\xB0"    ,  /* U+04D3: CYRILLIC SMALL LETTER A WITH DIAERESIS                   -> U+0430: CYRILLIC SMALL LETTER A                            */
            "\xD3\x94" => "\xD3\x95"    ,  /* U+04D4: CYRILLIC CAPITAL LIGATURE A IE                           -> U+04D5: CYRILLIC SMALL LIGATURE A IE                       */
            "\xD3\x96" => "\xD0\xB5"    ,  /* U+04D6: CYRILLIC CAPITAL LETTER IE WITH BREVE                    -> U+0435: CYRILLIC SMALL LETTER IE                           */
            "\xD3\x97" => "\xD0\xB5"    ,  /* U+04D7: CYRILLIC SMALL LETTER IE WITH BREVE                      -> U+0435: CYRILLIC SMALL LETTER IE                           */
            "\xD3\x98" => "\xD3\x99"    ,  /* U+04D8: CYRILLIC CAPITAL LETTER SCHWA                            -> U+04D9: CYRILLIC SMALL LETTER SCHWA                        */
            "\xD3\x9A" => "\xD3\x99"    ,  /* U+04DA: CYRILLIC CAPITAL LETTER SCHWA WITH DIAERESIS             -> U+04D9: CYRILLIC SMALL LETTER SCHWA                        */
            "\xD3\x9B" => "\xD3\x99"    ,  /* U+04DB: CYRILLIC SMALL LETTER SCHWA WITH DIAERESIS               -> U+04D9: CYRILLIC SMALL LETTER SCHWA                        */
            "\xD3\x9C" => "\xD0\xB6"    ,  /* U+04DC: CYRILLIC CAPITAL LETTER ZHE WITH DIAERESIS               -> U+0436: CYRILLIC SMALL LETTER ZHE                          */
            "\xD3\x9D" => "\xD0\xB6"    ,  /* U+04DD: CYRILLIC SMALL LETTER ZHE WITH DIAERESIS                 -> U+0436: CYRILLIC SMALL LETTER ZHE                          */
            "\xD3\x9E" => "\xD0\xB7"    ,  /* U+04DE: CYRILLIC CAPITAL LETTER ZE WITH DIAERESIS                -> U+0437: CYRILLIC SMALL LETTER ZE                           */
            "\xD3\x9F" => "\xD0\xB7"    ,  /* U+04DF: CYRILLIC SMALL LETTER ZE WITH DIAERESIS                  -> U+0437: CYRILLIC SMALL LETTER ZE                           */
            "\xD3\xA0" => "\xD3\xA1"    ,  /* U+04E0: CYRILLIC CAPITAL LETTER ABKHASIAN DZE                    -> U+04E1: CYRILLIC SMALL LETTER ABKHASIAN DZE                */
            "\xD3\xA2" => "\xD0\xB8"    ,  /* U+04E2: CYRILLIC CAPITAL LETTER I WITH MACRON                    -> U+0438: CYRILLIC SMALL LETTER I                            */
            "\xD3\xA3" => "\xD0\xB8"    ,  /* U+04E3: CYRILLIC SMALL LETTER I WITH MACRON                      -> U+0438: CYRILLIC SMALL LETTER I                            */
            "\xD3\xA4" => "\xD0\xB8"    ,  /* U+04E4: CYRILLIC CAPITAL LETTER I WITH DIAERESIS                 -> U+0438: CYRILLIC SMALL LETTER I                            */
            "\xD3\xA5" => "\xD0\xB8"    ,  /* U+04E5: CYRILLIC SMALL LETTER I WITH DIAERESIS                   -> U+0438: CYRILLIC SMALL LETTER I                            */
            "\xD3\xA6" => "\xD0\xBE"    ,  /* U+04E6: CYRILLIC CAPITAL LETTER O WITH DIAERESIS                 -> U+043E: CYRILLIC SMALL LETTER O                            */
            "\xD3\xA7" => "\xD0\xBE"    ,  /* U+04E7: CYRILLIC SMALL LETTER O WITH DIAERESIS                   -> U+043E: CYRILLIC SMALL LETTER O                            */
            "\xD3\xA8" => "\xD3\xA9"    ,  /* U+04E8: CYRILLIC CAPITAL LETTER BARRED O                         -> U+04E9: CYRILLIC SMALL LETTER BARRED O                     */
            "\xD3\xAA" => "\xD3\xA9"    ,  /* U+04EA: CYRILLIC CAPITAL LETTER BARRED O WITH DIAERESIS          -> U+04E9: CYRILLIC SMALL LETTER BARRED O                     */
            "\xD3\xAB" => "\xD3\xA9"    ,  /* U+04EB: CYRILLIC SMALL LETTER BARRED O WITH DIAERESIS            -> U+04E9: CYRILLIC SMALL LETTER BARRED O                     */
            "\xD3\xAC" => "\xD1\x8D"    ,  /* U+04EC: CYRILLIC CAPITAL LETTER E WITH DIAERESIS                 -> U+044D: CYRILLIC SMALL LETTER E                            */
            "\xD3\xAD" => "\xD1\x8D"    ,  /* U+04ED: CYRILLIC SMALL LETTER E WITH DIAERESIS                   -> U+044D: CYRILLIC SMALL LETTER E                            */
            "\xD3\xAE" => "\xD1\x83"    ,  /* U+04EE: CYRILLIC CAPITAL LETTER U WITH MACRON                    -> U+0443: CYRILLIC SMALL LETTER U                            */
            "\xD3\xAF" => "\xD1\x83"    ,  /* U+04EF: CYRILLIC SMALL LETTER U WITH MACRON                      -> U+0443: CYRILLIC SMALL LETTER U                            */
            "\xD3\xB0" => "\xD1\x83"    ,  /* U+04F0: CYRILLIC CAPITAL LETTER U WITH DIAERESIS                 -> U+0443: CYRILLIC SMALL LETTER U                            */
            "\xD3\xB1" => "\xD1\x83"    ,  /* U+04F1: CYRILLIC SMALL LETTER U WITH DIAERESIS                   -> U+0443: CYRILLIC SMALL LETTER U                            */
            "\xD3\xB2" => "\xD1\x83"    ,  /* U+04F2: CYRILLIC CAPITAL LETTER U WITH DOUBLE ACUTE              -> U+0443: CYRILLIC SMALL LETTER U                            */
            "\xD3\xB3" => "\xD1\x83"    ,  /* U+04F3: CYRILLIC SMALL LETTER U WITH DOUBLE ACUTE                -> U+0443: CYRILLIC SMALL LETTER U                            */
            "\xD3\xB4" => "\xD1\x87"    ,  /* U+04F4: CYRILLIC CAPITAL LETTER CHE WITH DIAERESIS               -> U+0447: CYRILLIC SMALL LETTER CHE                          */
            "\xD3\xB5" => "\xD1\x87"    ,  /* U+04F5: CYRILLIC SMALL LETTER CHE WITH DIAERESIS                 -> U+0447: CYRILLIC SMALL LETTER CHE                          */
            "\xD3\xB6" => "\xD3\xB7"    ,  /* U+04F6: CYRILLIC CAPITAL LETTER GHE WITH DESCENDER               -> U+04F7: CYRILLIC SMALL LETTER GHE WITH DESCENDER           */
            "\xD3\xB8" => "\xD1\x8B"    ,  /* U+04F8: CYRILLIC CAPITAL LETTER YERU WITH DIAERESIS              -> U+044B: CYRILLIC SMALL LETTER YERU                         */
            "\xD3\xB9" => "\xD1\x8B"    ,  /* U+04F9: CYRILLIC SMALL LETTER YERU WITH DIAERESIS                -> U+044B: CYRILLIC SMALL LETTER YERU                         */
            "\xD3\xBA" => "\xD3\xBB"    ,  /* U+04FA: CYRILLIC CAPITAL LETTER GHE WITH STROKE AND HOOK         -> U+04FB: CYRILLIC SMALL LETTER GHE WITH STROKE AND HOOK     */
            "\xD3\xBC" => "\xD3\xBD"    ,  /* U+04FC: CYRILLIC CAPITAL LETTER HA WITH HOOK                     -> U+04FD: CYRILLIC SMALL LETTER HA WITH HOOK                 */
            "\xD3\xBE" => "\xD3\xBF"    ,  /* U+04FE: CYRILLIC CAPITAL LETTER HA WITH STROKE                   -> U+04FF: CYRILLIC SMALL LETTER HA WITH STROKE               */
            "\xD4\x80" => "\xD4\x81"    ,  /* U+0500: CYRILLIC CAPITAL LETTER KOMI DE                          -> U+0501: CYRILLIC SMALL LETTER KOMI DE                      */
            "\xD4\x82" => "\xD4\x83"    ,  /* U+0502: CYRILLIC CAPITAL LETTER KOMI DJE                         -> U+0503: CYRILLIC SMALL LETTER KOMI DJE                     */
            "\xD4\x84" => "\xD4\x85"    ,  /* U+0504: CYRILLIC CAPITAL LETTER KOMI ZJE                         -> U+0505: CYRILLIC SMALL LETTER KOMI ZJE                     */
            "\xD4\x86" => "\xD4\x87"    ,  /* U+0506: CYRILLIC CAPITAL LETTER KOMI DZJE                        -> U+0507: CYRILLIC SMALL LETTER KOMI DZJE                    */
            "\xD4\x88" => "\xD4\x89"    ,  /* U+0508: CYRILLIC CAPITAL LETTER KOMI LJE                         -> U+0509: CYRILLIC SMALL LETTER KOMI LJE                     */
            "\xD4\x8A" => "\xD4\x8B"    ,  /* U+050A: CYRILLIC CAPITAL LETTER KOMI NJE                         -> U+050B: CYRILLIC SMALL LETTER KOMI NJE                     */
            "\xD4\x8C" => "\xD4\x8D"    ,  /* U+050C: CYRILLIC CAPITAL LETTER KOMI SJE                         -> U+050D: CYRILLIC SMALL LETTER KOMI SJE                     */
            "\xD4\x8E" => "\xD4\x8F"    ,  /* U+050E: CYRILLIC CAPITAL LETTER KOMI TJE                         -> U+050F: CYRILLIC SMALL LETTER KOMI TJE                     */
            "\xD4\x90" => "\xD4\x91"    ,  /* U+0510: CYRILLIC CAPITAL LETTER REVERSED ZE                      -> U+0511: CYRILLIC SMALL LETTER REVERSED ZE                  */
            "\xD4\x92" => "\xD4\x93"    ,  /* U+0512: CYRILLIC CAPITAL LETTER EL WITH HOOK                     -> U+0513: CYRILLIC SMALL LETTER EL WITH HOOK                 */
            "\xD4\x94" => "\xD4\x95"    ,  /* U+0514: CYRILLIC CAPITAL LETTER LHA                              -> U+0515: CYRILLIC SMALL LETTER LHA                          */
            "\xD4\x96" => "\xD4\x97"    ,  /* U+0516: CYRILLIC CAPITAL LETTER RHA                              -> U+0517: CYRILLIC SMALL LETTER RHA                          */
            "\xD4\x98" => "\xD4\x99"    ,  /* U+0518: CYRILLIC CAPITAL LETTER YAE                              -> U+0519: CYRILLIC SMALL LETTER YAE                          */
            "\xD4\x9A" => "\xD4\x9B"    ,  /* U+051A: CYRILLIC CAPITAL LETTER QA                               -> U+051B: CYRILLIC SMALL LETTER QA                           */
            "\xD4\x9C" => "\xD4\x9D"    ,  /* U+051C: CYRILLIC CAPITAL LETTER WE                               -> U+051D: CYRILLIC SMALL LETTER WE                           */
            "\xD4\x9E" => "\xD4\x9F"    ,  /* U+051E: CYRILLIC CAPITAL LETTER ALEUT KA                         -> U+051F: CYRILLIC SMALL LETTER ALEUT KA                     */
            "\xD4\xA0" => "\xD4\xA1"    ,  /* U+0520: CYRILLIC CAPITAL LETTER EL WITH MIDDLE HOOK              -> U+0521: CYRILLIC SMALL LETTER EL WITH MIDDLE HOOK          */
            "\xD4\xA2" => "\xD4\xA3"    ,  /* U+0522: CYRILLIC CAPITAL LETTER EN WITH MIDDLE HOOK              -> U+0523: CYRILLIC SMALL LETTER EN WITH MIDDLE HOOK          */
            "\xD4\xA4" => "\xD4\xA5"    ,  /* U+0524: CYRILLIC CAPITAL LETTER PE WITH DESCENDER                -> U+0525: CYRILLIC SMALL LETTER PE WITH DESCENDER            */
            "\xD4\xA6" => "\xD4\xA7"    ,  /* U+0526: CYRILLIC CAPITAL LETTER SHHA WITH DESCENDER              -> U+0527: CYRILLIC SMALL LETTER SHHA WITH DESCENDER          */
            "\xD4\xB1" => "\xD5\xA1"    ,  /* U+0531: ARMENIAN CAPITAL LETTER AYB                              -> U+0561: ARMENIAN SMALL LETTER AYB                          */
            "\xD4\xB2" => "\xD5\xA2"    ,  /* U+0532: ARMENIAN CAPITAL LETTER BEN                              -> U+0562: ARMENIAN SMALL LETTER BEN                          */
            "\xD4\xB3" => "\xD5\xA3"    ,  /* U+0533: ARMENIAN CAPITAL LETTER GIM                              -> U+0563: ARMENIAN SMALL LETTER GIM                          */
            "\xD4\xB4" => "\xD5\xA4"    ,  /* U+0534: ARMENIAN CAPITAL LETTER DA                               -> U+0564: ARMENIAN SMALL LETTER DA                           */
            "\xD4\xB5" => "\xD5\xA5"    ,  /* U+0535: ARMENIAN CAPITAL LETTER ECH                              -> U+0565: ARMENIAN SMALL LETTER ECH                          */
            "\xD4\xB6" => "\xD5\xA6"    ,  /* U+0536: ARMENIAN CAPITAL LETTER ZA                               -> U+0566: ARMENIAN SMALL LETTER ZA                           */
            "\xD4\xB7" => "\xD5\xA7"    ,  /* U+0537: ARMENIAN CAPITAL LETTER EH                               -> U+0567: ARMENIAN SMALL LETTER EH                           */
            "\xD4\xB8" => "\xD5\xA8"    ,  /* U+0538: ARMENIAN CAPITAL LETTER ET                               -> U+0568: ARMENIAN SMALL LETTER ET                           */
            "\xD4\xB9" => "\xD5\xA9"    ,  /* U+0539: ARMENIAN CAPITAL LETTER TO                               -> U+0569: ARMENIAN SMALL LETTER TO                           */
            "\xD4\xBA" => "\xD5\xAA"    ,  /* U+053A: ARMENIAN CAPITAL LETTER ZHE                              -> U+056A: ARMENIAN SMALL LETTER ZHE                          */
            "\xD4\xBB" => "\xD5\xAB"    ,  /* U+053B: ARMENIAN CAPITAL LETTER INI                              -> U+056B: ARMENIAN SMALL LETTER INI                          */
            "\xD4\xBC" => "\xD5\xAC"    ,  /* U+053C: ARMENIAN CAPITAL LETTER LIWN                             -> U+056C: ARMENIAN SMALL LETTER LIWN                         */
            "\xD4\xBD" => "\xD5\xAD"    ,  /* U+053D: ARMENIAN CAPITAL LETTER XEH                              -> U+056D: ARMENIAN SMALL LETTER XEH                          */
            "\xD4\xBE" => "\xD5\xAE"    ,  /* U+053E: ARMENIAN CAPITAL LETTER CA                               -> U+056E: ARMENIAN SMALL LETTER CA                           */
            "\xD4\xBF" => "\xD5\xAF"    ,  /* U+053F: ARMENIAN CAPITAL LETTER KEN                              -> U+056F: ARMENIAN SMALL LETTER KEN                          */
            "\xD5\x80" => "\xD5\xB0"    ,  /* U+0540: ARMENIAN CAPITAL LETTER HO                               -> U+0570: ARMENIAN SMALL LETTER HO                           */
            "\xD5\x81" => "\xD5\xB1"    ,  /* U+0541: ARMENIAN CAPITAL LETTER JA                               -> U+0571: ARMENIAN SMALL LETTER JA                           */
            "\xD5\x82" => "\xD5\xB2"    ,  /* U+0542: ARMENIAN CAPITAL LETTER GHAD                             -> U+0572: ARMENIAN SMALL LETTER GHAD                         */
            "\xD5\x83" => "\xD5\xB3"    ,  /* U+0543: ARMENIAN CAPITAL LETTER CHEH                             -> U+0573: ARMENIAN SMALL LETTER CHEH                         */
            "\xD5\x84" => "\xD5\xB4"    ,  /* U+0544: ARMENIAN CAPITAL LETTER MEN                              -> U+0574: ARMENIAN SMALL LETTER MEN                          */
            "\xD5\x85" => "\xD5\xB5"    ,  /* U+0545: ARMENIAN CAPITAL LETTER YI                               -> U+0575: ARMENIAN SMALL LETTER YI                           */
            "\xD5\x86" => "\xD5\xB6"    ,  /* U+0546: ARMENIAN CAPITAL LETTER NOW                              -> U+0576: ARMENIAN SMALL LETTER NOW                          */
            "\xD5\x87" => "\xD5\xB7"    ,  /* U+0547: ARMENIAN CAPITAL LETTER SHA                              -> U+0577: ARMENIAN SMALL LETTER SHA                          */
            "\xD5\x88" => "\xD5\xB8"    ,  /* U+0548: ARMENIAN CAPITAL LETTER VO                               -> U+0578: ARMENIAN SMALL LETTER VO                           */
            "\xD5\x89" => "\xD5\xB9"    ,  /* U+0549: ARMENIAN CAPITAL LETTER CHA                              -> U+0579: ARMENIAN SMALL LETTER CHA                          */
            "\xD5\x8A" => "\xD5\xBA"    ,  /* U+054A: ARMENIAN CAPITAL LETTER PEH                              -> U+057A: ARMENIAN SMALL LETTER PEH                          */
            "\xD5\x8B" => "\xD5\xBB"    ,  /* U+054B: ARMENIAN CAPITAL LETTER JHEH                             -> U+057B: ARMENIAN SMALL LETTER JHEH                         */
            "\xD5\x8C" => "\xD5\xBC"    ,  /* U+054C: ARMENIAN CAPITAL LETTER RA                               -> U+057C: ARMENIAN SMALL LETTER RA                           */
            "\xD5\x8D" => "\xD5\xBD"    ,  /* U+054D: ARMENIAN CAPITAL LETTER SEH                              -> U+057D: ARMENIAN SMALL LETTER SEH                          */
            "\xD5\x8E" => "\xD5\xBE"    ,  /* U+054E: ARMENIAN CAPITAL LETTER VEW                              -> U+057E: ARMENIAN SMALL LETTER VEW                          */
            "\xD5\x8F" => "\xD5\xBF"    ,  /* U+054F: ARMENIAN CAPITAL LETTER TIWN                             -> U+057F: ARMENIAN SMALL LETTER TIWN                         */
            "\xD5\x90" => "\xD6\x80"    ,  /* U+0550: ARMENIAN CAPITAL LETTER REH                              -> U+0580: ARMENIAN SMALL LETTER REH                          */
            "\xD5\x91" => "\xD6\x81"    ,  /* U+0551: ARMENIAN CAPITAL LETTER CO                               -> U+0581: ARMENIAN SMALL LETTER CO                           */
            "\xD5\x92" => "\xD6\x82"    ,  /* U+0552: ARMENIAN CAPITAL LETTER YIWN                             -> U+0582: ARMENIAN SMALL LETTER YIWN                         */
            "\xD5\x93" => "\xD6\x83"    ,  /* U+0553: ARMENIAN CAPITAL LETTER PIWR                             -> U+0583: ARMENIAN SMALL LETTER PIWR                         */
            "\xD5\x94" => "\xD6\x84"    ,  /* U+0554: ARMENIAN CAPITAL LETTER KEH                              -> U+0584: ARMENIAN SMALL LETTER KEH                          */
            "\xD5\x95" => "\xD6\x85"    ,  /* U+0555: ARMENIAN CAPITAL LETTER OH                               -> U+0585: ARMENIAN SMALL LETTER OH                           */
            "\xD5\x96" => "\xD6\x86"       /* U+0556: ARMENIAN CAPITAL LETTER FEH                              -> U+0586: ARMENIAN SMALL LETTER FEH                          */

        ];
    }

    protected $endCharacters_utf8 = "\t\r\n !\"#\$%&'()+,-./:;<=>@[\\]^_`{|}~£§¨°";

    /**
     * Converts a string
     *
     * @param string $string
     * @param string $target One of the unicode::CONVERT_TO_* constants
     *
     * @return string
     *
     * @throws Exception_InvalidArgument
     */
    public function convert($string, $target)
    {
        $ok_methods = [self::CONVERT_TO_LC, self::CONVERT_TO_ND, self::CONVERT_TO_LCND];

        if (!in_array($target, $ok_methods)) {
            throw new Exception_InvalidArgument(
                sprintf('Invalid argument 2 "%s", valid values are [%s].'
                    , $target
                    , implode('|', $ok_methods)
                )
            );
        }
        if (function_exists('phrasea_utf8_convert_to')) {
            return phrasea_utf8_convert_to($string, $target);
        }

        $out = '';
        $_map = $this->getMap($target);   // faster in loop
        $length = mb_strlen($string, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            if (true === array_key_exists(($c = mb_substr($string, $i, 1, 'UTF-8')), $_map)) {
                $out .= $_map[$c];
            } else {
                $out .= $c;
            }
        }

        return $out;
    }

    public function remove_indexer_chars($string)
    {
        $so = "";

        $string = $this->convert($string, static::CONVERT_TO_LCND);

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
        return $this->convert($string, static::CONVERT_TO_ND);
    }

    public function remove_nonazAZ09($string, $keep_underscores = true, $keep_minus = true, $keep_dot = false)
    {
        $string = $this->remove_diacritics($string);

        $out = '';
        $l = mb_strlen($string);
        for ($i = 0; $i < $l; $i ++) {
            $c = mb_substr($string, $i, 1);
            if(($c>='a'&&$c<='z')||($c>='A'&&$c<='Z')||($c>='0'&&$c<='9')
                ||($keep_underscores&&$c=='_')||($keep_dot&&$c=='.')||($keep_minus&&$c=='-')) {
                $out .= $c;
            }
        }

        return $out;
    }

    /**
     * Removes all digits a the begining of a string
     * @Example : returns 'soleil' for '123soleil' and 'bb2' for '1bb2'
     *
     * @param  string $string
     * @return string
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

            $chars_in = [];

            for ($cc = 0; $cc < 32; $cc ++) {
                if (in_array($cc, [9, 10, 13])) {
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
        $date = str_replace(['-', ':', '/', '.'], ' ', $date);
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
