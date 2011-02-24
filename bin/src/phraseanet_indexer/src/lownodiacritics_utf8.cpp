#include "lownodiacritics_utf8.h"

CMAP1 cmap_1[128] = {
	  { 0x00, CFLAG_NORMALCHAR                }	/* U+00 (0x00) unchanged ; <control>   */
	, { 0x01, CFLAG_NORMALCHAR                }	/* U+01 (0x01) unchanged ; <control>   */
	, { 0x02, CFLAG_NORMALCHAR                }	/* U+02 (0x02) unchanged ; <control>   */
	, { 0x03, CFLAG_NORMALCHAR                }	/* U+03 (0x03) unchanged ; <control>   */
	, { 0x04, CFLAG_NORMALCHAR                }	/* U+04 (0x04) unchanged ; <control>   */
	, { 0x05, CFLAG_NORMALCHAR                }	/* U+05 (0x05) unchanged ; <control>   */
	, { 0x06, CFLAG_NORMALCHAR                }	/* U+06 (0x06) unchanged ; <control>   */
	, { 0x07, CFLAG_NORMALCHAR                }	/* U+07 (0x07) unchanged ; <control>   */
	, { 0x08, CFLAG_NORMALCHAR                }	/* U+08 (0x08) unchanged ; <control>   */
	, { 0x09, CFLAG_ENDCHAR | CFLAG_SPACECHAR }	/* U+09 (0x09) unchanged ; <control>   */
	, { 0x0A, CFLAG_ENDCHAR | CFLAG_SPACECHAR }	/* U+0A (0x0A) unchanged ; <control>   */
	, { 0x0B, CFLAG_NORMALCHAR                }	/* U+0B (0x0B) unchanged ; <control>   */
	, { 0x0C, CFLAG_NORMALCHAR                }	/* U+0C (0x0C) unchanged ; <control>   */
	, { 0x0D, CFLAG_ENDCHAR | CFLAG_SPACECHAR }	/* U+0D (0x0D) unchanged ; <control>   */
	, { 0x0E, CFLAG_NORMALCHAR                }	/* U+0E (0x0E) unchanged ; <control>   */
	, { 0x0F, CFLAG_NORMALCHAR                }	/* U+0F (0x0F) unchanged ; <control>   */
	, { 0x10, CFLAG_NORMALCHAR                }	/* U+10 (0x10) unchanged ; <control>   */
	, { 0x11, CFLAG_NORMALCHAR                }	/* U+11 (0x11) unchanged ; <control>   */
	, { 0x12, CFLAG_NORMALCHAR                }	/* U+12 (0x12) unchanged ; <control>   */
	, { 0x13, CFLAG_NORMALCHAR                }	/* U+13 (0x13) unchanged ; <control>   */
	, { 0x14, CFLAG_NORMALCHAR                }	/* U+14 (0x14) unchanged ; <control>   */
	, { 0x15, CFLAG_NORMALCHAR                }	/* U+15 (0x15) unchanged ; <control>   */
	, { 0x16, CFLAG_NORMALCHAR                }	/* U+16 (0x16) unchanged ; <control>   */
	, { 0x17, CFLAG_NORMALCHAR                }	/* U+17 (0x17) unchanged ; <control>   */
	, { 0x18, CFLAG_NORMALCHAR                }	/* U+18 (0x18) unchanged ; <control>   */
	, { 0x19, CFLAG_NORMALCHAR                }	/* U+19 (0x19) unchanged ; <control>   */
	, { 0x1A, CFLAG_NORMALCHAR                }	/* U+1A (0x1A) unchanged ; <control>   */
	, { 0x1B, CFLAG_NORMALCHAR                }	/* U+1B (0x1B) unchanged ; <control>   */
	, { 0x1C, CFLAG_NORMALCHAR                }	/* U+1C (0x1C) unchanged ; <control>   */
	, { 0x1D, CFLAG_NORMALCHAR                }	/* U+1D (0x1D) unchanged ; <control>   */
	, { 0x1E, CFLAG_NORMALCHAR                }	/* U+1E (0x1E) unchanged ; <control>   */
	, { 0x1F, CFLAG_NORMALCHAR                }	/* U+1F (0x1F) unchanged ; <control>   */
	, { 0x20, CFLAG_ENDCHAR | CFLAG_SPACECHAR }	/* U+20 (0x20) unchanged ; SPACE   */
	, { 0x21, CFLAG_ENDCHAR                   }	/* U+21 (0x21) unchanged ; EXCLAMATION MARK   */
	, { 0x22, CFLAG_ENDCHAR                   }	/* U+22 (0x22) unchanged ; QUOTATION MARK   */
	, { 0x23, CFLAG_ENDCHAR                   }	/* U+23 (0x23) unchanged ; NUMBER SIGN   */
	, { 0x24, CFLAG_ENDCHAR                   }	/* U+24 (0x24) unchanged ; DOLLAR SIGN   */
	, { 0x25, CFLAG_ENDCHAR                   }	/* U+25 (0x25) unchanged ; PERCENT SIGN   */
	, { 0x26, CFLAG_ENDCHAR                   }	/* U+26 (0x26) unchanged ; AMPERSAND   */
	, { 0x27, CFLAG_ENDCHAR                   }	/* U+27 (0x27) unchanged ; APOSTROPHE   */
	, { 0x28, CFLAG_ENDCHAR                   }	/* U+28 (0x28) unchanged ; LEFT PARENTHESIS   */
	, { 0x29, CFLAG_ENDCHAR                   }	/* U+29 (0x29) unchanged ; RIGHT PARENTHESIS   */
	, { 0x2A, CFLAG_ENDCHAR                   }	/* U+2A (0x2A) unchanged ; ASTERISK   */
	, { 0x2B, CFLAG_ENDCHAR                   }	/* U+2B (0x2B) unchanged ; PLUS SIGN   */
	, { 0x2C, CFLAG_ENDCHAR                   }	/* U+2C (0x2C) unchanged ; COMMA   */
	, { 0x2D, CFLAG_ENDCHAR                   }	/* U+2D (0x2D) unchanged ; HYPHEN-MINUS   */
	, { 0x2E, CFLAG_ENDCHAR                   }	/* U+2E (0x2E) unchanged ; FULL STOP   */
	, { 0x2F, CFLAG_ENDCHAR                   }	/* U+2F (0x2F) unchanged ; SOLIDUS   */
	, { 0x30, CFLAG_NORMALCHAR                }	/* U+30 (0x30) unchanged ; DIGIT ZERO   */
	, { 0x31, CFLAG_NORMALCHAR                }	/* U+31 (0x31) unchanged ; DIGIT ONE   */
	, { 0x32, CFLAG_NORMALCHAR                }	/* U+32 (0x32) unchanged ; DIGIT TWO   */
	, { 0x33, CFLAG_NORMALCHAR                }	/* U+33 (0x33) unchanged ; DIGIT THREE   */
	, { 0x34, CFLAG_NORMALCHAR                }	/* U+34 (0x34) unchanged ; DIGIT FOUR   */
	, { 0x35, CFLAG_NORMALCHAR                }	/* U+35 (0x35) unchanged ; DIGIT FIVE   */
	, { 0x36, CFLAG_NORMALCHAR                }	/* U+36 (0x36) unchanged ; DIGIT SIX   */
	, { 0x37, CFLAG_NORMALCHAR                }	/* U+37 (0x37) unchanged ; DIGIT SEVEN   */
	, { 0x38, CFLAG_NORMALCHAR                }	/* U+38 (0x38) unchanged ; DIGIT EIGHT   */
	, { 0x39, CFLAG_NORMALCHAR                }	/* U+39 (0x39) unchanged ; DIGIT NINE   */
	, { 0x3A, CFLAG_ENDCHAR                   }	/* U+3A (0x3A) unchanged ; COLON   */
	, { 0x3B, CFLAG_ENDCHAR                   }	/* U+3B (0x3B) unchanged ; SEMICOLON   */
	, { 0x3C, CFLAG_ENDCHAR                   }	/* U+3C (0x3C) unchanged ; LESS-THAN SIGN   */
	, { 0x3D, CFLAG_ENDCHAR                   }	/* U+3D (0x3D) unchanged ; EQUALS SIGN   */
	, { 0x3E, CFLAG_ENDCHAR                   }	/* U+3E (0x3E) unchanged ; GREATER-THAN SIGN   */
	, { 0x3F, CFLAG_ENDCHAR                   }	/* U+3F (0x3F) unchanged ; QUESTION MARK   */
	, { 0x40, CFLAG_ENDCHAR                   }	/* U+40 (0x40) unchanged ; COMMERCIAL AT   */
	, { 0x61, CFLAG_NORMALCHAR                }	/* U+41 (0x41) -> 0x61 ; LATIN CAPITAL LETTER A   */
	, { 0x62, CFLAG_NORMALCHAR                }	/* U+42 (0x42) -> 0x62 ; LATIN CAPITAL LETTER B   */
	, { 0x63, CFLAG_NORMALCHAR                }	/* U+43 (0x43) -> 0x63 ; LATIN CAPITAL LETTER C   */
	, { 0x64, CFLAG_NORMALCHAR                }	/* U+44 (0x44) -> 0x64 ; LATIN CAPITAL LETTER D   */
	, { 0x65, CFLAG_NORMALCHAR                }	/* U+45 (0x45) -> 0x65 ; LATIN CAPITAL LETTER E   */
	, { 0x66, CFLAG_NORMALCHAR                }	/* U+46 (0x46) -> 0x66 ; LATIN CAPITAL LETTER F   */
	, { 0x67, CFLAG_NORMALCHAR                }	/* U+47 (0x47) -> 0x67 ; LATIN CAPITAL LETTER G   */
	, { 0x68, CFLAG_NORMALCHAR                }	/* U+48 (0x48) -> 0x68 ; LATIN CAPITAL LETTER H   */
	, { 0x69, CFLAG_NORMALCHAR                }	/* U+49 (0x49) -> 0x69 ; LATIN CAPITAL LETTER I   */
	, { 0x6A, CFLAG_NORMALCHAR                }	/* U+4A (0x4A) -> 0x6A ; LATIN CAPITAL LETTER J   */
	, { 0x6B, CFLAG_NORMALCHAR                }	/* U+4B (0x4B) -> 0x6B ; LATIN CAPITAL LETTER K   */
	, { 0x6C, CFLAG_NORMALCHAR                }	/* U+4C (0x4C) -> 0x6C ; LATIN CAPITAL LETTER L   */
	, { 0x6D, CFLAG_NORMALCHAR                }	/* U+4D (0x4D) -> 0x6D ; LATIN CAPITAL LETTER M   */
	, { 0x6E, CFLAG_NORMALCHAR                }	/* U+4E (0x4E) -> 0x6E ; LATIN CAPITAL LETTER N   */
	, { 0x6F, CFLAG_NORMALCHAR                }	/* U+4F (0x4F) -> 0x6F ; LATIN CAPITAL LETTER O   */
	, { 0x70, CFLAG_NORMALCHAR                }	/* U+50 (0x50) -> 0x70 ; LATIN CAPITAL LETTER P   */
	, { 0x71, CFLAG_NORMALCHAR                }	/* U+51 (0x51) -> 0x71 ; LATIN CAPITAL LETTER Q   */
	, { 0x72, CFLAG_NORMALCHAR                }	/* U+52 (0x52) -> 0x72 ; LATIN CAPITAL LETTER R   */
	, { 0x73, CFLAG_NORMALCHAR                }	/* U+53 (0x53) -> 0x73 ; LATIN CAPITAL LETTER S   */
	, { 0x74, CFLAG_NORMALCHAR                }	/* U+54 (0x54) -> 0x74 ; LATIN CAPITAL LETTER T   */
	, { 0x75, CFLAG_NORMALCHAR                }	/* U+55 (0x55) -> 0x75 ; LATIN CAPITAL LETTER U   */
	, { 0x76, CFLAG_NORMALCHAR                }	/* U+56 (0x56) -> 0x76 ; LATIN CAPITAL LETTER V   */
	, { 0x77, CFLAG_NORMALCHAR                }	/* U+57 (0x57) -> 0x77 ; LATIN CAPITAL LETTER W   */
	, { 0x78, CFLAG_NORMALCHAR                }	/* U+58 (0x58) -> 0x78 ; LATIN CAPITAL LETTER X   */
	, { 0x79, CFLAG_NORMALCHAR                }	/* U+59 (0x59) -> 0x79 ; LATIN CAPITAL LETTER Y   */
	, { 0x7A, CFLAG_NORMALCHAR                }	/* U+5A (0x5A) -> 0x7A ; LATIN CAPITAL LETTER Z   */
	, { 0x5B, CFLAG_ENDCHAR                   }	/* U+5B (0x5B) unchanged ; LEFT SQUARE BRACKET   */
	, { 0x5C, CFLAG_ENDCHAR                   }	/* U+5C (0x5C) unchanged ; REVERSE SOLIDUS   */
	, { 0x5D, CFLAG_ENDCHAR                   }	/* U+5D (0x5D) unchanged ; RIGHT SQUARE BRACKET   */
	, { 0x5E, CFLAG_ENDCHAR                   }	/* U+5E (0x5E) unchanged ; CIRCUMFLEX ACCENT   */
	, { 0x5F, CFLAG_ENDCHAR                   }	/* U+5F (0x5F) unchanged ; LOW LINE   */
	, { 0x60, CFLAG_ENDCHAR                   }	/* U+60 (0x60) unchanged ; GRAVE ACCENT   */
	, { 0x61, CFLAG_NORMALCHAR                }	/* U+61 (0x61) unchanged ; LATIN SMALL LETTER A   */
	, { 0x62, CFLAG_NORMALCHAR                }	/* U+62 (0x62) unchanged ; LATIN SMALL LETTER B   */
	, { 0x63, CFLAG_NORMALCHAR                }	/* U+63 (0x63) unchanged ; LATIN SMALL LETTER C   */
	, { 0x64, CFLAG_NORMALCHAR                }	/* U+64 (0x64) unchanged ; LATIN SMALL LETTER D   */
	, { 0x65, CFLAG_NORMALCHAR                }	/* U+65 (0x65) unchanged ; LATIN SMALL LETTER E   */
	, { 0x66, CFLAG_NORMALCHAR                }	/* U+66 (0x66) unchanged ; LATIN SMALL LETTER F   */
	, { 0x67, CFLAG_NORMALCHAR                }	/* U+67 (0x67) unchanged ; LATIN SMALL LETTER G   */
	, { 0x68, CFLAG_NORMALCHAR                }	/* U+68 (0x68) unchanged ; LATIN SMALL LETTER H   */
	, { 0x69, CFLAG_NORMALCHAR                }	/* U+69 (0x69) unchanged ; LATIN SMALL LETTER I   */
	, { 0x6A, CFLAG_NORMALCHAR                }	/* U+6A (0x6A) unchanged ; LATIN SMALL LETTER J   */
	, { 0x6B, CFLAG_NORMALCHAR                }	/* U+6B (0x6B) unchanged ; LATIN SMALL LETTER K   */
	, { 0x6C, CFLAG_NORMALCHAR                }	/* U+6C (0x6C) unchanged ; LATIN SMALL LETTER L   */
	, { 0x6D, CFLAG_NORMALCHAR                }	/* U+6D (0x6D) unchanged ; LATIN SMALL LETTER M   */
	, { 0x6E, CFLAG_NORMALCHAR                }	/* U+6E (0x6E) unchanged ; LATIN SMALL LETTER N   */
	, { 0x6F, CFLAG_NORMALCHAR                }	/* U+6F (0x6F) unchanged ; LATIN SMALL LETTER O   */
	, { 0x70, CFLAG_NORMALCHAR                }	/* U+70 (0x70) unchanged ; LATIN SMALL LETTER P   */
	, { 0x71, CFLAG_NORMALCHAR                }	/* U+71 (0x71) unchanged ; LATIN SMALL LETTER Q   */
	, { 0x72, CFLAG_NORMALCHAR                }	/* U+72 (0x72) unchanged ; LATIN SMALL LETTER R   */
	, { 0x73, CFLAG_NORMALCHAR                }	/* U+73 (0x73) unchanged ; LATIN SMALL LETTER S   */
	, { 0x74, CFLAG_NORMALCHAR                }	/* U+74 (0x74) unchanged ; LATIN SMALL LETTER T   */
	, { 0x75, CFLAG_NORMALCHAR                }	/* U+75 (0x75) unchanged ; LATIN SMALL LETTER U   */
	, { 0x76, CFLAG_NORMALCHAR                }	/* U+76 (0x76) unchanged ; LATIN SMALL LETTER V   */
	, { 0x77, CFLAG_NORMALCHAR                }	/* U+77 (0x77) unchanged ; LATIN SMALL LETTER W   */
	, { 0x78, CFLAG_NORMALCHAR                }	/* U+78 (0x78) unchanged ; LATIN SMALL LETTER X   */
	, { 0x79, CFLAG_NORMALCHAR                }	/* U+79 (0x79) unchanged ; LATIN SMALL LETTER Y   */
	, { 0x7A, CFLAG_NORMALCHAR                }	/* U+7A (0x7A) unchanged ; LATIN SMALL LETTER Z   */
	, { 0x7B, CFLAG_ENDCHAR                   }	/* U+7B (0x7B) unchanged ; LEFT CURLY BRACKET   */
	, { 0x7C, CFLAG_ENDCHAR                   }	/* U+7C (0x7C) unchanged ; VERTICAL LINE   */
	, { 0x7D, CFLAG_ENDCHAR                   }	/* U+7D (0x7D) unchanged ; RIGHT CURLY BRACKET   */
	, { 0x7E, CFLAG_ENDCHAR                   }	/* U+7E (0x7E) unchanged ; TILDE   */
	, { 0x7F, CFLAG_NORMALCHAR                }	/* U+7F (0x7F) unchanged ; <control>   */
 };


CMAP2 cmap_2[1920] = {
	  { (unsigned char *)"\xC2\x80" , CFLAG_NORMALCHAR }	/* U+80 (0xC280) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x81" , CFLAG_NORMALCHAR }	/* U+81 (0xC281) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x82" , CFLAG_NORMALCHAR }	/* U+82 (0xC282) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x83" , CFLAG_NORMALCHAR }	/* U+83 (0xC283) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x84" , CFLAG_NORMALCHAR }	/* U+84 (0xC284) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x85" , CFLAG_NORMALCHAR }	/* U+85 (0xC285) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x86" , CFLAG_NORMALCHAR }	/* U+86 (0xC286) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x87" , CFLAG_NORMALCHAR }	/* U+87 (0xC287) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x88" , CFLAG_NORMALCHAR }	/* U+88 (0xC288) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x89" , CFLAG_NORMALCHAR }	/* U+89 (0xC289) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x8A" , CFLAG_NORMALCHAR }	/* U+8A (0xC28A) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x8B" , CFLAG_NORMALCHAR }	/* U+8B (0xC28B) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x8C" , CFLAG_NORMALCHAR }	/* U+8C (0xC28C) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x8D" , CFLAG_NORMALCHAR }	/* U+8D (0xC28D) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x8E" , CFLAG_NORMALCHAR }	/* U+8E (0xC28E) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x8F" , CFLAG_NORMALCHAR }	/* U+8F (0xC28F) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x90" , CFLAG_NORMALCHAR }	/* U+90 (0xC290) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x91" , CFLAG_NORMALCHAR }	/* U+91 (0xC291) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x92" , CFLAG_NORMALCHAR }	/* U+92 (0xC292) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x93" , CFLAG_NORMALCHAR }	/* U+93 (0xC293) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x94" , CFLAG_NORMALCHAR }	/* U+94 (0xC294) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x95" , CFLAG_NORMALCHAR }	/* U+95 (0xC295) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x96" , CFLAG_NORMALCHAR }	/* U+96 (0xC296) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x97" , CFLAG_NORMALCHAR }	/* U+97 (0xC297) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x98" , CFLAG_NORMALCHAR }	/* U+98 (0xC298) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x99" , CFLAG_NORMALCHAR }	/* U+99 (0xC299) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x9A" , CFLAG_NORMALCHAR }	/* U+9A (0xC29A) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x9B" , CFLAG_NORMALCHAR }	/* U+9B (0xC29B) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x9C" , CFLAG_NORMALCHAR }	/* U+9C (0xC29C) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x9D" , CFLAG_NORMALCHAR }	/* U+9D (0xC29D) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x9E" , CFLAG_NORMALCHAR }	/* U+9E (0xC29E) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\x9F" , CFLAG_NORMALCHAR }	/* U+9F (0xC29F) unchanged ; <control>   */
	, { (unsigned char *)"\xC2\xA0" , CFLAG_ENDCHAR    }	/* U+A0 (0xC2A0) unchanged ; NO-BREAK SPACE   */
	, { (unsigned char *)"\xC2\xA1" , CFLAG_ENDCHAR    }	/* U+A1 (0xC2A1) unchanged ; INVERTED EXCLAMATION MARK   */
	, { (unsigned char *)"\xC2\xA2" , CFLAG_ENDCHAR    }	/* U+A2 (0xC2A2) unchanged ; CENT SIGN   */
	, { (unsigned char *)"\xC2\xA3" , CFLAG_ENDCHAR    }	/* U+A3 (0xC2A3) unchanged ; POUND SIGN   */
	, { (unsigned char *)"\xC2\xA4" , CFLAG_ENDCHAR    }	/* U+A4 (0xC2A4) unchanged ; CURRENCY SIGN   */
	, { (unsigned char *)"\xC2\xA5" , CFLAG_ENDCHAR    }	/* U+A5 (0xC2A5) unchanged ; YEN SIGN   */
	, { (unsigned char *)"\xC2\xA6" , CFLAG_ENDCHAR    }	/* U+A6 (0xC2A6) unchanged ; BROKEN BAR   */
	, { (unsigned char *)"\xC2\xA7" , CFLAG_ENDCHAR    }	/* U+A7 (0xC2A7) unchanged ; SECTION SIGN   */
	, { (unsigned char *)"\xC2\xA8" , CFLAG_ENDCHAR    }	/* U+A8 (0xC2A8) unchanged ; DIAERESIS   */
	, { (unsigned char *)"\xC2\xA9" , CFLAG_ENDCHAR    }	/* U+A9 (0xC2A9) unchanged ; COPYRIGHT SIGN   */
	, { (unsigned char *)"\xC2\xAA" , CFLAG_ENDCHAR    }	/* U+AA (0xC2AA) unchanged ; FEMININE ORDINAL INDICATOR   */
	, { (unsigned char *)"\xC2\xAB" , CFLAG_ENDCHAR    }	/* U+AB (0xC2AB) unchanged ; LEFT-POINTING DOUBLE ANGLE QUOTATION MARK   */
	, { (unsigned char *)"\xC2\xAC" , CFLAG_ENDCHAR    }	/* U+AC (0xC2AC) unchanged ; NOT SIGN   */
	, { (unsigned char *)"\xC2\xAD" , CFLAG_ENDCHAR    }	/* U+AD (0xC2AD) unchanged ; SOFT HYPHEN   */
	, { (unsigned char *)"\xC2\xAE" , CFLAG_ENDCHAR    }	/* U+AE (0xC2AE) unchanged ; REGISTERED SIGN   */
	, { (unsigned char *)"\xC2\xAF" , CFLAG_NORMALCHAR }	/* U+AF (0xC2AF) unchanged ; MACRON   */
	, { (unsigned char *)"\xC2\xB0" , CFLAG_ENDCHAR    }	/* U+B0 (0xC2B0) unchanged ; DEGREE SIGN   */
	, { (unsigned char *)"\xC2\xB1" , CFLAG_ENDCHAR    }	/* U+B1 (0xC2B1) unchanged ; PLUS-MINUS SIGN   */
	, { (unsigned char *)"\xC2\xB2" , CFLAG_ENDCHAR    }	/* U+B2 (0xC2B2) unchanged ; SUPERSCRIPT TWO   */
	, { (unsigned char *)"\xC2\xB3" , CFLAG_ENDCHAR    }	/* U+B3 (0xC2B3) unchanged ; SUPERSCRIPT THREE   */
	, { (unsigned char *)"\xC2\xB4" , CFLAG_ENDCHAR    }	/* U+B4 (0xC2B4) unchanged ; ACUTE ACCENT   */
	, { (unsigned char *)"\xC2\xB5" , CFLAG_ENDCHAR    }	/* U+B5 (0xC2B5) unchanged ; MICRO SIGN   */
	, { (unsigned char *)"\xC2\xB6" , CFLAG_ENDCHAR    }	/* U+B6 (0xC2B6) unchanged ; PILCROW SIGN   */
	, { (unsigned char *)"\xC2\xB7" , CFLAG_NORMALCHAR }	/* U+B7 (0xC2B7) unchanged ; MIDDLE DOT   */
	, { (unsigned char *)"\xC2\xB8" , CFLAG_ENDCHAR    }	/* U+B8 (0xC2B8) unchanged ; CEDILLA   */
	, { (unsigned char *)"\xC2\xB9" , CFLAG_ENDCHAR    }	/* U+B9 (0xC2B9) unchanged ; SUPERSCRIPT ONE   */
	, { (unsigned char *)"\xC2\xBA" , CFLAG_ENDCHAR    }	/* U+BA (0xC2BA) unchanged ; MASCULINE ORDINAL INDICATOR   */
	, { (unsigned char *)"\xC2\xBB" , CFLAG_ENDCHAR    }	/* U+BB (0xC2BB) unchanged ; RIGHT-POINTING DOUBLE ANGLE QUOTATION MARK   */
	, { (unsigned char *)"\xC2\xBC" , CFLAG_ENDCHAR    }	/* U+BC (0xC2BC) unchanged ; VULGAR FRACTION ONE QUARTER   */
	, { (unsigned char *)"\xC2\xBD" , CFLAG_ENDCHAR    }	/* U+BD (0xC2BD) unchanged ; VULGAR FRACTION ONE HALF   */
	, { (unsigned char *)"\xC2\xBE" , CFLAG_ENDCHAR    }	/* U+BE (0xC2BE) unchanged ; VULGAR FRACTION THREE QUARTERS   */
	, { (unsigned char *)"\xC2\xBF" , CFLAG_ENDCHAR    }	/* U+BF (0xC2BF) unchanged ; INVERTED QUESTION MARK   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+C0 (0xC380) -> 0x61 ; LATIN CAPITAL LETTER A WITH GRAVE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+C1 (0xC381) -> 0x61 ; LATIN CAPITAL LETTER A WITH ACUTE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+C2 (0xC382) -> 0x61 ; LATIN CAPITAL LETTER A WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+C3 (0xC383) -> 0x61 ; LATIN CAPITAL LETTER A WITH TILDE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+C4 (0xC384) -> 0x61 ; LATIN CAPITAL LETTER A WITH DIAERESIS   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+C5 (0xC385) -> 0x61 ; LATIN CAPITAL LETTER A WITH RING ABOVE   */
	, { (unsigned char *)"\xC3\xA6" , CFLAG_NORMALCHAR }	/* U+C6 (0xC386) -> 0xC3A6 ; LATIN CAPITAL LETTER AE   */
	, { (unsigned char *)"\x63"     , CFLAG_NORMALCHAR }	/* U+C7 (0xC387) -> 0x63 ; LATIN CAPITAL LETTER C WITH CEDILLA   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+C8 (0xC388) -> 0x65 ; LATIN CAPITAL LETTER E WITH GRAVE   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+C9 (0xC389) -> 0x65 ; LATIN CAPITAL LETTER E WITH ACUTE   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+CA (0xC38A) -> 0x65 ; LATIN CAPITAL LETTER E WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+CB (0xC38B) -> 0x65 ; LATIN CAPITAL LETTER E WITH DIAERESIS   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+CC (0xC38C) -> 0x69 ; LATIN CAPITAL LETTER I WITH GRAVE   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+CD (0xC38D) -> 0x69 ; LATIN CAPITAL LETTER I WITH ACUTE   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+CE (0xC38E) -> 0x69 ; LATIN CAPITAL LETTER I WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+CF (0xC38F) -> 0x69 ; LATIN CAPITAL LETTER I WITH DIAERESIS   */
	, { (unsigned char *)"\xC3\xB0" , CFLAG_NORMALCHAR }	/* U+D0 (0xC390) -> 0xC3B0 ; LATIN CAPITAL LETTER ETH   */
	, { (unsigned char *)"\x6E"     , CFLAG_NORMALCHAR }	/* U+D1 (0xC391) -> 0x6E ; LATIN CAPITAL LETTER N WITH TILDE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+D2 (0xC392) -> 0x6F ; LATIN CAPITAL LETTER O WITH GRAVE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+D3 (0xC393) -> 0x6F ; LATIN CAPITAL LETTER O WITH ACUTE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+D4 (0xC394) -> 0x6F ; LATIN CAPITAL LETTER O WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+D5 (0xC395) -> 0x6F ; LATIN CAPITAL LETTER O WITH TILDE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+D6 (0xC396) -> 0x6F ; LATIN CAPITAL LETTER O WITH DIAERESIS   */
	, { (unsigned char *)"\xC3\x97" , CFLAG_NORMALCHAR }	/* U+D7 (0xC397) unchanged ; MULTIPLICATION SIGN   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+D8 (0xC398) -> 0x6F ; LATIN CAPITAL LETTER O WITH STROKE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+D9 (0xC399) -> 0x75 ; LATIN CAPITAL LETTER U WITH GRAVE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+DA (0xC39A) -> 0x75 ; LATIN CAPITAL LETTER U WITH ACUTE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+DB (0xC39B) -> 0x75 ; LATIN CAPITAL LETTER U WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+DC (0xC39C) -> 0x75 ; LATIN CAPITAL LETTER U WITH DIAERESIS   */
	, { (unsigned char *)"\x79"     , CFLAG_NORMALCHAR }	/* U+DD (0xC39D) -> 0x79 ; LATIN CAPITAL LETTER Y WITH ACUTE   */
	, { (unsigned char *)"\xC3\xBE" , CFLAG_NORMALCHAR }	/* U+DE (0xC39E) -> 0xC3BE ; LATIN CAPITAL LETTER THORN   */
	, { (unsigned char *)"\xC3\x9F" , CFLAG_NORMALCHAR }	/* U+DF (0xC39F) unchanged ; LATIN SMALL LETTER SHARP S   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+E0 (0xC3A0) -> 0x61 ; LATIN SMALL LETTER A WITH GRAVE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+E1 (0xC3A1) -> 0x61 ; LATIN SMALL LETTER A WITH ACUTE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+E2 (0xC3A2) -> 0x61 ; LATIN SMALL LETTER A WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+E3 (0xC3A3) -> 0x61 ; LATIN SMALL LETTER A WITH TILDE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+E4 (0xC3A4) -> 0x61 ; LATIN SMALL LETTER A WITH DIAERESIS   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+E5 (0xC3A5) -> 0x61 ; LATIN SMALL LETTER A WITH RING ABOVE   */
	, { (unsigned char *)"\xC3\xA6" , CFLAG_NORMALCHAR }	/* U+E6 (0xC3A6) unchanged ; LATIN SMALL LETTER AE   */
	, { (unsigned char *)"\x63"     , CFLAG_NORMALCHAR }	/* U+E7 (0xC3A7) -> 0x63 ; LATIN SMALL LETTER C WITH CEDILLA   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+E8 (0xC3A8) -> 0x65 ; LATIN SMALL LETTER E WITH GRAVE   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+E9 (0xC3A9) -> 0x65 ; LATIN SMALL LETTER E WITH ACUTE   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+EA (0xC3AA) -> 0x65 ; LATIN SMALL LETTER E WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+EB (0xC3AB) -> 0x65 ; LATIN SMALL LETTER E WITH DIAERESIS   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+EC (0xC3AC) -> 0x69 ; LATIN SMALL LETTER I WITH GRAVE   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+ED (0xC3AD) -> 0x69 ; LATIN SMALL LETTER I WITH ACUTE   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+EE (0xC3AE) -> 0x69 ; LATIN SMALL LETTER I WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+EF (0xC3AF) -> 0x69 ; LATIN SMALL LETTER I WITH DIAERESIS   */
	, { (unsigned char *)"\xC3\xB0" , CFLAG_NORMALCHAR }	/* U+F0 (0xC3B0) unchanged ; LATIN SMALL LETTER ETH   */
	, { (unsigned char *)"\x6E"     , CFLAG_NORMALCHAR }	/* U+F1 (0xC3B1) -> 0x6E ; LATIN SMALL LETTER N WITH TILDE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+F2 (0xC3B2) -> 0x6F ; LATIN SMALL LETTER O WITH GRAVE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+F3 (0xC3B3) -> 0x6F ; LATIN SMALL LETTER O WITH ACUTE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+F4 (0xC3B4) -> 0x6F ; LATIN SMALL LETTER O WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+F5 (0xC3B5) -> 0x6F ; LATIN SMALL LETTER O WITH TILDE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+F6 (0xC3B6) -> 0x6F ; LATIN SMALL LETTER O WITH DIAERESIS   */
	, { (unsigned char *)"\xC3\xB7" , CFLAG_ENDCHAR    }	/* U+F7 (0xC3B7) unchanged ; DIVISION SIGN   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+F8 (0xC3B8) -> 0x6F ; LATIN SMALL LETTER O WITH STROKE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+F9 (0xC3B9) -> 0x75 ; LATIN SMALL LETTER U WITH GRAVE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+FA (0xC3BA) -> 0x75 ; LATIN SMALL LETTER U WITH ACUTE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+FB (0xC3BB) -> 0x75 ; LATIN SMALL LETTER U WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+FC (0xC3BC) -> 0x75 ; LATIN SMALL LETTER U WITH DIAERESIS   */
	, { (unsigned char *)"\x79"     , CFLAG_NORMALCHAR }	/* U+FD (0xC3BD) -> 0x79 ; LATIN SMALL LETTER Y WITH ACUTE   */
	, { (unsigned char *)"\xC3\xBE" , CFLAG_NORMALCHAR }	/* U+FE (0xC3BE) unchanged ; LATIN SMALL LETTER THORN   */
	, { (unsigned char *)"\x79"     , CFLAG_NORMALCHAR }	/* U+FF (0xC3BF) -> 0x79 ; LATIN SMALL LETTER Y WITH DIAERESIS   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+100 (0xC480) -> 0x61 ; LATIN CAPITAL LETTER A WITH MACRON   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+101 (0xC481) -> 0x61 ; LATIN SMALL LETTER A WITH MACRON   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+102 (0xC482) -> 0x61 ; LATIN CAPITAL LETTER A WITH BREVE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+103 (0xC483) -> 0x61 ; LATIN SMALL LETTER A WITH BREVE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+104 (0xC484) -> 0x61 ; LATIN CAPITAL LETTER A WITH OGONEK   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+105 (0xC485) -> 0x61 ; LATIN SMALL LETTER A WITH OGONEK   */
	, { (unsigned char *)"\x63"     , CFLAG_NORMALCHAR }	/* U+106 (0xC486) -> 0x63 ; LATIN CAPITAL LETTER C WITH ACUTE   */
	, { (unsigned char *)"\x63"     , CFLAG_NORMALCHAR }	/* U+107 (0xC487) -> 0x63 ; LATIN SMALL LETTER C WITH ACUTE   */
	, { (unsigned char *)"\x63"     , CFLAG_NORMALCHAR }	/* U+108 (0xC488) -> 0x63 ; LATIN CAPITAL LETTER C WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x63"     , CFLAG_NORMALCHAR }	/* U+109 (0xC489) -> 0x63 ; LATIN SMALL LETTER C WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x63"     , CFLAG_NORMALCHAR }	/* U+10A (0xC48A) -> 0x63 ; LATIN CAPITAL LETTER C WITH DOT ABOVE   */
	, { (unsigned char *)"\x63"     , CFLAG_NORMALCHAR }	/* U+10B (0xC48B) -> 0x63 ; LATIN SMALL LETTER C WITH DOT ABOVE   */
	, { (unsigned char *)"\x63"     , CFLAG_NORMALCHAR }	/* U+10C (0xC48C) -> 0x63 ; LATIN CAPITAL LETTER C WITH CARON   */
	, { (unsigned char *)"\x63"     , CFLAG_NORMALCHAR }	/* U+10D (0xC48D) -> 0x63 ; LATIN SMALL LETTER C WITH CARON   */
	, { (unsigned char *)"\x64"     , CFLAG_NORMALCHAR }	/* U+10E (0xC48E) -> 0x64 ; LATIN CAPITAL LETTER D WITH CARON   */
	, { (unsigned char *)"\x64"     , CFLAG_NORMALCHAR }	/* U+10F (0xC48F) -> 0x64 ; LATIN SMALL LETTER D WITH CARON   */
	, { (unsigned char *)"\x64"     , CFLAG_NORMALCHAR }	/* U+110 (0xC490) -> 0x64 ; LATIN CAPITAL LETTER D WITH STROKE   */
	, { (unsigned char *)"\x64"     , CFLAG_NORMALCHAR }	/* U+111 (0xC491) -> 0x64 ; LATIN SMALL LETTER D WITH STROKE   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+112 (0xC492) -> 0x65 ; LATIN CAPITAL LETTER E WITH MACRON   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+113 (0xC493) -> 0x65 ; LATIN SMALL LETTER E WITH MACRON   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+114 (0xC494) -> 0x65 ; LATIN CAPITAL LETTER E WITH BREVE   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+115 (0xC495) -> 0x65 ; LATIN SMALL LETTER E WITH BREVE   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+116 (0xC496) -> 0x65 ; LATIN CAPITAL LETTER E WITH DOT ABOVE   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+117 (0xC497) -> 0x65 ; LATIN SMALL LETTER E WITH DOT ABOVE   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+118 (0xC498) -> 0x65 ; LATIN CAPITAL LETTER E WITH OGONEK   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+119 (0xC499) -> 0x65 ; LATIN SMALL LETTER E WITH OGONEK   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+11A (0xC49A) -> 0x65 ; LATIN CAPITAL LETTER E WITH CARON   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+11B (0xC49B) -> 0x65 ; LATIN SMALL LETTER E WITH CARON   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+11C (0xC49C) -> 0x67 ; LATIN CAPITAL LETTER G WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+11D (0xC49D) -> 0x67 ; LATIN SMALL LETTER G WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+11E (0xC49E) -> 0x67 ; LATIN CAPITAL LETTER G WITH BREVE   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+11F (0xC49F) -> 0x67 ; LATIN SMALL LETTER G WITH BREVE   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+120 (0xC4A0) -> 0x67 ; LATIN CAPITAL LETTER G WITH DOT ABOVE   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+121 (0xC4A1) -> 0x67 ; LATIN SMALL LETTER G WITH DOT ABOVE   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+122 (0xC4A2) -> 0x67 ; LATIN CAPITAL LETTER G WITH CEDILLA   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+123 (0xC4A3) -> 0x67 ; LATIN SMALL LETTER G WITH CEDILLA   */
	, { (unsigned char *)"\x68"     , CFLAG_NORMALCHAR }	/* U+124 (0xC4A4) -> 0x68 ; LATIN CAPITAL LETTER H WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x68"     , CFLAG_NORMALCHAR }	/* U+125 (0xC4A5) -> 0x68 ; LATIN SMALL LETTER H WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x68"     , CFLAG_NORMALCHAR }	/* U+126 (0xC4A6) -> 0x68 ; LATIN CAPITAL LETTER H WITH STROKE   */
	, { (unsigned char *)"\x68"     , CFLAG_NORMALCHAR }	/* U+127 (0xC4A7) -> 0x68 ; LATIN SMALL LETTER H WITH STROKE   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+128 (0xC4A8) -> 0x69 ; LATIN CAPITAL LETTER I WITH TILDE   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+129 (0xC4A9) -> 0x69 ; LATIN SMALL LETTER I WITH TILDE   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+12A (0xC4AA) -> 0x69 ; LATIN CAPITAL LETTER I WITH MACRON   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+12B (0xC4AB) -> 0x69 ; LATIN SMALL LETTER I WITH MACRON   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+12C (0xC4AC) -> 0x69 ; LATIN CAPITAL LETTER I WITH BREVE   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+12D (0xC4AD) -> 0x69 ; LATIN SMALL LETTER I WITH BREVE   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+12E (0xC4AE) -> 0x69 ; LATIN CAPITAL LETTER I WITH OGONEK   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+12F (0xC4AF) -> 0x69 ; LATIN SMALL LETTER I WITH OGONEK   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+130 (0xC4B0) -> 0x69 ; LATIN CAPITAL LETTER I WITH DOT ABOVE   */
	, { (unsigned char *)"\xC4\xB1" , CFLAG_NORMALCHAR }	/* U+131 (0xC4B1) unchanged ; LATIN SMALL LETTER DOTLESS I   */
	, { (unsigned char *)"\xC4\xB3" , CFLAG_NORMALCHAR }	/* U+132 (0xC4B2) -> 0xC4B3 ; LATIN CAPITAL LIGATURE IJ   */
	, { (unsigned char *)"\xC4\xB3" , CFLAG_NORMALCHAR }	/* U+133 (0xC4B3) unchanged ; LATIN SMALL LIGATURE IJ   */
	, { (unsigned char *)"\x6A"     , CFLAG_NORMALCHAR }	/* U+134 (0xC4B4) -> 0x6A ; LATIN CAPITAL LETTER J WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x6A"     , CFLAG_NORMALCHAR }	/* U+135 (0xC4B5) -> 0x6A ; LATIN SMALL LETTER J WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x6B"     , CFLAG_NORMALCHAR }	/* U+136 (0xC4B6) -> 0x6B ; LATIN CAPITAL LETTER K WITH CEDILLA   */
	, { (unsigned char *)"\x6B"     , CFLAG_NORMALCHAR }	/* U+137 (0xC4B7) -> 0x6B ; LATIN SMALL LETTER K WITH CEDILLA   */
	, { (unsigned char *)"\xC4\xB8" , CFLAG_NORMALCHAR }	/* U+138 (0xC4B8) unchanged ; LATIN SMALL LETTER KRA   */
	, { (unsigned char *)"\x6C"     , CFLAG_NORMALCHAR }	/* U+139 (0xC4B9) -> 0x6C ; LATIN CAPITAL LETTER L WITH ACUTE   */
	, { (unsigned char *)"\x6C"     , CFLAG_NORMALCHAR }	/* U+13A (0xC4BA) -> 0x6C ; LATIN SMALL LETTER L WITH ACUTE   */
	, { (unsigned char *)"\x6C"     , CFLAG_NORMALCHAR }	/* U+13B (0xC4BB) -> 0x6C ; LATIN CAPITAL LETTER L WITH CEDILLA   */
	, { (unsigned char *)"\x6C"     , CFLAG_NORMALCHAR }	/* U+13C (0xC4BC) -> 0x6C ; LATIN SMALL LETTER L WITH CEDILLA   */
	, { (unsigned char *)"\x6C"     , CFLAG_NORMALCHAR }	/* U+13D (0xC4BD) -> 0x6C ; LATIN CAPITAL LETTER L WITH CARON   */
	, { (unsigned char *)"\x6C"     , CFLAG_NORMALCHAR }	/* U+13E (0xC4BE) -> 0x6C ; LATIN SMALL LETTER L WITH CARON   */
	, { (unsigned char *)"\x6C"     , CFLAG_NORMALCHAR }	/* U+13F (0xC4BF) -> 0x6C ; LATIN CAPITAL LETTER L WITH MIDDLE DOT   */
	, { (unsigned char *)"\x6C"     , CFLAG_NORMALCHAR }	/* U+140 (0xC580) -> 0x6C ; LATIN SMALL LETTER L WITH MIDDLE DOT   */
	, { (unsigned char *)"\x6C"     , CFLAG_NORMALCHAR }	/* U+141 (0xC581) -> 0x6C ; LATIN CAPITAL LETTER L WITH STROKE   */
	, { (unsigned char *)"\x6C"     , CFLAG_NORMALCHAR }	/* U+142 (0xC582) -> 0x6C ; LATIN SMALL LETTER L WITH STROKE   */
	, { (unsigned char *)"\x6E"     , CFLAG_NORMALCHAR }	/* U+143 (0xC583) -> 0x6E ; LATIN CAPITAL LETTER N WITH ACUTE   */
	, { (unsigned char *)"\x6E"     , CFLAG_NORMALCHAR }	/* U+144 (0xC584) -> 0x6E ; LATIN SMALL LETTER N WITH ACUTE   */
	, { (unsigned char *)"\x6E"     , CFLAG_NORMALCHAR }	/* U+145 (0xC585) -> 0x6E ; LATIN CAPITAL LETTER N WITH CEDILLA   */
	, { (unsigned char *)"\x6E"     , CFLAG_NORMALCHAR }	/* U+146 (0xC586) -> 0x6E ; LATIN SMALL LETTER N WITH CEDILLA   */
	, { (unsigned char *)"\x6E"     , CFLAG_NORMALCHAR }	/* U+147 (0xC587) -> 0x6E ; LATIN CAPITAL LETTER N WITH CARON   */
	, { (unsigned char *)"\x6E"     , CFLAG_NORMALCHAR }	/* U+148 (0xC588) -> 0x6E ; LATIN SMALL LETTER N WITH CARON   */
	, { (unsigned char *)"\xC5\x89" , CFLAG_NORMALCHAR }	/* U+149 (0xC589) unchanged ; LATIN SMALL LETTER N PRECEDED BY APOSTROPHE   */
	, { (unsigned char *)"\xC5\x8B" , CFLAG_NORMALCHAR }	/* U+14A (0xC58A) -> 0xC58B ; LATIN CAPITAL LETTER ENG   */
	, { (unsigned char *)"\xC5\x8B" , CFLAG_NORMALCHAR }	/* U+14B (0xC58B) unchanged ; LATIN SMALL LETTER ENG   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+14C (0xC58C) -> 0x6F ; LATIN CAPITAL LETTER O WITH MACRON   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+14D (0xC58D) -> 0x6F ; LATIN SMALL LETTER O WITH MACRON   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+14E (0xC58E) -> 0x6F ; LATIN CAPITAL LETTER O WITH BREVE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+14F (0xC58F) -> 0x6F ; LATIN SMALL LETTER O WITH BREVE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+150 (0xC590) -> 0x6F ; LATIN CAPITAL LETTER O WITH DOUBLE ACUTE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+151 (0xC591) -> 0x6F ; LATIN SMALL LETTER O WITH DOUBLE ACUTE   */
	, { (unsigned char *)"\xC5\x93" , CFLAG_NORMALCHAR }	/* U+152 (0xC592) -> 0xC593 ; LATIN CAPITAL LIGATURE OE   */
	, { (unsigned char *)"\xC5\x93" , CFLAG_NORMALCHAR }	/* U+153 (0xC593) unchanged ; LATIN SMALL LIGATURE OE   */
	, { (unsigned char *)"\x72"     , CFLAG_NORMALCHAR }	/* U+154 (0xC594) -> 0x72 ; LATIN CAPITAL LETTER R WITH ACUTE   */
	, { (unsigned char *)"\x72"     , CFLAG_NORMALCHAR }	/* U+155 (0xC595) -> 0x72 ; LATIN SMALL LETTER R WITH ACUTE   */
	, { (unsigned char *)"\x72"     , CFLAG_NORMALCHAR }	/* U+156 (0xC596) -> 0x72 ; LATIN CAPITAL LETTER R WITH CEDILLA   */
	, { (unsigned char *)"\x72"     , CFLAG_NORMALCHAR }	/* U+157 (0xC597) -> 0x72 ; LATIN SMALL LETTER R WITH CEDILLA   */
	, { (unsigned char *)"\x72"     , CFLAG_NORMALCHAR }	/* U+158 (0xC598) -> 0x72 ; LATIN CAPITAL LETTER R WITH CARON   */
	, { (unsigned char *)"\x72"     , CFLAG_NORMALCHAR }	/* U+159 (0xC599) -> 0x72 ; LATIN SMALL LETTER R WITH CARON   */
	, { (unsigned char *)"\x73"     , CFLAG_NORMALCHAR }	/* U+15A (0xC59A) -> 0x73 ; LATIN CAPITAL LETTER S WITH ACUTE   */
	, { (unsigned char *)"\x73"     , CFLAG_NORMALCHAR }	/* U+15B (0xC59B) -> 0x73 ; LATIN SMALL LETTER S WITH ACUTE   */
	, { (unsigned char *)"\x73"     , CFLAG_NORMALCHAR }	/* U+15C (0xC59C) -> 0x73 ; LATIN CAPITAL LETTER S WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x73"     , CFLAG_NORMALCHAR }	/* U+15D (0xC59D) -> 0x73 ; LATIN SMALL LETTER S WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x73"     , CFLAG_NORMALCHAR }	/* U+15E (0xC59E) -> 0x73 ; LATIN CAPITAL LETTER S WITH CEDILLA   */
	, { (unsigned char *)"\x73"     , CFLAG_NORMALCHAR }	/* U+15F (0xC59F) -> 0x73 ; LATIN SMALL LETTER S WITH CEDILLA   */
	, { (unsigned char *)"\x73"     , CFLAG_NORMALCHAR }	/* U+160 (0xC5A0) -> 0x73 ; LATIN CAPITAL LETTER S WITH CARON   */
	, { (unsigned char *)"\x73"     , CFLAG_NORMALCHAR }	/* U+161 (0xC5A1) -> 0x73 ; LATIN SMALL LETTER S WITH CARON   */
	, { (unsigned char *)"\x74"     , CFLAG_NORMALCHAR }	/* U+162 (0xC5A2) -> 0x74 ; LATIN CAPITAL LETTER T WITH CEDILLA   */
	, { (unsigned char *)"\x74"     , CFLAG_NORMALCHAR }	/* U+163 (0xC5A3) -> 0x74 ; LATIN SMALL LETTER T WITH CEDILLA   */
	, { (unsigned char *)"\x74"     , CFLAG_NORMALCHAR }	/* U+164 (0xC5A4) -> 0x74 ; LATIN CAPITAL LETTER T WITH CARON   */
	, { (unsigned char *)"\x74"     , CFLAG_NORMALCHAR }	/* U+165 (0xC5A5) -> 0x74 ; LATIN SMALL LETTER T WITH CARON   */
	, { (unsigned char *)"\x74"     , CFLAG_NORMALCHAR }	/* U+166 (0xC5A6) -> 0x74 ; LATIN CAPITAL LETTER T WITH STROKE   */
	, { (unsigned char *)"\x74"     , CFLAG_NORMALCHAR }	/* U+167 (0xC5A7) -> 0x74 ; LATIN SMALL LETTER T WITH STROKE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+168 (0xC5A8) -> 0x75 ; LATIN CAPITAL LETTER U WITH TILDE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+169 (0xC5A9) -> 0x75 ; LATIN SMALL LETTER U WITH TILDE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+16A (0xC5AA) -> 0x75 ; LATIN CAPITAL LETTER U WITH MACRON   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+16B (0xC5AB) -> 0x75 ; LATIN SMALL LETTER U WITH MACRON   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+16C (0xC5AC) -> 0x75 ; LATIN CAPITAL LETTER U WITH BREVE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+16D (0xC5AD) -> 0x75 ; LATIN SMALL LETTER U WITH BREVE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+16E (0xC5AE) -> 0x75 ; LATIN CAPITAL LETTER U WITH RING ABOVE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+16F (0xC5AF) -> 0x75 ; LATIN SMALL LETTER U WITH RING ABOVE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+170 (0xC5B0) -> 0x75 ; LATIN CAPITAL LETTER U WITH DOUBLE ACUTE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+171 (0xC5B1) -> 0x75 ; LATIN SMALL LETTER U WITH DOUBLE ACUTE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+172 (0xC5B2) -> 0x75 ; LATIN CAPITAL LETTER U WITH OGONEK   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+173 (0xC5B3) -> 0x75 ; LATIN SMALL LETTER U WITH OGONEK   */
	, { (unsigned char *)"\x77"     , CFLAG_NORMALCHAR }	/* U+174 (0xC5B4) -> 0x77 ; LATIN CAPITAL LETTER W WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x77"     , CFLAG_NORMALCHAR }	/* U+175 (0xC5B5) -> 0x77 ; LATIN SMALL LETTER W WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x79"     , CFLAG_NORMALCHAR }	/* U+176 (0xC5B6) -> 0x79 ; LATIN CAPITAL LETTER Y WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x79"     , CFLAG_NORMALCHAR }	/* U+177 (0xC5B7) -> 0x79 ; LATIN SMALL LETTER Y WITH CIRCUMFLEX   */
	, { (unsigned char *)"\x79"     , CFLAG_NORMALCHAR }	/* U+178 (0xC5B8) -> 0x79 ; LATIN CAPITAL LETTER Y WITH DIAERESIS   */
	, { (unsigned char *)"\x7A"     , CFLAG_NORMALCHAR }	/* U+179 (0xC5B9) -> 0x7A ; LATIN CAPITAL LETTER Z WITH ACUTE   */
	, { (unsigned char *)"\x7A"     , CFLAG_NORMALCHAR }	/* U+17A (0xC5BA) -> 0x7A ; LATIN SMALL LETTER Z WITH ACUTE   */
	, { (unsigned char *)"\x7A"     , CFLAG_NORMALCHAR }	/* U+17B (0xC5BB) -> 0x7A ; LATIN CAPITAL LETTER Z WITH DOT ABOVE   */
	, { (unsigned char *)"\x7A"     , CFLAG_NORMALCHAR }	/* U+17C (0xC5BC) -> 0x7A ; LATIN SMALL LETTER Z WITH DOT ABOVE   */
	, { (unsigned char *)"\x7A"     , CFLAG_NORMALCHAR }	/* U+17D (0xC5BD) -> 0x7A ; LATIN CAPITAL LETTER Z WITH CARON   */
	, { (unsigned char *)"\x7A"     , CFLAG_NORMALCHAR }	/* U+17E (0xC5BE) -> 0x7A ; LATIN SMALL LETTER Z WITH CARON   */
	, { (unsigned char *)"\xC5\xBF" , CFLAG_NORMALCHAR }	/* U+17F (0xC5BF) unchanged ; LATIN SMALL LETTER LONG S   */
	, { (unsigned char *)"\x62"     , CFLAG_NORMALCHAR }	/* U+180 (0xC680) -> 0x62 ; LATIN SMALL LETTER B WITH STROKE   */
	, { (unsigned char *)"\x62"     , CFLAG_NORMALCHAR }	/* U+181 (0xC681) -> 0x62 ; LATIN CAPITAL LETTER B WITH HOOK   */
	, { (unsigned char *)"\x62"     , CFLAG_NORMALCHAR }	/* U+182 (0xC682) -> 0x62 ; LATIN CAPITAL LETTER B WITH TOPBAR   */
	, { (unsigned char *)"\x62"     , CFLAG_NORMALCHAR }	/* U+183 (0xC683) -> 0x62 ; LATIN SMALL LETTER B WITH TOPBAR   */
	, { (unsigned char *)"\xC6\x85" , CFLAG_NORMALCHAR }	/* U+184 (0xC684) -> 0xC685 ; LATIN CAPITAL LETTER TONE SIX   */
	, { (unsigned char *)"\xC6\x85" , CFLAG_NORMALCHAR }	/* U+185 (0xC685) unchanged ; LATIN SMALL LETTER TONE SIX   */
	, { (unsigned char *)"\xC9\x94" , CFLAG_NORMALCHAR }	/* U+186 (0xC686) -> 0xC994 ; LATIN CAPITAL LETTER OPEN O   */
	, { (unsigned char *)"\x63"     , CFLAG_NORMALCHAR }	/* U+187 (0xC687) -> 0x63 ; LATIN CAPITAL LETTER C WITH HOOK   */
	, { (unsigned char *)"\x63"     , CFLAG_NORMALCHAR }	/* U+188 (0xC688) -> 0x63 ; LATIN SMALL LETTER C WITH HOOK   */
	, { (unsigned char *)"\x64"     , CFLAG_NORMALCHAR }	/* U+189 (0xC689) -> 0x64 ; LATIN CAPITAL LETTER AFRICAN D   */
	, { (unsigned char *)"\x64"     , CFLAG_NORMALCHAR }	/* U+18A (0xC68A) -> 0x64 ; LATIN CAPITAL LETTER D WITH HOOK   */
	, { (unsigned char *)"\x64"     , CFLAG_NORMALCHAR }	/* U+18B (0xC68B) -> 0x64 ; LATIN CAPITAL LETTER D WITH TOPBAR   */
	, { (unsigned char *)"\x64"     , CFLAG_NORMALCHAR }	/* U+18C (0xC68C) -> 0x64 ; LATIN SMALL LETTER D WITH TOPBAR   */
	, { (unsigned char *)"\xC6\x8D" , CFLAG_NORMALCHAR }	/* U+18D (0xC68D) unchanged ; LATIN SMALL LETTER TURNED DELTA   */
	, { (unsigned char *)"\xC7\x9D" , CFLAG_NORMALCHAR }	/* U+18E (0xC68E) -> 0xC79D ; LATIN CAPITAL LETTER REVERSED E   */
	, { (unsigned char *)"\xC9\x99" , CFLAG_NORMALCHAR }	/* U+18F (0xC68F) -> 0xC999 ; LATIN CAPITAL LETTER SCHWA   */
	, { (unsigned char *)"\xC9\x9B" , CFLAG_NORMALCHAR }	/* U+190 (0xC690) -> 0xC99B ; LATIN CAPITAL LETTER OPEN E   */
	, { (unsigned char *)"\x66"     , CFLAG_NORMALCHAR }	/* U+191 (0xC691) -> 0x66 ; LATIN CAPITAL LETTER F WITH HOOK   */
	, { (unsigned char *)"\x66"     , CFLAG_NORMALCHAR }	/* U+192 (0xC692) -> 0x66 ; LATIN SMALL LETTER F WITH HOOK   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+193 (0xC693) -> 0x67 ; LATIN CAPITAL LETTER G WITH HOOK   */
	, { (unsigned char *)"\xC9\xA3" , CFLAG_NORMALCHAR }	/* U+194 (0xC694) -> 0xC9A3 ; LATIN CAPITAL LETTER GAMMA   */
	, { (unsigned char *)"\xC6\x95" , CFLAG_NORMALCHAR }	/* U+195 (0xC695) unchanged ; LATIN SMALL LETTER HV   */
	, { (unsigned char *)"\xC9\xA9" , CFLAG_NORMALCHAR }	/* U+196 (0xC696) -> 0xC9A9 ; LATIN CAPITAL LETTER IOTA   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+197 (0xC697) -> 0x69 ; LATIN CAPITAL LETTER I WITH STROKE   */
	, { (unsigned char *)"\x6B"     , CFLAG_NORMALCHAR }	/* U+198 (0xC698) -> 0x6B ; LATIN CAPITAL LETTER K WITH HOOK   */
	, { (unsigned char *)"\x6B"     , CFLAG_NORMALCHAR }	/* U+199 (0xC699) -> 0x6B ; LATIN SMALL LETTER K WITH HOOK   */
	, { (unsigned char *)"\x6C"     , CFLAG_NORMALCHAR }	/* U+19A (0xC69A) -> 0x6C ; LATIN SMALL LETTER L WITH BAR   */
	, { (unsigned char *)"\xC6\x9B" , CFLAG_NORMALCHAR }	/* U+19B (0xC69B) unchanged ; LATIN SMALL LETTER LAMBDA WITH STROKE   */
	, { (unsigned char *)"\xC9\xAF" , CFLAG_NORMALCHAR }	/* U+19C (0xC69C) -> 0xC9AF ; LATIN CAPITAL LETTER TURNED M   */
	, { (unsigned char *)"\x6E"     , CFLAG_NORMALCHAR }	/* U+19D (0xC69D) -> 0x6E ; LATIN CAPITAL LETTER N WITH LEFT HOOK   */
	, { (unsigned char *)"\x6E"     , CFLAG_NORMALCHAR }	/* U+19E (0xC69E) -> 0x6E ; LATIN SMALL LETTER N WITH LONG RIGHT LEG   */
	, { (unsigned char *)"\xC9\xB5" , CFLAG_NORMALCHAR }	/* U+19F (0xC69F) -> 0xC9B5 ; LATIN CAPITAL LETTER O WITH MIDDLE TILDE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+1A0 (0xC6A0) -> 0x6F ; LATIN CAPITAL LETTER O WITH HORN   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+1A1 (0xC6A1) -> 0x6F ; LATIN SMALL LETTER O WITH HORN   */
	, { (unsigned char *)"\xC6\xA3" , CFLAG_NORMALCHAR }	/* U+1A2 (0xC6A2) -> 0xC6A3 ; LATIN CAPITAL LETTER OI   */
	, { (unsigned char *)"\xC6\xA3" , CFLAG_NORMALCHAR }	/* U+1A3 (0xC6A3) unchanged ; LATIN SMALL LETTER OI   */
	, { (unsigned char *)"\x70"     , CFLAG_NORMALCHAR }	/* U+1A4 (0xC6A4) -> 0x70 ; LATIN CAPITAL LETTER P WITH HOOK   */
	, { (unsigned char *)"\x70"     , CFLAG_NORMALCHAR }	/* U+1A5 (0xC6A5) -> 0x70 ; LATIN SMALL LETTER P WITH HOOK   */
	, { (unsigned char *)"\xCA\x80" , CFLAG_NORMALCHAR }	/* U+1A6 (0xC6A6) -> 0xCA80 ; LATIN LETTER YR   */
	, { (unsigned char *)"\xC6\xA8" , CFLAG_NORMALCHAR }	/* U+1A7 (0xC6A7) -> 0xC6A8 ; LATIN CAPITAL LETTER TONE TWO   */
	, { (unsigned char *)"\xC6\xA8" , CFLAG_NORMALCHAR }	/* U+1A8 (0xC6A8) unchanged ; LATIN SMALL LETTER TONE TWO   */
	, { (unsigned char *)"\xCA\x83" , CFLAG_NORMALCHAR }	/* U+1A9 (0xC6A9) -> 0xCA83 ; LATIN CAPITAL LETTER ESH   */
	, { (unsigned char *)"\xC6\xAA" , CFLAG_NORMALCHAR }	/* U+1AA (0xC6AA) unchanged ; LATIN LETTER REVERSED ESH LOOP   */
	, { (unsigned char *)"\x74"     , CFLAG_NORMALCHAR }	/* U+1AB (0xC6AB) -> 0x74 ; LATIN SMALL LETTER T WITH PALATAL HOOK   */
	, { (unsigned char *)"\x74"     , CFLAG_NORMALCHAR }	/* U+1AC (0xC6AC) -> 0x74 ; LATIN CAPITAL LETTER T WITH HOOK   */
	, { (unsigned char *)"\x74"     , CFLAG_NORMALCHAR }	/* U+1AD (0xC6AD) -> 0x74 ; LATIN SMALL LETTER T WITH HOOK   */
	, { (unsigned char *)"\x74"     , CFLAG_NORMALCHAR }	/* U+1AE (0xC6AE) -> 0x74 ; LATIN CAPITAL LETTER T WITH RETROFLEX HOOK   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+1AF (0xC6AF) -> 0x75 ; LATIN CAPITAL LETTER U WITH HORN   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+1B0 (0xC6B0) -> 0x75 ; LATIN SMALL LETTER U WITH HORN   */
	, { (unsigned char *)"\xCA\x8A" , CFLAG_NORMALCHAR }	/* U+1B1 (0xC6B1) -> 0xCA8A ; LATIN CAPITAL LETTER UPSILON   */
	, { (unsigned char *)"\x76"     , CFLAG_NORMALCHAR }	/* U+1B2 (0xC6B2) -> 0x76 ; LATIN CAPITAL LETTER V WITH HOOK   */
	, { (unsigned char *)"\x79"     , CFLAG_NORMALCHAR }	/* U+1B3 (0xC6B3) -> 0x79 ; LATIN CAPITAL LETTER Y WITH HOOK   */
	, { (unsigned char *)"\x79"     , CFLAG_NORMALCHAR }	/* U+1B4 (0xC6B4) -> 0x79 ; LATIN SMALL LETTER Y WITH HOOK   */
	, { (unsigned char *)"\x7A"     , CFLAG_NORMALCHAR }	/* U+1B5 (0xC6B5) -> 0x7A ; LATIN CAPITAL LETTER Z WITH STROKE   */
	, { (unsigned char *)"\x7A"     , CFLAG_NORMALCHAR }	/* U+1B6 (0xC6B6) -> 0x7A ; LATIN SMALL LETTER Z WITH STROKE   */
	, { (unsigned char *)"\xCA\x92" , CFLAG_NORMALCHAR }	/* U+1B7 (0xC6B7) -> 0xCA92 ; LATIN CAPITAL LETTER EZH   */
	, { (unsigned char *)"\xC6\xB9" , CFLAG_NORMALCHAR }	/* U+1B8 (0xC6B8) -> 0xC6B9 ; LATIN CAPITAL LETTER EZH REVERSED   */
	, { (unsigned char *)"\xC6\xB9" , CFLAG_NORMALCHAR }	/* U+1B9 (0xC6B9) unchanged ; LATIN SMALL LETTER EZH REVERSED   */
	, { (unsigned char *)"\xCA\x92" , CFLAG_NORMALCHAR }	/* U+1BA (0xC6BA) -> 0xCA92 ; LATIN SMALL LETTER EZH WITH TAIL   */
	, { (unsigned char *)"\xC6\xBB" , CFLAG_NORMALCHAR }	/* U+1BB (0xC6BB) unchanged ; LATIN LETTER TWO WITH STROKE   */
	, { (unsigned char *)"\xC6\xBD" , CFLAG_NORMALCHAR }	/* U+1BC (0xC6BC) -> 0xC6BD ; LATIN CAPITAL LETTER TONE FIVE   */
	, { (unsigned char *)"\xC6\xBD" , CFLAG_NORMALCHAR }	/* U+1BD (0xC6BD) unchanged ; LATIN SMALL LETTER TONE FIVE   */
	, { (unsigned char *)"\xC6\xBE" , CFLAG_NORMALCHAR }	/* U+1BE (0xC6BE) unchanged ; LATIN LETTER INVERTED GLOTTAL STOP WITH STROKE   */
	, { (unsigned char *)"\xC6\xBF" , CFLAG_NORMALCHAR }	/* U+1BF (0xC6BF) unchanged ; LATIN LETTER WYNN   */
	, { (unsigned char *)"\xC7\x80" , CFLAG_NORMALCHAR }	/* U+1C0 (0xC780) unchanged ; LATIN LETTER DENTAL CLICK   */
	, { (unsigned char *)"\xC7\x81" , CFLAG_NORMALCHAR }	/* U+1C1 (0xC781) unchanged ; LATIN LETTER LATERAL CLICK   */
	, { (unsigned char *)"\xC7\x82" , CFLAG_NORMALCHAR }	/* U+1C2 (0xC782) unchanged ; LATIN LETTER ALVEOLAR CLICK   */
	, { (unsigned char *)"\xC7\x83" , CFLAG_NORMALCHAR }	/* U+1C3 (0xC783) unchanged ; LATIN LETTER RETROFLEX CLICK   */
	, { (unsigned char *)"\xC7\x86" , CFLAG_NORMALCHAR }	/* U+1C4 (0xC784) -> 0xC786 ; LATIN CAPITAL LETTER DZ WITH CARON   */
	, { (unsigned char *)"\xC7\x86" , CFLAG_NORMALCHAR }	/* U+1C5 (0xC785) -> 0xC786 ; LATIN CAPITAL LETTER D WITH SMALL LETTER Z WITH CARON   */
	, { (unsigned char *)"\xC7\x86" , CFLAG_NORMALCHAR }	/* U+1C6 (0xC786) unchanged ; LATIN SMALL LETTER DZ WITH CARON   */
	, { (unsigned char *)"\xC7\x89" , CFLAG_NORMALCHAR }	/* U+1C7 (0xC787) -> 0xC789 ; LATIN CAPITAL LETTER LJ   */
	, { (unsigned char *)"\xC7\x89" , CFLAG_NORMALCHAR }	/* U+1C8 (0xC788) -> 0xC789 ; LATIN CAPITAL LETTER L WITH SMALL LETTER J   */
	, { (unsigned char *)"\xC7\x89" , CFLAG_NORMALCHAR }	/* U+1C9 (0xC789) unchanged ; LATIN SMALL LETTER LJ   */
	, { (unsigned char *)"\xC7\x8C" , CFLAG_NORMALCHAR }	/* U+1CA (0xC78A) -> 0xC78C ; LATIN CAPITAL LETTER NJ   */
	, { (unsigned char *)"\xC7\x8C" , CFLAG_NORMALCHAR }	/* U+1CB (0xC78B) -> 0xC78C ; LATIN CAPITAL LETTER N WITH SMALL LETTER J   */
	, { (unsigned char *)"\xC7\x8C" , CFLAG_NORMALCHAR }	/* U+1CC (0xC78C) unchanged ; LATIN SMALL LETTER NJ   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+1CD (0xC78D) -> 0x61 ; LATIN CAPITAL LETTER A WITH CARON   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+1CE (0xC78E) -> 0x61 ; LATIN SMALL LETTER A WITH CARON   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+1CF (0xC78F) -> 0x69 ; LATIN CAPITAL LETTER I WITH CARON   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+1D0 (0xC790) -> 0x69 ; LATIN SMALL LETTER I WITH CARON   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+1D1 (0xC791) -> 0x6F ; LATIN CAPITAL LETTER O WITH CARON   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+1D2 (0xC792) -> 0x6F ; LATIN SMALL LETTER O WITH CARON   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+1D3 (0xC793) -> 0x75 ; LATIN CAPITAL LETTER U WITH CARON   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+1D4 (0xC794) -> 0x75 ; LATIN SMALL LETTER U WITH CARON   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+1D5 (0xC795) -> 0x75 ; LATIN CAPITAL LETTER U WITH DIAERESIS AND MACRON   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+1D6 (0xC796) -> 0x75 ; LATIN SMALL LETTER U WITH DIAERESIS AND MACRON   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+1D7 (0xC797) -> 0x75 ; LATIN CAPITAL LETTER U WITH DIAERESIS AND ACUTE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+1D8 (0xC798) -> 0x75 ; LATIN SMALL LETTER U WITH DIAERESIS AND ACUTE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+1D9 (0xC799) -> 0x75 ; LATIN CAPITAL LETTER U WITH DIAERESIS AND CARON   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+1DA (0xC79A) -> 0x75 ; LATIN SMALL LETTER U WITH DIAERESIS AND CARON   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+1DB (0xC79B) -> 0x75 ; LATIN CAPITAL LETTER U WITH DIAERESIS AND GRAVE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+1DC (0xC79C) -> 0x75 ; LATIN SMALL LETTER U WITH DIAERESIS AND GRAVE   */
	, { (unsigned char *)"\xC7\x9D" , CFLAG_NORMALCHAR }	/* U+1DD (0xC79D) unchanged ; LATIN SMALL LETTER TURNED E   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+1DE (0xC79E) -> 0x61 ; LATIN CAPITAL LETTER A WITH DIAERESIS AND MACRON   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+1DF (0xC79F) -> 0x61 ; LATIN SMALL LETTER A WITH DIAERESIS AND MACRON   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+1E0 (0xC7A0) -> 0x61 ; LATIN CAPITAL LETTER A WITH DOT ABOVE AND MACRON   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+1E1 (0xC7A1) -> 0x61 ; LATIN SMALL LETTER A WITH DOT ABOVE AND MACRON   */
	, { (unsigned char *)"\xC3\xA6" , CFLAG_NORMALCHAR }	/* U+1E2 (0xC7A2) -> 0xC3A6 ; LATIN CAPITAL LETTER AE WITH MACRON   */
	, { (unsigned char *)"\xC3\xA6" , CFLAG_NORMALCHAR }	/* U+1E3 (0xC7A3) -> 0xC3A6 ; LATIN SMALL LETTER AE WITH MACRON   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+1E4 (0xC7A4) -> 0x67 ; LATIN CAPITAL LETTER G WITH STROKE   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+1E5 (0xC7A5) -> 0x67 ; LATIN SMALL LETTER G WITH STROKE   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+1E6 (0xC7A6) -> 0x67 ; LATIN CAPITAL LETTER G WITH CARON   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+1E7 (0xC7A7) -> 0x67 ; LATIN SMALL LETTER G WITH CARON   */
	, { (unsigned char *)"\x6B"     , CFLAG_NORMALCHAR }	/* U+1E8 (0xC7A8) -> 0x6B ; LATIN CAPITAL LETTER K WITH CARON   */
	, { (unsigned char *)"\x6B"     , CFLAG_NORMALCHAR }	/* U+1E9 (0xC7A9) -> 0x6B ; LATIN SMALL LETTER K WITH CARON   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+1EA (0xC7AA) -> 0x6F ; LATIN CAPITAL LETTER O WITH OGONEK   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+1EB (0xC7AB) -> 0x6F ; LATIN SMALL LETTER O WITH OGONEK   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+1EC (0xC7AC) -> 0x6F ; LATIN CAPITAL LETTER O WITH OGONEK AND MACRON   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+1ED (0xC7AD) -> 0x6F ; LATIN SMALL LETTER O WITH OGONEK AND MACRON   */
	, { (unsigned char *)"\xCA\x92" , CFLAG_NORMALCHAR }	/* U+1EE (0xC7AE) -> 0xCA92 ; LATIN CAPITAL LETTER EZH WITH CARON   */
	, { (unsigned char *)"\xCA\x92" , CFLAG_NORMALCHAR }	/* U+1EF (0xC7AF) -> 0xCA92 ; LATIN SMALL LETTER EZH WITH CARON   */
	, { (unsigned char *)"\x6A"     , CFLAG_NORMALCHAR }	/* U+1F0 (0xC7B0) -> 0x6A ; LATIN SMALL LETTER J WITH CARON   */
	, { (unsigned char *)"\xC7\xB3" , CFLAG_NORMALCHAR }	/* U+1F1 (0xC7B1) -> 0xC7B3 ; LATIN CAPITAL LETTER DZ   */
	, { (unsigned char *)"\xC7\xB3" , CFLAG_NORMALCHAR }	/* U+1F2 (0xC7B2) -> 0xC7B3 ; LATIN CAPITAL LETTER D WITH SMALL LETTER Z   */
	, { (unsigned char *)"\xC7\xB3" , CFLAG_NORMALCHAR }	/* U+1F3 (0xC7B3) unchanged ; LATIN SMALL LETTER DZ   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+1F4 (0xC7B4) -> 0x67 ; LATIN CAPITAL LETTER G WITH ACUTE   */
	, { (unsigned char *)"\x67"     , CFLAG_NORMALCHAR }	/* U+1F5 (0xC7B5) -> 0x67 ; LATIN SMALL LETTER G WITH ACUTE   */
	, { (unsigned char *)"\xC6\x95" , CFLAG_NORMALCHAR }	/* U+1F6 (0xC7B6) -> 0xC695 ; LATIN CAPITAL LETTER HWAIR   */
	, { (unsigned char *)"\xC6\xBF" , CFLAG_NORMALCHAR }	/* U+1F7 (0xC7B7) -> 0xC6BF ; LATIN CAPITAL LETTER WYNN   */
	, { (unsigned char *)"\x6E"     , CFLAG_NORMALCHAR }	/* U+1F8 (0xC7B8) -> 0x6E ; LATIN CAPITAL LETTER N WITH GRAVE   */
	, { (unsigned char *)"\x6E"     , CFLAG_NORMALCHAR }	/* U+1F9 (0xC7B9) -> 0x6E ; LATIN SMALL LETTER N WITH GRAVE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+1FA (0xC7BA) -> 0x61 ; LATIN CAPITAL LETTER A WITH RING ABOVE AND ACUTE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+1FB (0xC7BB) -> 0x61 ; LATIN SMALL LETTER A WITH RING ABOVE AND ACUTE   */
	, { (unsigned char *)"\xC3\xA6" , CFLAG_NORMALCHAR }	/* U+1FC (0xC7BC) -> 0xC3A6 ; LATIN CAPITAL LETTER AE WITH ACUTE   */
	, { (unsigned char *)"\xC3\xA6" , CFLAG_NORMALCHAR }	/* U+1FD (0xC7BD) -> 0xC3A6 ; LATIN SMALL LETTER AE WITH ACUTE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+1FE (0xC7BE) -> 0x6F ; LATIN CAPITAL LETTER O WITH STROKE AND ACUTE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+1FF (0xC7BF) -> 0x6F ; LATIN SMALL LETTER O WITH STROKE AND ACUTE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+200 (0xC880) -> 0x61 ; LATIN CAPITAL LETTER A WITH DOUBLE GRAVE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+201 (0xC881) -> 0x61 ; LATIN SMALL LETTER A WITH DOUBLE GRAVE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+202 (0xC882) -> 0x61 ; LATIN CAPITAL LETTER A WITH INVERTED BREVE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+203 (0xC883) -> 0x61 ; LATIN SMALL LETTER A WITH INVERTED BREVE   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+204 (0xC884) -> 0x65 ; LATIN CAPITAL LETTER E WITH DOUBLE GRAVE   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+205 (0xC885) -> 0x65 ; LATIN SMALL LETTER E WITH DOUBLE GRAVE   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+206 (0xC886) -> 0x65 ; LATIN CAPITAL LETTER E WITH INVERTED BREVE   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+207 (0xC887) -> 0x65 ; LATIN SMALL LETTER E WITH INVERTED BREVE   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+208 (0xC888) -> 0x69 ; LATIN CAPITAL LETTER I WITH DOUBLE GRAVE   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+209 (0xC889) -> 0x69 ; LATIN SMALL LETTER I WITH DOUBLE GRAVE   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+20A (0xC88A) -> 0x69 ; LATIN CAPITAL LETTER I WITH INVERTED BREVE   */
	, { (unsigned char *)"\x69"     , CFLAG_NORMALCHAR }	/* U+20B (0xC88B) -> 0x69 ; LATIN SMALL LETTER I WITH INVERTED BREVE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+20C (0xC88C) -> 0x6F ; LATIN CAPITAL LETTER O WITH DOUBLE GRAVE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+20D (0xC88D) -> 0x6F ; LATIN SMALL LETTER O WITH DOUBLE GRAVE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+20E (0xC88E) -> 0x6F ; LATIN CAPITAL LETTER O WITH INVERTED BREVE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+20F (0xC88F) -> 0x6F ; LATIN SMALL LETTER O WITH INVERTED BREVE   */
	, { (unsigned char *)"\x72"     , CFLAG_NORMALCHAR }	/* U+210 (0xC890) -> 0x72 ; LATIN CAPITAL LETTER R WITH DOUBLE GRAVE   */
	, { (unsigned char *)"\x72"     , CFLAG_NORMALCHAR }	/* U+211 (0xC891) -> 0x72 ; LATIN SMALL LETTER R WITH DOUBLE GRAVE   */
	, { (unsigned char *)"\x72"     , CFLAG_NORMALCHAR }	/* U+212 (0xC892) -> 0x72 ; LATIN CAPITAL LETTER R WITH INVERTED BREVE   */
	, { (unsigned char *)"\x72"     , CFLAG_NORMALCHAR }	/* U+213 (0xC893) -> 0x72 ; LATIN SMALL LETTER R WITH INVERTED BREVE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+214 (0xC894) -> 0x75 ; LATIN CAPITAL LETTER U WITH DOUBLE GRAVE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+215 (0xC895) -> 0x75 ; LATIN SMALL LETTER U WITH DOUBLE GRAVE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+216 (0xC896) -> 0x75 ; LATIN CAPITAL LETTER U WITH INVERTED BREVE   */
	, { (unsigned char *)"\x75"     , CFLAG_NORMALCHAR }	/* U+217 (0xC897) -> 0x75 ; LATIN SMALL LETTER U WITH INVERTED BREVE   */
	, { (unsigned char *)"\x73"     , CFLAG_NORMALCHAR }	/* U+218 (0xC898) -> 0x73 ; LATIN CAPITAL LETTER S WITH COMMA BELOW   */
	, { (unsigned char *)"\x73"     , CFLAG_NORMALCHAR }	/* U+219 (0xC899) -> 0x73 ; LATIN SMALL LETTER S WITH COMMA BELOW   */
	, { (unsigned char *)"\x74"     , CFLAG_NORMALCHAR }	/* U+21A (0xC89A) -> 0x74 ; LATIN CAPITAL LETTER T WITH COMMA BELOW   */
	, { (unsigned char *)"\x74"     , CFLAG_NORMALCHAR }	/* U+21B (0xC89B) -> 0x74 ; LATIN SMALL LETTER T WITH COMMA BELOW   */
	, { (unsigned char *)"\xC8\x9D" , CFLAG_NORMALCHAR }	/* U+21C (0xC89C) -> 0xC89D ; LATIN CAPITAL LETTER YOGH   */
	, { (unsigned char *)"\xC8\x9D" , CFLAG_NORMALCHAR }	/* U+21D (0xC89D) unchanged ; LATIN SMALL LETTER YOGH   */
	, { (unsigned char *)"\x68"     , CFLAG_NORMALCHAR }	/* U+21E (0xC89E) -> 0x68 ; LATIN CAPITAL LETTER H WITH CARON   */
	, { (unsigned char *)"\x68"     , CFLAG_NORMALCHAR }	/* U+21F (0xC89F) -> 0x68 ; LATIN SMALL LETTER H WITH CARON   */
	, { (unsigned char *)"\x6E"     , CFLAG_NORMALCHAR }	/* U+220 (0xC8A0) -> 0x6E ; LATIN CAPITAL LETTER N WITH LONG RIGHT LEG   */
	, { (unsigned char *)"\x64"     , CFLAG_NORMALCHAR }	/* U+221 (0xC8A1) -> 0x64 ; LATIN SMALL LETTER D WITH CURL   */
	, { (unsigned char *)"\xC8\xA3" , CFLAG_NORMALCHAR }	/* U+222 (0xC8A2) -> 0xC8A3 ; LATIN CAPITAL LETTER OU   */
	, { (unsigned char *)"\xC8\xA3" , CFLAG_NORMALCHAR }	/* U+223 (0xC8A3) unchanged ; LATIN SMALL LETTER OU   */
	, { (unsigned char *)"\x7A"     , CFLAG_NORMALCHAR }	/* U+224 (0xC8A4) -> 0x7A ; LATIN CAPITAL LETTER Z WITH HOOK   */
	, { (unsigned char *)"\x7A"     , CFLAG_NORMALCHAR }	/* U+225 (0xC8A5) -> 0x7A ; LATIN SMALL LETTER Z WITH HOOK   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+226 (0xC8A6) -> 0x61 ; LATIN CAPITAL LETTER A WITH DOT ABOVE   */
	, { (unsigned char *)"\x61"     , CFLAG_NORMALCHAR }	/* U+227 (0xC8A7) -> 0x61 ; LATIN SMALL LETTER A WITH DOT ABOVE   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+228 (0xC8A8) -> 0x65 ; LATIN CAPITAL LETTER E WITH CEDILLA   */
	, { (unsigned char *)"\x65"     , CFLAG_NORMALCHAR }	/* U+229 (0xC8A9) -> 0x65 ; LATIN SMALL LETTER E WITH CEDILLA   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+22A (0xC8AA) -> 0x6F ; LATIN CAPITAL LETTER O WITH DIAERESIS AND MACRON   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+22B (0xC8AB) -> 0x6F ; LATIN SMALL LETTER O WITH DIAERESIS AND MACRON   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+22C (0xC8AC) -> 0x6F ; LATIN CAPITAL LETTER O WITH TILDE AND MACRON   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+22D (0xC8AD) -> 0x6F ; LATIN SMALL LETTER O WITH TILDE AND MACRON   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+22E (0xC8AE) -> 0x6F ; LATIN CAPITAL LETTER O WITH DOT ABOVE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+22F (0xC8AF) -> 0x6F ; LATIN SMALL LETTER O WITH DOT ABOVE   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+230 (0xC8B0) -> 0x6F ; LATIN CAPITAL LETTER O WITH DOT ABOVE AND MACRON   */
	, { (unsigned char *)"\x6F"     , CFLAG_NORMALCHAR }	/* U+231 (0xC8B1) -> 0x6F ; LATIN SMALL LETTER O WITH DOT ABOVE AND MACRON   */
	, { (unsigned char *)"\x79"     , CFLAG_NORMALCHAR }	/* U+232 (0xC8B2) -> 0x79 ; LATIN CAPITAL LETTER Y WITH MACRON   */
	, { (unsigned char *)"\x79"     , CFLAG_NORMALCHAR }	/* U+233 (0xC8B3) -> 0x79 ; LATIN SMALL LETTER Y WITH MACRON   */
	, { (unsigned char *)"\x6C"     , CFLAG_NORMALCHAR }	/* U+234 (0xC8B4) -> 0x6C ; LATIN SMALL LETTER L WITH CURL   */
	, { (unsigned char *)"\x6E"     , CFLAG_NORMALCHAR }	/* U+235 (0xC8B5) -> 0x6E ; LATIN SMALL LETTER N WITH CURL   */
	, { (unsigned char *)"\x74"     , CFLAG_NORMALCHAR }	/* U+236 (0xC8B6) -> 0x74 ; LATIN SMALL LETTER T WITH CURL   */
	, { (unsigned char *)"\xC8\xB7" , CFLAG_NORMALCHAR }	/* U+237 (0xC8B7) unchanged ; LATIN SMALL LETTER DOTLESS J   */
	, { (unsigned char *)"\xC8\xB8" , CFLAG_NORMALCHAR }	/* U+238 (0xC8B8) unchanged ; LATIN SMALL LETTER DB DIGRAPH   */
	, { (unsigned char *)"\xC8\xB9" , CFLAG_NORMALCHAR }	/* U+239 (0xC8B9) unchanged ; LATIN SMALL LETTER QP DIGRAPH   */
	, { (unsigned char *)"\xC8\xBA" , CFLAG_NORMALCHAR }	/* U+23A (0xC8BA) unchanged ; LATIN CAPITAL LETTER A WITH STROKE   */
	, { (unsigned char *)"\xC8\xBC" , CFLAG_NORMALCHAR }	/* U+23B (0xC8BB) -> 0xC8BC ; LATIN CAPITAL LETTER C WITH STROKE   */
	, { (unsigned char *)"\xC8\xBC" , CFLAG_NORMALCHAR }	/* U+23C (0xC8BC) unchanged ; LATIN SMALL LETTER C WITH STROKE   */
	, { (unsigned char *)"\x6C"     , CFLAG_NORMALCHAR }	/* U+23D (0xC8BD) -> 0x6C ; LATIN CAPITAL LETTER L WITH BAR   */
	, { (unsigned char *)"\xC8\xBE" , CFLAG_NORMALCHAR }	/* U+23E (0xC8BE) unchanged ; LATIN CAPITAL LETTER T WITH DIAGONAL STROKE   */
	, { (unsigned char *)"\xC8\xBF" , CFLAG_NORMALCHAR }	/* U+23F (0xC8BF) unchanged ; LATIN SMALL LETTER S WITH SWASH TAIL   */
	, { (unsigned char *)"\xC9\x80" , CFLAG_NORMALCHAR }	/* U+240 (0xC980) unchanged ; LATIN SMALL LETTER Z WITH SWASH TAIL   */
	, { (unsigned char *)"\xCA\x94" , CFLAG_NORMALCHAR }	/* U+241 (0xC981) -> 0xCA94 ; LATIN CAPITAL LETTER GLOTTAL STOP   */
	, { (unsigned char *)"\xC9\x82" , CFLAG_NORMALCHAR }	/* U+242 (0xC982) unchanged ;    */
	, { (unsigned char *)"\xC9\x83" , CFLAG_NORMALCHAR }	/* U+243 (0xC983) unchanged ;    */
	, { (unsigned char *)"\xC9\x84" , CFLAG_NORMALCHAR }	/* U+244 (0xC984) unchanged ;    */
	, { (unsigned char *)"\xC9\x85" , CFLAG_NORMALCHAR }	/* U+245 (0xC985) unchanged ;    */
	, { (unsigned char *)"\xC9\x86" , CFLAG_NORMALCHAR }	/* U+246 (0xC986) unchanged ;    */
	, { (unsigned char *)"\xC9\x87" , CFLAG_NORMALCHAR }	/* U+247 (0xC987) unchanged ;    */
	, { (unsigned char *)"\xC9\x88" , CFLAG_NORMALCHAR }	/* U+248 (0xC988) unchanged ;    */
	, { (unsigned char *)"\xC9\x89" , CFLAG_NORMALCHAR }	/* U+249 (0xC989) unchanged ;    */
	, { (unsigned char *)"\xC9\x8A" , CFLAG_NORMALCHAR }	/* U+24A (0xC98A) unchanged ;    */
	, { (unsigned char *)"\xC9\x8B" , CFLAG_NORMALCHAR }	/* U+24B (0xC98B) unchanged ;    */
	, { (unsigned char *)"\xC9\x8C" , CFLAG_NORMALCHAR }	/* U+24C (0xC98C) unchanged ;    */
	, { (unsigned char *)"\xC9\x8D" , CFLAG_NORMALCHAR }	/* U+24D (0xC98D) unchanged ;    */
	, { (unsigned char *)"\xC9\x8E" , CFLAG_NORMALCHAR }	/* U+24E (0xC98E) unchanged ;    */
	, { (unsigned char *)"\xC9\x8F" , CFLAG_NORMALCHAR }	/* U+24F (0xC98F) unchanged ;    */
	, { (unsigned char *)"\xC9\x90" , CFLAG_NORMALCHAR }	/* U+250 (0xC990) unchanged ;    */
	, { (unsigned char *)"\xC9\x91" , CFLAG_NORMALCHAR }	/* U+251 (0xC991) unchanged ;    */
	, { (unsigned char *)"\xC9\x92" , CFLAG_NORMALCHAR }	/* U+252 (0xC992) unchanged ;    */
	, { (unsigned char *)"\xC9\x93" , CFLAG_NORMALCHAR }	/* U+253 (0xC993) unchanged ;    */
	, { (unsigned char *)"\xC9\x94" , CFLAG_NORMALCHAR }	/* U+254 (0xC994) unchanged ;    */
	, { (unsigned char *)"\xC9\x95" , CFLAG_NORMALCHAR }	/* U+255 (0xC995) unchanged ;    */
	, { (unsigned char *)"\xC9\x96" , CFLAG_NORMALCHAR }	/* U+256 (0xC996) unchanged ;    */
	, { (unsigned char *)"\xC9\x97" , CFLAG_NORMALCHAR }	/* U+257 (0xC997) unchanged ;    */
	, { (unsigned char *)"\xC9\x98" , CFLAG_NORMALCHAR }	/* U+258 (0xC998) unchanged ;    */
	, { (unsigned char *)"\xC9\x99" , CFLAG_NORMALCHAR }	/* U+259 (0xC999) unchanged ;    */
	, { (unsigned char *)"\xC9\x9A" , CFLAG_NORMALCHAR }	/* U+25A (0xC99A) unchanged ;    */
	, { (unsigned char *)"\xC9\x9B" , CFLAG_NORMALCHAR }	/* U+25B (0xC99B) unchanged ;    */
	, { (unsigned char *)"\xC9\x9C" , CFLAG_NORMALCHAR }	/* U+25C (0xC99C) unchanged ;    */
	, { (unsigned char *)"\xC9\x9D" , CFLAG_NORMALCHAR }	/* U+25D (0xC99D) unchanged ;    */
	, { (unsigned char *)"\xC9\x9E" , CFLAG_NORMALCHAR }	/* U+25E (0xC99E) unchanged ;    */
	, { (unsigned char *)"\xC9\x9F" , CFLAG_NORMALCHAR }	/* U+25F (0xC99F) unchanged ;    */
	, { (unsigned char *)"\xC9\xA0" , CFLAG_NORMALCHAR }	/* U+260 (0xC9A0) unchanged ;    */
	, { (unsigned char *)"\xC9\xA1" , CFLAG_NORMALCHAR }	/* U+261 (0xC9A1) unchanged ;    */
	, { (unsigned char *)"\xC9\xA2" , CFLAG_NORMALCHAR }	/* U+262 (0xC9A2) unchanged ;    */
	, { (unsigned char *)"\xC9\xA3" , CFLAG_NORMALCHAR }	/* U+263 (0xC9A3) unchanged ;    */
	, { (unsigned char *)"\xC9\xA4" , CFLAG_NORMALCHAR }	/* U+264 (0xC9A4) unchanged ;    */
	, { (unsigned char *)"\xC9\xA5" , CFLAG_NORMALCHAR }	/* U+265 (0xC9A5) unchanged ;    */
	, { (unsigned char *)"\xC9\xA6" , CFLAG_NORMALCHAR }	/* U+266 (0xC9A6) unchanged ;    */
	, { (unsigned char *)"\xC9\xA7" , CFLAG_NORMALCHAR }	/* U+267 (0xC9A7) unchanged ;    */
	, { (unsigned char *)"\xC9\xA8" , CFLAG_NORMALCHAR }	/* U+268 (0xC9A8) unchanged ;    */
	, { (unsigned char *)"\xC9\xA9" , CFLAG_NORMALCHAR }	/* U+269 (0xC9A9) unchanged ;    */
	, { (unsigned char *)"\xC9\xAA" , CFLAG_NORMALCHAR }	/* U+26A (0xC9AA) unchanged ;    */
	, { (unsigned char *)"\xC9\xAB" , CFLAG_NORMALCHAR }	/* U+26B (0xC9AB) unchanged ;    */
	, { (unsigned char *)"\xC9\xAC" , CFLAG_NORMALCHAR }	/* U+26C (0xC9AC) unchanged ;    */
	, { (unsigned char *)"\xC9\xAD" , CFLAG_NORMALCHAR }	/* U+26D (0xC9AD) unchanged ;    */
	, { (unsigned char *)"\xC9\xAE" , CFLAG_NORMALCHAR }	/* U+26E (0xC9AE) unchanged ;    */
	, { (unsigned char *)"\xC9\xAF" , CFLAG_NORMALCHAR }	/* U+26F (0xC9AF) unchanged ;    */
	, { (unsigned char *)"\xC9\xB0" , CFLAG_NORMALCHAR }	/* U+270 (0xC9B0) unchanged ;    */
	, { (unsigned char *)"\xC9\xB1" , CFLAG_NORMALCHAR }	/* U+271 (0xC9B1) unchanged ;    */
	, { (unsigned char *)"\xC9\xB2" , CFLAG_NORMALCHAR }	/* U+272 (0xC9B2) unchanged ;    */
	, { (unsigned char *)"\xC9\xB3" , CFLAG_NORMALCHAR }	/* U+273 (0xC9B3) unchanged ;    */
	, { (unsigned char *)"\xC9\xB4" , CFLAG_NORMALCHAR }	/* U+274 (0xC9B4) unchanged ;    */
	, { (unsigned char *)"\xC9\xB5" , CFLAG_NORMALCHAR }	/* U+275 (0xC9B5) unchanged ;    */
	, { (unsigned char *)"\xC9\xB6" , CFLAG_NORMALCHAR }	/* U+276 (0xC9B6) unchanged ;    */
	, { (unsigned char *)"\xC9\xB7" , CFLAG_NORMALCHAR }	/* U+277 (0xC9B7) unchanged ;    */
	, { (unsigned char *)"\xC9\xB8" , CFLAG_NORMALCHAR }	/* U+278 (0xC9B8) unchanged ;    */
	, { (unsigned char *)"\xC9\xB9" , CFLAG_NORMALCHAR }	/* U+279 (0xC9B9) unchanged ;    */
	, { (unsigned char *)"\xC9\xBA" , CFLAG_NORMALCHAR }	/* U+27A (0xC9BA) unchanged ;    */
	, { (unsigned char *)"\xC9\xBB" , CFLAG_NORMALCHAR }	/* U+27B (0xC9BB) unchanged ;    */
	, { (unsigned char *)"\xC9\xBC" , CFLAG_NORMALCHAR }	/* U+27C (0xC9BC) unchanged ;    */
	, { (unsigned char *)"\xC9\xBD" , CFLAG_NORMALCHAR }	/* U+27D (0xC9BD) unchanged ;    */
	, { (unsigned char *)"\xC9\xBE" , CFLAG_NORMALCHAR }	/* U+27E (0xC9BE) unchanged ;    */
	, { (unsigned char *)"\xC9\xBF" , CFLAG_NORMALCHAR }	/* U+27F (0xC9BF) unchanged ;    */
	, { (unsigned char *)"\xCA\x80" , CFLAG_NORMALCHAR }	/* U+280 (0xCA80) unchanged ;    */
	, { (unsigned char *)"\xCA\x81" , CFLAG_NORMALCHAR }	/* U+281 (0xCA81) unchanged ;    */
	, { (unsigned char *)"\xCA\x82" , CFLAG_NORMALCHAR }	/* U+282 (0xCA82) unchanged ;    */
	, { (unsigned char *)"\xCA\x83" , CFLAG_NORMALCHAR }	/* U+283 (0xCA83) unchanged ;    */
	, { (unsigned char *)"\xCA\x84" , CFLAG_NORMALCHAR }	/* U+284 (0xCA84) unchanged ;    */
	, { (unsigned char *)"\xCA\x85" , CFLAG_NORMALCHAR }	/* U+285 (0xCA85) unchanged ;    */
	, { (unsigned char *)"\xCA\x86" , CFLAG_NORMALCHAR }	/* U+286 (0xCA86) unchanged ;    */
	, { (unsigned char *)"\xCA\x87" , CFLAG_NORMALCHAR }	/* U+287 (0xCA87) unchanged ;    */
	, { (unsigned char *)"\xCA\x88" , CFLAG_NORMALCHAR }	/* U+288 (0xCA88) unchanged ;    */
	, { (unsigned char *)"\xCA\x89" , CFLAG_NORMALCHAR }	/* U+289 (0xCA89) unchanged ;    */
	, { (unsigned char *)"\xCA\x8A" , CFLAG_NORMALCHAR }	/* U+28A (0xCA8A) unchanged ;    */
	, { (unsigned char *)"\xCA\x8B" , CFLAG_NORMALCHAR }	/* U+28B (0xCA8B) unchanged ;    */
	, { (unsigned char *)"\xCA\x8C" , CFLAG_NORMALCHAR }	/* U+28C (0xCA8C) unchanged ;    */
	, { (unsigned char *)"\xCA\x8D" , CFLAG_NORMALCHAR }	/* U+28D (0xCA8D) unchanged ;    */
	, { (unsigned char *)"\xCA\x8E" , CFLAG_NORMALCHAR }	/* U+28E (0xCA8E) unchanged ;    */
	, { (unsigned char *)"\xCA\x8F" , CFLAG_NORMALCHAR }	/* U+28F (0xCA8F) unchanged ;    */
	, { (unsigned char *)"\xCA\x90" , CFLAG_NORMALCHAR }	/* U+290 (0xCA90) unchanged ;    */
	, { (unsigned char *)"\xCA\x91" , CFLAG_NORMALCHAR }	/* U+291 (0xCA91) unchanged ;    */
	, { (unsigned char *)"\xCA\x92" , CFLAG_NORMALCHAR }	/* U+292 (0xCA92) unchanged ;    */
	, { (unsigned char *)"\xCA\x93" , CFLAG_NORMALCHAR }	/* U+293 (0xCA93) unchanged ;    */
	, { (unsigned char *)"\xCA\x94" , CFLAG_NORMALCHAR }	/* U+294 (0xCA94) unchanged ;    */
	, { (unsigned char *)"\xCA\x95" , CFLAG_NORMALCHAR }	/* U+295 (0xCA95) unchanged ;    */
	, { (unsigned char *)"\xCA\x96" , CFLAG_NORMALCHAR }	/* U+296 (0xCA96) unchanged ;    */
	, { (unsigned char *)"\xCA\x97" , CFLAG_NORMALCHAR }	/* U+297 (0xCA97) unchanged ;    */
	, { (unsigned char *)"\xCA\x98" , CFLAG_NORMALCHAR }	/* U+298 (0xCA98) unchanged ;    */
	, { (unsigned char *)"\xCA\x99" , CFLAG_NORMALCHAR }	/* U+299 (0xCA99) unchanged ;    */
	, { (unsigned char *)"\xCA\x9A" , CFLAG_NORMALCHAR }	/* U+29A (0xCA9A) unchanged ;    */
	, { (unsigned char *)"\xCA\x9B" , CFLAG_NORMALCHAR }	/* U+29B (0xCA9B) unchanged ;    */
	, { (unsigned char *)"\xCA\x9C" , CFLAG_NORMALCHAR }	/* U+29C (0xCA9C) unchanged ;    */
	, { (unsigned char *)"\xCA\x9D" , CFLAG_NORMALCHAR }	/* U+29D (0xCA9D) unchanged ;    */
	, { (unsigned char *)"\xCA\x9E" , CFLAG_NORMALCHAR }	/* U+29E (0xCA9E) unchanged ;    */
	, { (unsigned char *)"\xCA\x9F" , CFLAG_NORMALCHAR }	/* U+29F (0xCA9F) unchanged ;    */
	, { (unsigned char *)"\xCA\xA0" , CFLAG_NORMALCHAR }	/* U+2A0 (0xCAA0) unchanged ;    */
	, { (unsigned char *)"\xCA\xA1" , CFLAG_NORMALCHAR }	/* U+2A1 (0xCAA1) unchanged ;    */
	, { (unsigned char *)"\xCA\xA2" , CFLAG_NORMALCHAR }	/* U+2A2 (0xCAA2) unchanged ;    */
	, { (unsigned char *)"\xCA\xA3" , CFLAG_NORMALCHAR }	/* U+2A3 (0xCAA3) unchanged ;    */
	, { (unsigned char *)"\xCA\xA4" , CFLAG_NORMALCHAR }	/* U+2A4 (0xCAA4) unchanged ;    */
	, { (unsigned char *)"\xCA\xA5" , CFLAG_NORMALCHAR }	/* U+2A5 (0xCAA5) unchanged ;    */
	, { (unsigned char *)"\xCA\xA6" , CFLAG_NORMALCHAR }	/* U+2A6 (0xCAA6) unchanged ;    */
	, { (unsigned char *)"\xCA\xA7" , CFLAG_NORMALCHAR }	/* U+2A7 (0xCAA7) unchanged ;    */
	, { (unsigned char *)"\xCA\xA8" , CFLAG_NORMALCHAR }	/* U+2A8 (0xCAA8) unchanged ;    */
	, { (unsigned char *)"\xCA\xA9" , CFLAG_NORMALCHAR }	/* U+2A9 (0xCAA9) unchanged ;    */
	, { (unsigned char *)"\xCA\xAA" , CFLAG_NORMALCHAR }	/* U+2AA (0xCAAA) unchanged ;    */
	, { (unsigned char *)"\xCA\xAB" , CFLAG_NORMALCHAR }	/* U+2AB (0xCAAB) unchanged ;    */
	, { (unsigned char *)"\xCA\xAC" , CFLAG_NORMALCHAR }	/* U+2AC (0xCAAC) unchanged ;    */
	, { (unsigned char *)"\xCA\xAD" , CFLAG_NORMALCHAR }	/* U+2AD (0xCAAD) unchanged ;    */
	, { (unsigned char *)"\xCA\xAE" , CFLAG_NORMALCHAR }	/* U+2AE (0xCAAE) unchanged ;    */
	, { (unsigned char *)"\xCA\xAF" , CFLAG_NORMALCHAR }	/* U+2AF (0xCAAF) unchanged ;    */
	, { (unsigned char *)"\xCA\xB0" , CFLAG_NORMALCHAR }	/* U+2B0 (0xCAB0) unchanged ;    */
	, { (unsigned char *)"\xCA\xB1" , CFLAG_NORMALCHAR }	/* U+2B1 (0xCAB1) unchanged ;    */
	, { (unsigned char *)"\xCA\xB2" , CFLAG_NORMALCHAR }	/* U+2B2 (0xCAB2) unchanged ;    */
	, { (unsigned char *)"\xCA\xB3" , CFLAG_NORMALCHAR }	/* U+2B3 (0xCAB3) unchanged ;    */
	, { (unsigned char *)"\xCA\xB4" , CFLAG_NORMALCHAR }	/* U+2B4 (0xCAB4) unchanged ;    */
	, { (unsigned char *)"\xCA\xB5" , CFLAG_NORMALCHAR }	/* U+2B5 (0xCAB5) unchanged ;    */
	, { (unsigned char *)"\xCA\xB6" , CFLAG_NORMALCHAR }	/* U+2B6 (0xCAB6) unchanged ;    */
	, { (unsigned char *)"\xCA\xB7" , CFLAG_NORMALCHAR }	/* U+2B7 (0xCAB7) unchanged ;    */
	, { (unsigned char *)"\xCA\xB8" , CFLAG_NORMALCHAR }	/* U+2B8 (0xCAB8) unchanged ;    */
	, { (unsigned char *)"\xCA\xB9" , CFLAG_NORMALCHAR }	/* U+2B9 (0xCAB9) unchanged ;    */
	, { (unsigned char *)"\xCA\xBA" , CFLAG_NORMALCHAR }	/* U+2BA (0xCABA) unchanged ;    */
	, { (unsigned char *)"\xCA\xBB" , CFLAG_NORMALCHAR }	/* U+2BB (0xCABB) unchanged ;    */
	, { (unsigned char *)"\xCA\xBC" , CFLAG_NORMALCHAR }	/* U+2BC (0xCABC) unchanged ;    */
	, { (unsigned char *)"\xCA\xBD" , CFLAG_NORMALCHAR }	/* U+2BD (0xCABD) unchanged ;    */
	, { (unsigned char *)"\xCA\xBE" , CFLAG_NORMALCHAR }	/* U+2BE (0xCABE) unchanged ;    */
	, { (unsigned char *)"\xCA\xBF" , CFLAG_NORMALCHAR }	/* U+2BF (0xCABF) unchanged ;    */
	, { (unsigned char *)"\xCB\x80" , CFLAG_NORMALCHAR }	/* U+2C0 (0xCB80) unchanged ;    */
	, { (unsigned char *)"\xCB\x81" , CFLAG_NORMALCHAR }	/* U+2C1 (0xCB81) unchanged ;    */
	, { (unsigned char *)"\xCB\x82" , CFLAG_NORMALCHAR }	/* U+2C2 (0xCB82) unchanged ;    */
	, { (unsigned char *)"\xCB\x83" , CFLAG_NORMALCHAR }	/* U+2C3 (0xCB83) unchanged ;    */
	, { (unsigned char *)"\xCB\x84" , CFLAG_NORMALCHAR }	/* U+2C4 (0xCB84) unchanged ;    */
	, { (unsigned char *)"\xCB\x85" , CFLAG_NORMALCHAR }	/* U+2C5 (0xCB85) unchanged ;    */
	, { (unsigned char *)"\xCB\x86" , CFLAG_NORMALCHAR }	/* U+2C6 (0xCB86) unchanged ;    */
	, { (unsigned char *)"\xCB\x87" , CFLAG_NORMALCHAR }	/* U+2C7 (0xCB87) unchanged ;    */
	, { (unsigned char *)"\xCB\x88" , CFLAG_NORMALCHAR }	/* U+2C8 (0xCB88) unchanged ;    */
	, { (unsigned char *)"\xCB\x89" , CFLAG_NORMALCHAR }	/* U+2C9 (0xCB89) unchanged ;    */
	, { (unsigned char *)"\xCB\x8A" , CFLAG_NORMALCHAR }	/* U+2CA (0xCB8A) unchanged ;    */
	, { (unsigned char *)"\xCB\x8B" , CFLAG_NORMALCHAR }	/* U+2CB (0xCB8B) unchanged ;    */
	, { (unsigned char *)"\xCB\x8C" , CFLAG_NORMALCHAR }	/* U+2CC (0xCB8C) unchanged ;    */
	, { (unsigned char *)"\xCB\x8D" , CFLAG_NORMALCHAR }	/* U+2CD (0xCB8D) unchanged ;    */
	, { (unsigned char *)"\xCB\x8E" , CFLAG_NORMALCHAR }	/* U+2CE (0xCB8E) unchanged ;    */
	, { (unsigned char *)"\xCB\x8F" , CFLAG_NORMALCHAR }	/* U+2CF (0xCB8F) unchanged ;    */
	, { (unsigned char *)"\xCB\x90" , CFLAG_NORMALCHAR }	/* U+2D0 (0xCB90) unchanged ;    */
	, { (unsigned char *)"\xCB\x91" , CFLAG_NORMALCHAR }	/* U+2D1 (0xCB91) unchanged ;    */
	, { (unsigned char *)"\xCB\x92" , CFLAG_NORMALCHAR }	/* U+2D2 (0xCB92) unchanged ;    */
	, { (unsigned char *)"\xCB\x93" , CFLAG_NORMALCHAR }	/* U+2D3 (0xCB93) unchanged ;    */
	, { (unsigned char *)"\xCB\x94" , CFLAG_NORMALCHAR }	/* U+2D4 (0xCB94) unchanged ;    */
	, { (unsigned char *)"\xCB\x95" , CFLAG_NORMALCHAR }	/* U+2D5 (0xCB95) unchanged ;    */
	, { (unsigned char *)"\xCB\x96" , CFLAG_NORMALCHAR }	/* U+2D6 (0xCB96) unchanged ;    */
	, { (unsigned char *)"\xCB\x97" , CFLAG_NORMALCHAR }	/* U+2D7 (0xCB97) unchanged ;    */
	, { (unsigned char *)"\xCB\x98" , CFLAG_NORMALCHAR }	/* U+2D8 (0xCB98) unchanged ;    */
	, { (unsigned char *)"\xCB\x99" , CFLAG_NORMALCHAR }	/* U+2D9 (0xCB99) unchanged ;    */
	, { (unsigned char *)"\xCB\x9A" , CFLAG_NORMALCHAR }	/* U+2DA (0xCB9A) unchanged ;    */
	, { (unsigned char *)"\xCB\x9B" , CFLAG_NORMALCHAR }	/* U+2DB (0xCB9B) unchanged ;    */
	, { (unsigned char *)"\xCB\x9C" , CFLAG_NORMALCHAR }	/* U+2DC (0xCB9C) unchanged ;    */
	, { (unsigned char *)"\xCB\x9D" , CFLAG_NORMALCHAR }	/* U+2DD (0xCB9D) unchanged ;    */
	, { (unsigned char *)"\xCB\x9E" , CFLAG_NORMALCHAR }	/* U+2DE (0xCB9E) unchanged ;    */
	, { (unsigned char *)"\xCB\x9F" , CFLAG_NORMALCHAR }	/* U+2DF (0xCB9F) unchanged ;    */
	, { (unsigned char *)"\xCB\xA0" , CFLAG_NORMALCHAR }	/* U+2E0 (0xCBA0) unchanged ;    */
	, { (unsigned char *)"\xCB\xA1" , CFLAG_NORMALCHAR }	/* U+2E1 (0xCBA1) unchanged ;    */
	, { (unsigned char *)"\xCB\xA2" , CFLAG_NORMALCHAR }	/* U+2E2 (0xCBA2) unchanged ;    */
	, { (unsigned char *)"\xCB\xA3" , CFLAG_NORMALCHAR }	/* U+2E3 (0xCBA3) unchanged ;    */
	, { (unsigned char *)"\xCB\xA4" , CFLAG_NORMALCHAR }	/* U+2E4 (0xCBA4) unchanged ;    */
	, { (unsigned char *)"\xCB\xA5" , CFLAG_NORMALCHAR }	/* U+2E5 (0xCBA5) unchanged ;    */
	, { (unsigned char *)"\xCB\xA6" , CFLAG_NORMALCHAR }	/* U+2E6 (0xCBA6) unchanged ;    */
	, { (unsigned char *)"\xCB\xA7" , CFLAG_NORMALCHAR }	/* U+2E7 (0xCBA7) unchanged ;    */
	, { (unsigned char *)"\xCB\xA8" , CFLAG_NORMALCHAR }	/* U+2E8 (0xCBA8) unchanged ;    */
	, { (unsigned char *)"\xCB\xA9" , CFLAG_NORMALCHAR }	/* U+2E9 (0xCBA9) unchanged ;    */
	, { (unsigned char *)"\xCB\xAA" , CFLAG_NORMALCHAR }	/* U+2EA (0xCBAA) unchanged ;    */
	, { (unsigned char *)"\xCB\xAB" , CFLAG_NORMALCHAR }	/* U+2EB (0xCBAB) unchanged ;    */
	, { (unsigned char *)"\xCB\xAC" , CFLAG_NORMALCHAR }	/* U+2EC (0xCBAC) unchanged ;    */
	, { (unsigned char *)"\xCB\xAD" , CFLAG_NORMALCHAR }	/* U+2ED (0xCBAD) unchanged ;    */
	, { (unsigned char *)"\xCB\xAE" , CFLAG_NORMALCHAR }	/* U+2EE (0xCBAE) unchanged ;    */
	, { (unsigned char *)"\xCB\xAF" , CFLAG_NORMALCHAR }	/* U+2EF (0xCBAF) unchanged ;    */
	, { (unsigned char *)"\xCB\xB0" , CFLAG_NORMALCHAR }	/* U+2F0 (0xCBB0) unchanged ;    */
	, { (unsigned char *)"\xCB\xB1" , CFLAG_NORMALCHAR }	/* U+2F1 (0xCBB1) unchanged ;    */
	, { (unsigned char *)"\xCB\xB2" , CFLAG_NORMALCHAR }	/* U+2F2 (0xCBB2) unchanged ;    */
	, { (unsigned char *)"\xCB\xB3" , CFLAG_NORMALCHAR }	/* U+2F3 (0xCBB3) unchanged ;    */
	, { (unsigned char *)"\xCB\xB4" , CFLAG_NORMALCHAR }	/* U+2F4 (0xCBB4) unchanged ;    */
	, { (unsigned char *)"\xCB\xB5" , CFLAG_NORMALCHAR }	/* U+2F5 (0xCBB5) unchanged ;    */
	, { (unsigned char *)"\xCB\xB6" , CFLAG_NORMALCHAR }	/* U+2F6 (0xCBB6) unchanged ;    */
	, { (unsigned char *)"\xCB\xB7" , CFLAG_NORMALCHAR }	/* U+2F7 (0xCBB7) unchanged ;    */
	, { (unsigned char *)"\xCB\xB8" , CFLAG_NORMALCHAR }	/* U+2F8 (0xCBB8) unchanged ;    */
	, { (unsigned char *)"\xCB\xB9" , CFLAG_NORMALCHAR }	/* U+2F9 (0xCBB9) unchanged ;    */
	, { (unsigned char *)"\xCB\xBA" , CFLAG_NORMALCHAR }	/* U+2FA (0xCBBA) unchanged ;    */
	, { (unsigned char *)"\xCB\xBB" , CFLAG_NORMALCHAR }	/* U+2FB (0xCBBB) unchanged ;    */
	, { (unsigned char *)"\xCB\xBC" , CFLAG_NORMALCHAR }	/* U+2FC (0xCBBC) unchanged ;    */
	, { (unsigned char *)"\xCB\xBD" , CFLAG_NORMALCHAR }	/* U+2FD (0xCBBD) unchanged ;    */
	, { (unsigned char *)"\xCB\xBE" , CFLAG_NORMALCHAR }	/* U+2FE (0xCBBE) unchanged ;    */
	, { (unsigned char *)"\xCB\xBF" , CFLAG_NORMALCHAR }	/* U+2FF (0xCBBF) unchanged ;    */
	, { (unsigned char *)"\xCC\x80" , CFLAG_NORMALCHAR }	/* U+300 (0xCC80) unchanged ;    */
	, { (unsigned char *)"\xCC\x81" , CFLAG_NORMALCHAR }	/* U+301 (0xCC81) unchanged ;    */
	, { (unsigned char *)"\xCC\x82" , CFLAG_NORMALCHAR }	/* U+302 (0xCC82) unchanged ;    */
	, { (unsigned char *)"\xCC\x83" , CFLAG_NORMALCHAR }	/* U+303 (0xCC83) unchanged ;    */
	, { (unsigned char *)"\xCC\x84" , CFLAG_NORMALCHAR }	/* U+304 (0xCC84) unchanged ;    */
	, { (unsigned char *)"\xCC\x85" , CFLAG_NORMALCHAR }	/* U+305 (0xCC85) unchanged ;    */
	, { (unsigned char *)"\xCC\x86" , CFLAG_NORMALCHAR }	/* U+306 (0xCC86) unchanged ;    */
	, { (unsigned char *)"\xCC\x87" , CFLAG_NORMALCHAR }	/* U+307 (0xCC87) unchanged ;    */
	, { (unsigned char *)"\xCC\x88" , CFLAG_NORMALCHAR }	/* U+308 (0xCC88) unchanged ;    */
	, { (unsigned char *)"\xCC\x89" , CFLAG_NORMALCHAR }	/* U+309 (0xCC89) unchanged ;    */
	, { (unsigned char *)"\xCC\x8A" , CFLAG_NORMALCHAR }	/* U+30A (0xCC8A) unchanged ;    */
	, { (unsigned char *)"\xCC\x8B" , CFLAG_NORMALCHAR }	/* U+30B (0xCC8B) unchanged ;    */
	, { (unsigned char *)"\xCC\x8C" , CFLAG_NORMALCHAR }	/* U+30C (0xCC8C) unchanged ;    */
	, { (unsigned char *)"\xCC\x8D" , CFLAG_NORMALCHAR }	/* U+30D (0xCC8D) unchanged ;    */
	, { (unsigned char *)"\xCC\x8E" , CFLAG_NORMALCHAR }	/* U+30E (0xCC8E) unchanged ;    */
	, { (unsigned char *)"\xCC\x8F" , CFLAG_NORMALCHAR }	/* U+30F (0xCC8F) unchanged ;    */
	, { (unsigned char *)"\xCC\x90" , CFLAG_NORMALCHAR }	/* U+310 (0xCC90) unchanged ;    */
	, { (unsigned char *)"\xCC\x91" , CFLAG_NORMALCHAR }	/* U+311 (0xCC91) unchanged ;    */
	, { (unsigned char *)"\xCC\x92" , CFLAG_NORMALCHAR }	/* U+312 (0xCC92) unchanged ;    */
	, { (unsigned char *)"\xCC\x93" , CFLAG_NORMALCHAR }	/* U+313 (0xCC93) unchanged ;    */
	, { (unsigned char *)"\xCC\x94" , CFLAG_NORMALCHAR }	/* U+314 (0xCC94) unchanged ;    */
	, { (unsigned char *)"\xCC\x95" , CFLAG_NORMALCHAR }	/* U+315 (0xCC95) unchanged ;    */
	, { (unsigned char *)"\xCC\x96" , CFLAG_NORMALCHAR }	/* U+316 (0xCC96) unchanged ;    */
	, { (unsigned char *)"\xCC\x97" , CFLAG_NORMALCHAR }	/* U+317 (0xCC97) unchanged ;    */
	, { (unsigned char *)"\xCC\x98" , CFLAG_NORMALCHAR }	/* U+318 (0xCC98) unchanged ;    */
	, { (unsigned char *)"\xCC\x99" , CFLAG_NORMALCHAR }	/* U+319 (0xCC99) unchanged ;    */
	, { (unsigned char *)"\xCC\x9A" , CFLAG_NORMALCHAR }	/* U+31A (0xCC9A) unchanged ;    */
	, { (unsigned char *)"\xCC\x9B" , CFLAG_NORMALCHAR }	/* U+31B (0xCC9B) unchanged ;    */
	, { (unsigned char *)"\xCC\x9C" , CFLAG_NORMALCHAR }	/* U+31C (0xCC9C) unchanged ;    */
	, { (unsigned char *)"\xCC\x9D" , CFLAG_NORMALCHAR }	/* U+31D (0xCC9D) unchanged ;    */
	, { (unsigned char *)"\xCC\x9E" , CFLAG_NORMALCHAR }	/* U+31E (0xCC9E) unchanged ;    */
	, { (unsigned char *)"\xCC\x9F" , CFLAG_NORMALCHAR }	/* U+31F (0xCC9F) unchanged ;    */
	, { (unsigned char *)"\xCC\xA0" , CFLAG_NORMALCHAR }	/* U+320 (0xCCA0) unchanged ;    */
	, { (unsigned char *)"\xCC\xA1" , CFLAG_NORMALCHAR }	/* U+321 (0xCCA1) unchanged ;    */
	, { (unsigned char *)"\xCC\xA2" , CFLAG_NORMALCHAR }	/* U+322 (0xCCA2) unchanged ;    */
	, { (unsigned char *)"\xCC\xA3" , CFLAG_NORMALCHAR }	/* U+323 (0xCCA3) unchanged ;    */
	, { (unsigned char *)"\xCC\xA4" , CFLAG_NORMALCHAR }	/* U+324 (0xCCA4) unchanged ;    */
	, { (unsigned char *)"\xCC\xA5" , CFLAG_NORMALCHAR }	/* U+325 (0xCCA5) unchanged ;    */
	, { (unsigned char *)"\xCC\xA6" , CFLAG_NORMALCHAR }	/* U+326 (0xCCA6) unchanged ;    */
	, { (unsigned char *)"\xCC\xA7" , CFLAG_NORMALCHAR }	/* U+327 (0xCCA7) unchanged ;    */
	, { (unsigned char *)"\xCC\xA8" , CFLAG_NORMALCHAR }	/* U+328 (0xCCA8) unchanged ;    */
	, { (unsigned char *)"\xCC\xA9" , CFLAG_NORMALCHAR }	/* U+329 (0xCCA9) unchanged ;    */
	, { (unsigned char *)"\xCC\xAA" , CFLAG_NORMALCHAR }	/* U+32A (0xCCAA) unchanged ;    */
	, { (unsigned char *)"\xCC\xAB" , CFLAG_NORMALCHAR }	/* U+32B (0xCCAB) unchanged ;    */
	, { (unsigned char *)"\xCC\xAC" , CFLAG_NORMALCHAR }	/* U+32C (0xCCAC) unchanged ;    */
	, { (unsigned char *)"\xCC\xAD" , CFLAG_NORMALCHAR }	/* U+32D (0xCCAD) unchanged ;    */
	, { (unsigned char *)"\xCC\xAE" , CFLAG_NORMALCHAR }	/* U+32E (0xCCAE) unchanged ;    */
	, { (unsigned char *)"\xCC\xAF" , CFLAG_NORMALCHAR }	/* U+32F (0xCCAF) unchanged ;    */
	, { (unsigned char *)"\xCC\xB0" , CFLAG_NORMALCHAR }	/* U+330 (0xCCB0) unchanged ;    */
	, { (unsigned char *)"\xCC\xB1" , CFLAG_NORMALCHAR }	/* U+331 (0xCCB1) unchanged ;    */
	, { (unsigned char *)"\xCC\xB2" , CFLAG_NORMALCHAR }	/* U+332 (0xCCB2) unchanged ;    */
	, { (unsigned char *)"\xCC\xB3" , CFLAG_NORMALCHAR }	/* U+333 (0xCCB3) unchanged ;    */
	, { (unsigned char *)"\xCC\xB4" , CFLAG_NORMALCHAR }	/* U+334 (0xCCB4) unchanged ;    */
	, { (unsigned char *)"\xCC\xB5" , CFLAG_NORMALCHAR }	/* U+335 (0xCCB5) unchanged ;    */
	, { (unsigned char *)"\xCC\xB6" , CFLAG_NORMALCHAR }	/* U+336 (0xCCB6) unchanged ;    */
	, { (unsigned char *)"\xCC\xB7" , CFLAG_NORMALCHAR }	/* U+337 (0xCCB7) unchanged ;    */
	, { (unsigned char *)"\xCC\xB8" , CFLAG_NORMALCHAR }	/* U+338 (0xCCB8) unchanged ;    */
	, { (unsigned char *)"\xCC\xB9" , CFLAG_NORMALCHAR }	/* U+339 (0xCCB9) unchanged ;    */
	, { (unsigned char *)"\xCC\xBA" , CFLAG_NORMALCHAR }	/* U+33A (0xCCBA) unchanged ;    */
	, { (unsigned char *)"\xCC\xBB" , CFLAG_NORMALCHAR }	/* U+33B (0xCCBB) unchanged ;    */
	, { (unsigned char *)"\xCC\xBC" , CFLAG_NORMALCHAR }	/* U+33C (0xCCBC) unchanged ;    */
	, { (unsigned char *)"\xCC\xBD" , CFLAG_NORMALCHAR }	/* U+33D (0xCCBD) unchanged ;    */
	, { (unsigned char *)"\xCC\xBE" , CFLAG_NORMALCHAR }	/* U+33E (0xCCBE) unchanged ;    */
	, { (unsigned char *)"\xCC\xBF" , CFLAG_NORMALCHAR }	/* U+33F (0xCCBF) unchanged ;    */
	, { (unsigned char *)"\xCD\x80" , CFLAG_NORMALCHAR }	/* U+340 (0xCD80) unchanged ;    */
	, { (unsigned char *)"\xCD\x81" , CFLAG_NORMALCHAR }	/* U+341 (0xCD81) unchanged ;    */
	, { (unsigned char *)"\xCD\x82" , CFLAG_NORMALCHAR }	/* U+342 (0xCD82) unchanged ;    */
	, { (unsigned char *)"\xCD\x83" , CFLAG_NORMALCHAR }	/* U+343 (0xCD83) unchanged ;    */
	, { (unsigned char *)"\xCD\x84" , CFLAG_NORMALCHAR }	/* U+344 (0xCD84) unchanged ;    */
	, { (unsigned char *)"\xCD\x85" , CFLAG_NORMALCHAR }	/* U+345 (0xCD85) unchanged ;    */
	, { (unsigned char *)"\xCD\x86" , CFLAG_NORMALCHAR }	/* U+346 (0xCD86) unchanged ;    */
	, { (unsigned char *)"\xCD\x87" , CFLAG_NORMALCHAR }	/* U+347 (0xCD87) unchanged ;    */
	, { (unsigned char *)"\xCD\x88" , CFLAG_NORMALCHAR }	/* U+348 (0xCD88) unchanged ;    */
	, { (unsigned char *)"\xCD\x89" , CFLAG_NORMALCHAR }	/* U+349 (0xCD89) unchanged ;    */
	, { (unsigned char *)"\xCD\x8A" , CFLAG_NORMALCHAR }	/* U+34A (0xCD8A) unchanged ;    */
	, { (unsigned char *)"\xCD\x8B" , CFLAG_NORMALCHAR }	/* U+34B (0xCD8B) unchanged ;    */
	, { (unsigned char *)"\xCD\x8C" , CFLAG_NORMALCHAR }	/* U+34C (0xCD8C) unchanged ;    */
	, { (unsigned char *)"\xCD\x8D" , CFLAG_NORMALCHAR }	/* U+34D (0xCD8D) unchanged ;    */
	, { (unsigned char *)"\xCD\x8E" , CFLAG_NORMALCHAR }	/* U+34E (0xCD8E) unchanged ;    */
	, { (unsigned char *)"\xCD\x8F" , CFLAG_NORMALCHAR }	/* U+34F (0xCD8F) unchanged ;    */
	, { (unsigned char *)"\xCD\x90" , CFLAG_NORMALCHAR }	/* U+350 (0xCD90) unchanged ;    */
	, { (unsigned char *)"\xCD\x91" , CFLAG_NORMALCHAR }	/* U+351 (0xCD91) unchanged ;    */
	, { (unsigned char *)"\xCD\x92" , CFLAG_NORMALCHAR }	/* U+352 (0xCD92) unchanged ;    */
	, { (unsigned char *)"\xCD\x93" , CFLAG_NORMALCHAR }	/* U+353 (0xCD93) unchanged ;    */
	, { (unsigned char *)"\xCD\x94" , CFLAG_NORMALCHAR }	/* U+354 (0xCD94) unchanged ;    */
	, { (unsigned char *)"\xCD\x95" , CFLAG_NORMALCHAR }	/* U+355 (0xCD95) unchanged ;    */
	, { (unsigned char *)"\xCD\x96" , CFLAG_NORMALCHAR }	/* U+356 (0xCD96) unchanged ;    */
	, { (unsigned char *)"\xCD\x97" , CFLAG_NORMALCHAR }	/* U+357 (0xCD97) unchanged ;    */
	, { (unsigned char *)"\xCD\x98" , CFLAG_NORMALCHAR }	/* U+358 (0xCD98) unchanged ;    */
	, { (unsigned char *)"\xCD\x99" , CFLAG_NORMALCHAR }	/* U+359 (0xCD99) unchanged ;    */
	, { (unsigned char *)"\xCD\x9A" , CFLAG_NORMALCHAR }	/* U+35A (0xCD9A) unchanged ;    */
	, { (unsigned char *)"\xCD\x9B" , CFLAG_NORMALCHAR }	/* U+35B (0xCD9B) unchanged ;    */
	, { (unsigned char *)"\xCD\x9C" , CFLAG_NORMALCHAR }	/* U+35C (0xCD9C) unchanged ;    */
	, { (unsigned char *)"\xCD\x9D" , CFLAG_NORMALCHAR }	/* U+35D (0xCD9D) unchanged ;    */
	, { (unsigned char *)"\xCD\x9E" , CFLAG_NORMALCHAR }	/* U+35E (0xCD9E) unchanged ;    */
	, { (unsigned char *)"\xCD\x9F" , CFLAG_NORMALCHAR }	/* U+35F (0xCD9F) unchanged ;    */
	, { (unsigned char *)"\xCD\xA0" , CFLAG_NORMALCHAR }	/* U+360 (0xCDA0) unchanged ;    */
	, { (unsigned char *)"\xCD\xA1" , CFLAG_NORMALCHAR }	/* U+361 (0xCDA1) unchanged ;    */
	, { (unsigned char *)"\xCD\xA2" , CFLAG_NORMALCHAR }	/* U+362 (0xCDA2) unchanged ;    */
	, { (unsigned char *)"\xCD\xA3" , CFLAG_NORMALCHAR }	/* U+363 (0xCDA3) unchanged ;    */
	, { (unsigned char *)"\xCD\xA4" , CFLAG_NORMALCHAR }	/* U+364 (0xCDA4) unchanged ;    */
	, { (unsigned char *)"\xCD\xA5" , CFLAG_NORMALCHAR }	/* U+365 (0xCDA5) unchanged ;    */
	, { (unsigned char *)"\xCD\xA6" , CFLAG_NORMALCHAR }	/* U+366 (0xCDA6) unchanged ;    */
	, { (unsigned char *)"\xCD\xA7" , CFLAG_NORMALCHAR }	/* U+367 (0xCDA7) unchanged ;    */
	, { (unsigned char *)"\xCD\xA8" , CFLAG_NORMALCHAR }	/* U+368 (0xCDA8) unchanged ;    */
	, { (unsigned char *)"\xCD\xA9" , CFLAG_NORMALCHAR }	/* U+369 (0xCDA9) unchanged ;    */
	, { (unsigned char *)"\xCD\xAA" , CFLAG_NORMALCHAR }	/* U+36A (0xCDAA) unchanged ;    */
	, { (unsigned char *)"\xCD\xAB" , CFLAG_NORMALCHAR }	/* U+36B (0xCDAB) unchanged ;    */
	, { (unsigned char *)"\xCD\xAC" , CFLAG_NORMALCHAR }	/* U+36C (0xCDAC) unchanged ;    */
	, { (unsigned char *)"\xCD\xAD" , CFLAG_NORMALCHAR }	/* U+36D (0xCDAD) unchanged ;    */
	, { (unsigned char *)"\xCD\xAE" , CFLAG_NORMALCHAR }	/* U+36E (0xCDAE) unchanged ;    */
	, { (unsigned char *)"\xCD\xAF" , CFLAG_NORMALCHAR }	/* U+36F (0xCDAF) unchanged ;    */
	, { (unsigned char *)"\xCD\xB0" , CFLAG_NORMALCHAR }	/* U+370 (0xCDB0) unchanged ;    */
	, { (unsigned char *)"\xCD\xB1" , CFLAG_NORMALCHAR }	/* U+371 (0xCDB1) unchanged ;    */
	, { (unsigned char *)"\xCD\xB2" , CFLAG_NORMALCHAR }	/* U+372 (0xCDB2) unchanged ;    */
	, { (unsigned char *)"\xCD\xB3" , CFLAG_NORMALCHAR }	/* U+373 (0xCDB3) unchanged ;    */
	, { (unsigned char *)"\xCD\xB4" , CFLAG_NORMALCHAR }	/* U+374 (0xCDB4) unchanged ;    */
	, { (unsigned char *)"\xCD\xB5" , CFLAG_NORMALCHAR }	/* U+375 (0xCDB5) unchanged ;    */
	, { (unsigned char *)"\xCD\xB6" , CFLAG_NORMALCHAR }	/* U+376 (0xCDB6) unchanged ;    */
	, { (unsigned char *)"\xCD\xB7" , CFLAG_NORMALCHAR }	/* U+377 (0xCDB7) unchanged ;    */
	, { (unsigned char *)"\xCD\xB8" , CFLAG_NORMALCHAR }	/* U+378 (0xCDB8) unchanged ;    */
	, { (unsigned char *)"\xCD\xB9" , CFLAG_NORMALCHAR }	/* U+379 (0xCDB9) unchanged ;    */
	, { (unsigned char *)"\xCD\xBA" , CFLAG_NORMALCHAR }	/* U+37A (0xCDBA) unchanged ;    */
	, { (unsigned char *)"\xCD\xBB" , CFLAG_NORMALCHAR }	/* U+37B (0xCDBB) unchanged ;    */
	, { (unsigned char *)"\xCD\xBC" , CFLAG_NORMALCHAR }	/* U+37C (0xCDBC) unchanged ;    */
	, { (unsigned char *)"\xCD\xBD" , CFLAG_NORMALCHAR }	/* U+37D (0xCDBD) unchanged ;    */
	, { (unsigned char *)"\xCD\xBE" , CFLAG_NORMALCHAR }	/* U+37E (0xCDBE) unchanged ;    */
	, { (unsigned char *)"\xCD\xBF" , CFLAG_NORMALCHAR }	/* U+37F (0xCDBF) unchanged ;    */
	, { (unsigned char *)"\xCE\x80" , CFLAG_NORMALCHAR }	/* U+380 (0xCE80) unchanged ;    */
	, { (unsigned char *)"\xCE\x81" , CFLAG_NORMALCHAR }	/* U+381 (0xCE81) unchanged ;    */
	, { (unsigned char *)"\xCE\x82" , CFLAG_NORMALCHAR }	/* U+382 (0xCE82) unchanged ;    */
	, { (unsigned char *)"\xCE\x83" , CFLAG_NORMALCHAR }	/* U+383 (0xCE83) unchanged ;    */
	, { (unsigned char *)"\xCE\x84" , CFLAG_NORMALCHAR }	/* U+384 (0xCE84) unchanged ;    */
	, { (unsigned char *)"\xCE\x85" , CFLAG_NORMALCHAR }	/* U+385 (0xCE85) unchanged ;    */
	, { (unsigned char *)"\xCE\x86" , CFLAG_NORMALCHAR }	/* U+386 (0xCE86) unchanged ;    */
	, { (unsigned char *)"\xCE\x87" , CFLAG_NORMALCHAR }	/* U+387 (0xCE87) unchanged ;    */
	, { (unsigned char *)"\xCE\x88" , CFLAG_NORMALCHAR }	/* U+388 (0xCE88) unchanged ;    */
	, { (unsigned char *)"\xCE\x89" , CFLAG_NORMALCHAR }	/* U+389 (0xCE89) unchanged ;    */
	, { (unsigned char *)"\xCE\x8A" , CFLAG_NORMALCHAR }	/* U+38A (0xCE8A) unchanged ;    */
	, { (unsigned char *)"\xCE\x8B" , CFLAG_NORMALCHAR }	/* U+38B (0xCE8B) unchanged ;    */
	, { (unsigned char *)"\xCE\x8C" , CFLAG_NORMALCHAR }	/* U+38C (0xCE8C) unchanged ;    */
	, { (unsigned char *)"\xCE\x8D" , CFLAG_NORMALCHAR }	/* U+38D (0xCE8D) unchanged ;    */
	, { (unsigned char *)"\xCE\x8E" , CFLAG_NORMALCHAR }	/* U+38E (0xCE8E) unchanged ;    */
	, { (unsigned char *)"\xCE\x8F" , CFLAG_NORMALCHAR }	/* U+38F (0xCE8F) unchanged ;    */
	, { (unsigned char *)"\xCE\x90" , CFLAG_NORMALCHAR }	/* U+390 (0xCE90) unchanged ;    */
	, { (unsigned char *)"\xCE\x91" , CFLAG_NORMALCHAR }	/* U+391 (0xCE91) unchanged ;    */
	, { (unsigned char *)"\xCE\x92" , CFLAG_NORMALCHAR }	/* U+392 (0xCE92) unchanged ;    */
	, { (unsigned char *)"\xCE\x93" , CFLAG_NORMALCHAR }	/* U+393 (0xCE93) unchanged ;    */
	, { (unsigned char *)"\xCE\x94" , CFLAG_NORMALCHAR }	/* U+394 (0xCE94) unchanged ;    */
	, { (unsigned char *)"\xCE\x95" , CFLAG_NORMALCHAR }	/* U+395 (0xCE95) unchanged ;    */
	, { (unsigned char *)"\xCE\x96" , CFLAG_NORMALCHAR }	/* U+396 (0xCE96) unchanged ;    */
	, { (unsigned char *)"\xCE\x97" , CFLAG_NORMALCHAR }	/* U+397 (0xCE97) unchanged ;    */
	, { (unsigned char *)"\xCE\x98" , CFLAG_NORMALCHAR }	/* U+398 (0xCE98) unchanged ;    */
	, { (unsigned char *)"\xCE\x99" , CFLAG_NORMALCHAR }	/* U+399 (0xCE99) unchanged ;    */
	, { (unsigned char *)"\xCE\x9A" , CFLAG_NORMALCHAR }	/* U+39A (0xCE9A) unchanged ;    */
	, { (unsigned char *)"\xCE\x9B" , CFLAG_NORMALCHAR }	/* U+39B (0xCE9B) unchanged ;    */
	, { (unsigned char *)"\xCE\x9C" , CFLAG_NORMALCHAR }	/* U+39C (0xCE9C) unchanged ;    */
	, { (unsigned char *)"\xCE\x9D" , CFLAG_NORMALCHAR }	/* U+39D (0xCE9D) unchanged ;    */
	, { (unsigned char *)"\xCE\x9E" , CFLAG_NORMALCHAR }	/* U+39E (0xCE9E) unchanged ;    */
	, { (unsigned char *)"\xCE\x9F" , CFLAG_NORMALCHAR }	/* U+39F (0xCE9F) unchanged ;    */
	, { (unsigned char *)"\xCE\xA0" , CFLAG_NORMALCHAR }	/* U+3A0 (0xCEA0) unchanged ;    */
	, { (unsigned char *)"\xCE\xA1" , CFLAG_NORMALCHAR }	/* U+3A1 (0xCEA1) unchanged ;    */
	, { (unsigned char *)"\xCE\xA2" , CFLAG_NORMALCHAR }	/* U+3A2 (0xCEA2) unchanged ;    */
	, { (unsigned char *)"\xCE\xA3" , CFLAG_NORMALCHAR }	/* U+3A3 (0xCEA3) unchanged ;    */
	, { (unsigned char *)"\xCE\xA4" , CFLAG_NORMALCHAR }	/* U+3A4 (0xCEA4) unchanged ;    */
	, { (unsigned char *)"\xCE\xA5" , CFLAG_NORMALCHAR }	/* U+3A5 (0xCEA5) unchanged ;    */
	, { (unsigned char *)"\xCE\xA6" , CFLAG_NORMALCHAR }	/* U+3A6 (0xCEA6) unchanged ;    */
	, { (unsigned char *)"\xCE\xA7" , CFLAG_NORMALCHAR }	/* U+3A7 (0xCEA7) unchanged ;    */
	, { (unsigned char *)"\xCE\xA8" , CFLAG_NORMALCHAR }	/* U+3A8 (0xCEA8) unchanged ;    */
	, { (unsigned char *)"\xCE\xA9" , CFLAG_NORMALCHAR }	/* U+3A9 (0xCEA9) unchanged ;    */
	, { (unsigned char *)"\xCE\xAA" , CFLAG_NORMALCHAR }	/* U+3AA (0xCEAA) unchanged ;    */
	, { (unsigned char *)"\xCE\xAB" , CFLAG_NORMALCHAR }	/* U+3AB (0xCEAB) unchanged ;    */
	, { (unsigned char *)"\xCE\xAC" , CFLAG_NORMALCHAR }	/* U+3AC (0xCEAC) unchanged ;    */
	, { (unsigned char *)"\xCE\xAD" , CFLAG_NORMALCHAR }	/* U+3AD (0xCEAD) unchanged ;    */
	, { (unsigned char *)"\xCE\xAE" , CFLAG_NORMALCHAR }	/* U+3AE (0xCEAE) unchanged ;    */
	, { (unsigned char *)"\xCE\xAF" , CFLAG_NORMALCHAR }	/* U+3AF (0xCEAF) unchanged ;    */
	, { (unsigned char *)"\xCE\xB0" , CFLAG_NORMALCHAR }	/* U+3B0 (0xCEB0) unchanged ;    */
	, { (unsigned char *)"\xCE\xB1" , CFLAG_NORMALCHAR }	/* U+3B1 (0xCEB1) unchanged ;    */
	, { (unsigned char *)"\xCE\xB2" , CFLAG_NORMALCHAR }	/* U+3B2 (0xCEB2) unchanged ;    */
	, { (unsigned char *)"\xCE\xB3" , CFLAG_NORMALCHAR }	/* U+3B3 (0xCEB3) unchanged ;    */
	, { (unsigned char *)"\xCE\xB4" , CFLAG_NORMALCHAR }	/* U+3B4 (0xCEB4) unchanged ;    */
	, { (unsigned char *)"\xCE\xB5" , CFLAG_NORMALCHAR }	/* U+3B5 (0xCEB5) unchanged ;    */
	, { (unsigned char *)"\xCE\xB6" , CFLAG_NORMALCHAR }	/* U+3B6 (0xCEB6) unchanged ;    */
	, { (unsigned char *)"\xCE\xB7" , CFLAG_NORMALCHAR }	/* U+3B7 (0xCEB7) unchanged ;    */
	, { (unsigned char *)"\xCE\xB8" , CFLAG_NORMALCHAR }	/* U+3B8 (0xCEB8) unchanged ;    */
	, { (unsigned char *)"\xCE\xB9" , CFLAG_NORMALCHAR }	/* U+3B9 (0xCEB9) unchanged ;    */
	, { (unsigned char *)"\xCE\xBA" , CFLAG_NORMALCHAR }	/* U+3BA (0xCEBA) unchanged ;    */
	, { (unsigned char *)"\xCE\xBB" , CFLAG_NORMALCHAR }	/* U+3BB (0xCEBB) unchanged ;    */
	, { (unsigned char *)"\xCE\xBC" , CFLAG_NORMALCHAR }	/* U+3BC (0xCEBC) unchanged ;    */
	, { (unsigned char *)"\xCE\xBD" , CFLAG_NORMALCHAR }	/* U+3BD (0xCEBD) unchanged ;    */
	, { (unsigned char *)"\xCE\xBE" , CFLAG_NORMALCHAR }	/* U+3BE (0xCEBE) unchanged ;    */
	, { (unsigned char *)"\xCE\xBF" , CFLAG_NORMALCHAR }	/* U+3BF (0xCEBF) unchanged ;    */
	, { (unsigned char *)"\xCF\x80" , CFLAG_NORMALCHAR }	/* U+3C0 (0xCF80) unchanged ;    */
	, { (unsigned char *)"\xCF\x81" , CFLAG_NORMALCHAR }	/* U+3C1 (0xCF81) unchanged ;    */
	, { (unsigned char *)"\xCF\x82" , CFLAG_NORMALCHAR }	/* U+3C2 (0xCF82) unchanged ;    */
	, { (unsigned char *)"\xCF\x83" , CFLAG_NORMALCHAR }	/* U+3C3 (0xCF83) unchanged ;    */
	, { (unsigned char *)"\xCF\x84" , CFLAG_NORMALCHAR }	/* U+3C4 (0xCF84) unchanged ;    */
	, { (unsigned char *)"\xCF\x85" , CFLAG_NORMALCHAR }	/* U+3C5 (0xCF85) unchanged ;    */
	, { (unsigned char *)"\xCF\x86" , CFLAG_NORMALCHAR }	/* U+3C6 (0xCF86) unchanged ;    */
	, { (unsigned char *)"\xCF\x87" , CFLAG_NORMALCHAR }	/* U+3C7 (0xCF87) unchanged ;    */
	, { (unsigned char *)"\xCF\x88" , CFLAG_NORMALCHAR }	/* U+3C8 (0xCF88) unchanged ;    */
	, { (unsigned char *)"\xCF\x89" , CFLAG_NORMALCHAR }	/* U+3C9 (0xCF89) unchanged ;    */
	, { (unsigned char *)"\xCF\x8A" , CFLAG_NORMALCHAR }	/* U+3CA (0xCF8A) unchanged ;    */
	, { (unsigned char *)"\xCF\x8B" , CFLAG_NORMALCHAR }	/* U+3CB (0xCF8B) unchanged ;    */
	, { (unsigned char *)"\xCF\x8C" , CFLAG_NORMALCHAR }	/* U+3CC (0xCF8C) unchanged ;    */
	, { (unsigned char *)"\xCF\x8D" , CFLAG_NORMALCHAR }	/* U+3CD (0xCF8D) unchanged ;    */
	, { (unsigned char *)"\xCF\x8E" , CFLAG_NORMALCHAR }	/* U+3CE (0xCF8E) unchanged ;    */
	, { (unsigned char *)"\xCF\x8F" , CFLAG_NORMALCHAR }	/* U+3CF (0xCF8F) unchanged ;    */
	, { (unsigned char *)"\xCF\x90" , CFLAG_NORMALCHAR }	/* U+3D0 (0xCF90) unchanged ;    */
	, { (unsigned char *)"\xCF\x91" , CFLAG_NORMALCHAR }	/* U+3D1 (0xCF91) unchanged ;    */
	, { (unsigned char *)"\xCF\x92" , CFLAG_NORMALCHAR }	/* U+3D2 (0xCF92) unchanged ;    */
	, { (unsigned char *)"\xCF\x93" , CFLAG_NORMALCHAR }	/* U+3D3 (0xCF93) unchanged ;    */
	, { (unsigned char *)"\xCF\x94" , CFLAG_NORMALCHAR }	/* U+3D4 (0xCF94) unchanged ;    */
	, { (unsigned char *)"\xCF\x95" , CFLAG_NORMALCHAR }	/* U+3D5 (0xCF95) unchanged ;    */
	, { (unsigned char *)"\xCF\x96" , CFLAG_NORMALCHAR }	/* U+3D6 (0xCF96) unchanged ;    */
	, { (unsigned char *)"\xCF\x97" , CFLAG_NORMALCHAR }	/* U+3D7 (0xCF97) unchanged ;    */
	, { (unsigned char *)"\xCF\x98" , CFLAG_NORMALCHAR }	/* U+3D8 (0xCF98) unchanged ;    */
	, { (unsigned char *)"\xCF\x99" , CFLAG_NORMALCHAR }	/* U+3D9 (0xCF99) unchanged ;    */
	, { (unsigned char *)"\xCF\x9A" , CFLAG_NORMALCHAR }	/* U+3DA (0xCF9A) unchanged ;    */
	, { (unsigned char *)"\xCF\x9B" , CFLAG_NORMALCHAR }	/* U+3DB (0xCF9B) unchanged ;    */
	, { (unsigned char *)"\xCF\x9C" , CFLAG_NORMALCHAR }	/* U+3DC (0xCF9C) unchanged ;    */
	, { (unsigned char *)"\xCF\x9D" , CFLAG_NORMALCHAR }	/* U+3DD (0xCF9D) unchanged ;    */
	, { (unsigned char *)"\xCF\x9E" , CFLAG_NORMALCHAR }	/* U+3DE (0xCF9E) unchanged ;    */
	, { (unsigned char *)"\xCF\x9F" , CFLAG_NORMALCHAR }	/* U+3DF (0xCF9F) unchanged ;    */
	, { (unsigned char *)"\xCF\xA0" , CFLAG_NORMALCHAR }	/* U+3E0 (0xCFA0) unchanged ;    */
	, { (unsigned char *)"\xCF\xA1" , CFLAG_NORMALCHAR }	/* U+3E1 (0xCFA1) unchanged ;    */
	, { (unsigned char *)"\xCF\xA2" , CFLAG_NORMALCHAR }	/* U+3E2 (0xCFA2) unchanged ;    */
	, { (unsigned char *)"\xCF\xA3" , CFLAG_NORMALCHAR }	/* U+3E3 (0xCFA3) unchanged ;    */
	, { (unsigned char *)"\xCF\xA4" , CFLAG_NORMALCHAR }	/* U+3E4 (0xCFA4) unchanged ;    */
	, { (unsigned char *)"\xCF\xA5" , CFLAG_NORMALCHAR }	/* U+3E5 (0xCFA5) unchanged ;    */
	, { (unsigned char *)"\xCF\xA6" , CFLAG_NORMALCHAR }	/* U+3E6 (0xCFA6) unchanged ;    */
	, { (unsigned char *)"\xCF\xA7" , CFLAG_NORMALCHAR }	/* U+3E7 (0xCFA7) unchanged ;    */
	, { (unsigned char *)"\xCF\xA8" , CFLAG_NORMALCHAR }	/* U+3E8 (0xCFA8) unchanged ;    */
	, { (unsigned char *)"\xCF\xA9" , CFLAG_NORMALCHAR }	/* U+3E9 (0xCFA9) unchanged ;    */
	, { (unsigned char *)"\xCF\xAA" , CFLAG_NORMALCHAR }	/* U+3EA (0xCFAA) unchanged ;    */
	, { (unsigned char *)"\xCF\xAB" , CFLAG_NORMALCHAR }	/* U+3EB (0xCFAB) unchanged ;    */
	, { (unsigned char *)"\xCF\xAC" , CFLAG_NORMALCHAR }	/* U+3EC (0xCFAC) unchanged ;    */
	, { (unsigned char *)"\xCF\xAD" , CFLAG_NORMALCHAR }	/* U+3ED (0xCFAD) unchanged ;    */
	, { (unsigned char *)"\xCF\xAE" , CFLAG_NORMALCHAR }	/* U+3EE (0xCFAE) unchanged ;    */
	, { (unsigned char *)"\xCF\xAF" , CFLAG_NORMALCHAR }	/* U+3EF (0xCFAF) unchanged ;    */
	, { (unsigned char *)"\xCF\xB0" , CFLAG_NORMALCHAR }	/* U+3F0 (0xCFB0) unchanged ;    */
	, { (unsigned char *)"\xCF\xB1" , CFLAG_NORMALCHAR }	/* U+3F1 (0xCFB1) unchanged ;    */
	, { (unsigned char *)"\xCF\xB2" , CFLAG_NORMALCHAR }	/* U+3F2 (0xCFB2) unchanged ;    */
	, { (unsigned char *)"\xCF\xB3" , CFLAG_NORMALCHAR }	/* U+3F3 (0xCFB3) unchanged ;    */
	, { (unsigned char *)"\xCF\xB4" , CFLAG_NORMALCHAR }	/* U+3F4 (0xCFB4) unchanged ;    */
	, { (unsigned char *)"\xCF\xB5" , CFLAG_NORMALCHAR }	/* U+3F5 (0xCFB5) unchanged ;    */
	, { (unsigned char *)"\xCF\xB6" , CFLAG_NORMALCHAR }	/* U+3F6 (0xCFB6) unchanged ;    */
	, { (unsigned char *)"\xCF\xB7" , CFLAG_NORMALCHAR }	/* U+3F7 (0xCFB7) unchanged ;    */
	, { (unsigned char *)"\xCF\xB8" , CFLAG_NORMALCHAR }	/* U+3F8 (0xCFB8) unchanged ;    */
	, { (unsigned char *)"\xCF\xB9" , CFLAG_NORMALCHAR }	/* U+3F9 (0xCFB9) unchanged ;    */
	, { (unsigned char *)"\xCF\xBA" , CFLAG_NORMALCHAR }	/* U+3FA (0xCFBA) unchanged ;    */
	, { (unsigned char *)"\xCF\xBB" , CFLAG_NORMALCHAR }	/* U+3FB (0xCFBB) unchanged ;    */
	, { (unsigned char *)"\xCF\xBC" , CFLAG_NORMALCHAR }	/* U+3FC (0xCFBC) unchanged ;    */
	, { (unsigned char *)"\xCF\xBD" , CFLAG_NORMALCHAR }	/* U+3FD (0xCFBD) unchanged ;    */
	, { (unsigned char *)"\xCF\xBE" , CFLAG_NORMALCHAR }	/* U+3FE (0xCFBE) unchanged ;    */
	, { (unsigned char *)"\xCF\xBF" , CFLAG_NORMALCHAR }	/* U+3FF (0xCFBF) unchanged ;    */
	, { (unsigned char *)"\xD0\x80" , CFLAG_NORMALCHAR }	/* U+400 (0xD080) unchanged ;    */
	, { (unsigned char *)"\xD0\x81" , CFLAG_NORMALCHAR }	/* U+401 (0xD081) unchanged ;    */
	, { (unsigned char *)"\xD0\x82" , CFLAG_NORMALCHAR }	/* U+402 (0xD082) unchanged ;    */
	, { (unsigned char *)"\xD0\x83" , CFLAG_NORMALCHAR }	/* U+403 (0xD083) unchanged ;    */
	, { (unsigned char *)"\xD0\x84" , CFLAG_NORMALCHAR }	/* U+404 (0xD084) unchanged ;    */
	, { (unsigned char *)"\xD0\x85" , CFLAG_NORMALCHAR }	/* U+405 (0xD085) unchanged ;    */
	, { (unsigned char *)"\xD0\x86" , CFLAG_NORMALCHAR }	/* U+406 (0xD086) unchanged ;    */
	, { (unsigned char *)"\xD0\x87" , CFLAG_NORMALCHAR }	/* U+407 (0xD087) unchanged ;    */
	, { (unsigned char *)"\xD0\x88" , CFLAG_NORMALCHAR }	/* U+408 (0xD088) unchanged ;    */
	, { (unsigned char *)"\xD0\x89" , CFLAG_NORMALCHAR }	/* U+409 (0xD089) unchanged ;    */
	, { (unsigned char *)"\xD0\x8A" , CFLAG_NORMALCHAR }	/* U+40A (0xD08A) unchanged ;    */
	, { (unsigned char *)"\xD0\x8B" , CFLAG_NORMALCHAR }	/* U+40B (0xD08B) unchanged ;    */
	, { (unsigned char *)"\xD0\x8C" , CFLAG_NORMALCHAR }	/* U+40C (0xD08C) unchanged ;    */
	, { (unsigned char *)"\xD0\x8D" , CFLAG_NORMALCHAR }	/* U+40D (0xD08D) unchanged ;    */
	, { (unsigned char *)"\xD0\x8E" , CFLAG_NORMALCHAR }	/* U+40E (0xD08E) unchanged ;    */
	, { (unsigned char *)"\xD0\x8F" , CFLAG_NORMALCHAR }	/* U+40F (0xD08F) unchanged ;    */
	, { (unsigned char *)"\xD0\x90" , CFLAG_NORMALCHAR }	/* U+410 (0xD090) unchanged ;    */
	, { (unsigned char *)"\xD0\x91" , CFLAG_NORMALCHAR }	/* U+411 (0xD091) unchanged ;    */
	, { (unsigned char *)"\xD0\x92" , CFLAG_NORMALCHAR }	/* U+412 (0xD092) unchanged ;    */
	, { (unsigned char *)"\xD0\x93" , CFLAG_NORMALCHAR }	/* U+413 (0xD093) unchanged ;    */
	, { (unsigned char *)"\xD0\x94" , CFLAG_NORMALCHAR }	/* U+414 (0xD094) unchanged ;    */
	, { (unsigned char *)"\xD0\x95" , CFLAG_NORMALCHAR }	/* U+415 (0xD095) unchanged ;    */
	, { (unsigned char *)"\xD0\x96" , CFLAG_NORMALCHAR }	/* U+416 (0xD096) unchanged ;    */
	, { (unsigned char *)"\xD0\x97" , CFLAG_NORMALCHAR }	/* U+417 (0xD097) unchanged ;    */
	, { (unsigned char *)"\xD0\x98" , CFLAG_NORMALCHAR }	/* U+418 (0xD098) unchanged ;    */
	, { (unsigned char *)"\xD0\x99" , CFLAG_NORMALCHAR }	/* U+419 (0xD099) unchanged ;    */
	, { (unsigned char *)"\xD0\x9A" , CFLAG_NORMALCHAR }	/* U+41A (0xD09A) unchanged ;    */
	, { (unsigned char *)"\xD0\x9B" , CFLAG_NORMALCHAR }	/* U+41B (0xD09B) unchanged ;    */
	, { (unsigned char *)"\xD0\x9C" , CFLAG_NORMALCHAR }	/* U+41C (0xD09C) unchanged ;    */
	, { (unsigned char *)"\xD0\x9D" , CFLAG_NORMALCHAR }	/* U+41D (0xD09D) unchanged ;    */
	, { (unsigned char *)"\xD0\x9E" , CFLAG_NORMALCHAR }	/* U+41E (0xD09E) unchanged ;    */
	, { (unsigned char *)"\xD0\x9F" , CFLAG_NORMALCHAR }	/* U+41F (0xD09F) unchanged ;    */
	, { (unsigned char *)"\xD0\xA0" , CFLAG_NORMALCHAR }	/* U+420 (0xD0A0) unchanged ;    */
	, { (unsigned char *)"\xD0\xA1" , CFLAG_NORMALCHAR }	/* U+421 (0xD0A1) unchanged ;    */
	, { (unsigned char *)"\xD0\xA2" , CFLAG_NORMALCHAR }	/* U+422 (0xD0A2) unchanged ;    */
	, { (unsigned char *)"\xD0\xA3" , CFLAG_NORMALCHAR }	/* U+423 (0xD0A3) unchanged ;    */
	, { (unsigned char *)"\xD0\xA4" , CFLAG_NORMALCHAR }	/* U+424 (0xD0A4) unchanged ;    */
	, { (unsigned char *)"\xD0\xA5" , CFLAG_NORMALCHAR }	/* U+425 (0xD0A5) unchanged ;    */
	, { (unsigned char *)"\xD0\xA6" , CFLAG_NORMALCHAR }	/* U+426 (0xD0A6) unchanged ;    */
	, { (unsigned char *)"\xD0\xA7" , CFLAG_NORMALCHAR }	/* U+427 (0xD0A7) unchanged ;    */
	, { (unsigned char *)"\xD0\xA8" , CFLAG_NORMALCHAR }	/* U+428 (0xD0A8) unchanged ;    */
	, { (unsigned char *)"\xD0\xA9" , CFLAG_NORMALCHAR }	/* U+429 (0xD0A9) unchanged ;    */
	, { (unsigned char *)"\xD0\xAA" , CFLAG_NORMALCHAR }	/* U+42A (0xD0AA) unchanged ;    */
	, { (unsigned char *)"\xD0\xAB" , CFLAG_NORMALCHAR }	/* U+42B (0xD0AB) unchanged ;    */
	, { (unsigned char *)"\xD0\xAC" , CFLAG_NORMALCHAR }	/* U+42C (0xD0AC) unchanged ;    */
	, { (unsigned char *)"\xD0\xAD" , CFLAG_NORMALCHAR }	/* U+42D (0xD0AD) unchanged ;    */
	, { (unsigned char *)"\xD0\xAE" , CFLAG_NORMALCHAR }	/* U+42E (0xD0AE) unchanged ;    */
	, { (unsigned char *)"\xD0\xAF" , CFLAG_NORMALCHAR }	/* U+42F (0xD0AF) unchanged ;    */
	, { (unsigned char *)"\xD0\xB0" , CFLAG_NORMALCHAR }	/* U+430 (0xD0B0) unchanged ;    */
	, { (unsigned char *)"\xD0\xB1" , CFLAG_NORMALCHAR }	/* U+431 (0xD0B1) unchanged ;    */
	, { (unsigned char *)"\xD0\xB2" , CFLAG_NORMALCHAR }	/* U+432 (0xD0B2) unchanged ;    */
	, { (unsigned char *)"\xD0\xB3" , CFLAG_NORMALCHAR }	/* U+433 (0xD0B3) unchanged ;    */
	, { (unsigned char *)"\xD0\xB4" , CFLAG_NORMALCHAR }	/* U+434 (0xD0B4) unchanged ;    */
	, { (unsigned char *)"\xD0\xB5" , CFLAG_NORMALCHAR }	/* U+435 (0xD0B5) unchanged ;    */
	, { (unsigned char *)"\xD0\xB6" , CFLAG_NORMALCHAR }	/* U+436 (0xD0B6) unchanged ;    */
	, { (unsigned char *)"\xD0\xB7" , CFLAG_NORMALCHAR }	/* U+437 (0xD0B7) unchanged ;    */
	, { (unsigned char *)"\xD0\xB8" , CFLAG_NORMALCHAR }	/* U+438 (0xD0B8) unchanged ;    */
	, { (unsigned char *)"\xD0\xB9" , CFLAG_NORMALCHAR }	/* U+439 (0xD0B9) unchanged ;    */
	, { (unsigned char *)"\xD0\xBA" , CFLAG_NORMALCHAR }	/* U+43A (0xD0BA) unchanged ;    */
	, { (unsigned char *)"\xD0\xBB" , CFLAG_NORMALCHAR }	/* U+43B (0xD0BB) unchanged ;    */
	, { (unsigned char *)"\xD0\xBC" , CFLAG_NORMALCHAR }	/* U+43C (0xD0BC) unchanged ;    */
	, { (unsigned char *)"\xD0\xBD" , CFLAG_NORMALCHAR }	/* U+43D (0xD0BD) unchanged ;    */
	, { (unsigned char *)"\xD0\xBE" , CFLAG_NORMALCHAR }	/* U+43E (0xD0BE) unchanged ;    */
	, { (unsigned char *)"\xD0\xBF" , CFLAG_NORMALCHAR }	/* U+43F (0xD0BF) unchanged ;    */
	, { (unsigned char *)"\xD1\x80" , CFLAG_NORMALCHAR }	/* U+440 (0xD180) unchanged ;    */
	, { (unsigned char *)"\xD1\x81" , CFLAG_NORMALCHAR }	/* U+441 (0xD181) unchanged ;    */
	, { (unsigned char *)"\xD1\x82" , CFLAG_NORMALCHAR }	/* U+442 (0xD182) unchanged ;    */
	, { (unsigned char *)"\xD1\x83" , CFLAG_NORMALCHAR }	/* U+443 (0xD183) unchanged ;    */
	, { (unsigned char *)"\xD1\x84" , CFLAG_NORMALCHAR }	/* U+444 (0xD184) unchanged ;    */
	, { (unsigned char *)"\xD1\x85" , CFLAG_NORMALCHAR }	/* U+445 (0xD185) unchanged ;    */
	, { (unsigned char *)"\xD1\x86" , CFLAG_NORMALCHAR }	/* U+446 (0xD186) unchanged ;    */
	, { (unsigned char *)"\xD1\x87" , CFLAG_NORMALCHAR }	/* U+447 (0xD187) unchanged ;    */
	, { (unsigned char *)"\xD1\x88" , CFLAG_NORMALCHAR }	/* U+448 (0xD188) unchanged ;    */
	, { (unsigned char *)"\xD1\x89" , CFLAG_NORMALCHAR }	/* U+449 (0xD189) unchanged ;    */
	, { (unsigned char *)"\xD1\x8A" , CFLAG_NORMALCHAR }	/* U+44A (0xD18A) unchanged ;    */
	, { (unsigned char *)"\xD1\x8B" , CFLAG_NORMALCHAR }	/* U+44B (0xD18B) unchanged ;    */
	, { (unsigned char *)"\xD1\x8C" , CFLAG_NORMALCHAR }	/* U+44C (0xD18C) unchanged ;    */
	, { (unsigned char *)"\xD1\x8D" , CFLAG_NORMALCHAR }	/* U+44D (0xD18D) unchanged ;    */
	, { (unsigned char *)"\xD1\x8E" , CFLAG_NORMALCHAR }	/* U+44E (0xD18E) unchanged ;    */
	, { (unsigned char *)"\xD1\x8F" , CFLAG_NORMALCHAR }	/* U+44F (0xD18F) unchanged ;    */
	, { (unsigned char *)"\xD1\x90" , CFLAG_NORMALCHAR }	/* U+450 (0xD190) unchanged ;    */
	, { (unsigned char *)"\xD1\x91" , CFLAG_NORMALCHAR }	/* U+451 (0xD191) unchanged ;    */
	, { (unsigned char *)"\xD1\x92" , CFLAG_NORMALCHAR }	/* U+452 (0xD192) unchanged ;    */
	, { (unsigned char *)"\xD1\x93" , CFLAG_NORMALCHAR }	/* U+453 (0xD193) unchanged ;    */
	, { (unsigned char *)"\xD1\x94" , CFLAG_NORMALCHAR }	/* U+454 (0xD194) unchanged ;    */
	, { (unsigned char *)"\xD1\x95" , CFLAG_NORMALCHAR }	/* U+455 (0xD195) unchanged ;    */
	, { (unsigned char *)"\xD1\x96" , CFLAG_NORMALCHAR }	/* U+456 (0xD196) unchanged ;    */
	, { (unsigned char *)"\xD1\x97" , CFLAG_NORMALCHAR }	/* U+457 (0xD197) unchanged ;    */
	, { (unsigned char *)"\xD1\x98" , CFLAG_NORMALCHAR }	/* U+458 (0xD198) unchanged ;    */
	, { (unsigned char *)"\xD1\x99" , CFLAG_NORMALCHAR }	/* U+459 (0xD199) unchanged ;    */
	, { (unsigned char *)"\xD1\x9A" , CFLAG_NORMALCHAR }	/* U+45A (0xD19A) unchanged ;    */
	, { (unsigned char *)"\xD1\x9B" , CFLAG_NORMALCHAR }	/* U+45B (0xD19B) unchanged ;    */
	, { (unsigned char *)"\xD1\x9C" , CFLAG_NORMALCHAR }	/* U+45C (0xD19C) unchanged ;    */
	, { (unsigned char *)"\xD1\x9D" , CFLAG_NORMALCHAR }	/* U+45D (0xD19D) unchanged ;    */
	, { (unsigned char *)"\xD1\x9E" , CFLAG_NORMALCHAR }	/* U+45E (0xD19E) unchanged ;    */
	, { (unsigned char *)"\xD1\x9F" , CFLAG_NORMALCHAR }	/* U+45F (0xD19F) unchanged ;    */
	, { (unsigned char *)"\xD1\xA0" , CFLAG_NORMALCHAR }	/* U+460 (0xD1A0) unchanged ;    */
	, { (unsigned char *)"\xD1\xA1" , CFLAG_NORMALCHAR }	/* U+461 (0xD1A1) unchanged ;    */
	, { (unsigned char *)"\xD1\xA2" , CFLAG_NORMALCHAR }	/* U+462 (0xD1A2) unchanged ;    */
	, { (unsigned char *)"\xD1\xA3" , CFLAG_NORMALCHAR }	/* U+463 (0xD1A3) unchanged ;    */
	, { (unsigned char *)"\xD1\xA4" , CFLAG_NORMALCHAR }	/* U+464 (0xD1A4) unchanged ;    */
	, { (unsigned char *)"\xD1\xA5" , CFLAG_NORMALCHAR }	/* U+465 (0xD1A5) unchanged ;    */
	, { (unsigned char *)"\xD1\xA6" , CFLAG_NORMALCHAR }	/* U+466 (0xD1A6) unchanged ;    */
	, { (unsigned char *)"\xD1\xA7" , CFLAG_NORMALCHAR }	/* U+467 (0xD1A7) unchanged ;    */
	, { (unsigned char *)"\xD1\xA8" , CFLAG_NORMALCHAR }	/* U+468 (0xD1A8) unchanged ;    */
	, { (unsigned char *)"\xD1\xA9" , CFLAG_NORMALCHAR }	/* U+469 (0xD1A9) unchanged ;    */
	, { (unsigned char *)"\xD1\xAA" , CFLAG_NORMALCHAR }	/* U+46A (0xD1AA) unchanged ;    */
	, { (unsigned char *)"\xD1\xAB" , CFLAG_NORMALCHAR }	/* U+46B (0xD1AB) unchanged ;    */
	, { (unsigned char *)"\xD1\xAC" , CFLAG_NORMALCHAR }	/* U+46C (0xD1AC) unchanged ;    */
	, { (unsigned char *)"\xD1\xAD" , CFLAG_NORMALCHAR }	/* U+46D (0xD1AD) unchanged ;    */
	, { (unsigned char *)"\xD1\xAE" , CFLAG_NORMALCHAR }	/* U+46E (0xD1AE) unchanged ;    */
	, { (unsigned char *)"\xD1\xAF" , CFLAG_NORMALCHAR }	/* U+46F (0xD1AF) unchanged ;    */
	, { (unsigned char *)"\xD1\xB0" , CFLAG_NORMALCHAR }	/* U+470 (0xD1B0) unchanged ;    */
	, { (unsigned char *)"\xD1\xB1" , CFLAG_NORMALCHAR }	/* U+471 (0xD1B1) unchanged ;    */
	, { (unsigned char *)"\xD1\xB2" , CFLAG_NORMALCHAR }	/* U+472 (0xD1B2) unchanged ;    */
	, { (unsigned char *)"\xD1\xB3" , CFLAG_NORMALCHAR }	/* U+473 (0xD1B3) unchanged ;    */
	, { (unsigned char *)"\xD1\xB4" , CFLAG_NORMALCHAR }	/* U+474 (0xD1B4) unchanged ;    */
	, { (unsigned char *)"\xD1\xB5" , CFLAG_NORMALCHAR }	/* U+475 (0xD1B5) unchanged ;    */
	, { (unsigned char *)"\xD1\xB6" , CFLAG_NORMALCHAR }	/* U+476 (0xD1B6) unchanged ;    */
	, { (unsigned char *)"\xD1\xB7" , CFLAG_NORMALCHAR }	/* U+477 (0xD1B7) unchanged ;    */
	, { (unsigned char *)"\xD1\xB8" , CFLAG_NORMALCHAR }	/* U+478 (0xD1B8) unchanged ;    */
	, { (unsigned char *)"\xD1\xB9" , CFLAG_NORMALCHAR }	/* U+479 (0xD1B9) unchanged ;    */
	, { (unsigned char *)"\xD1\xBA" , CFLAG_NORMALCHAR }	/* U+47A (0xD1BA) unchanged ;    */
	, { (unsigned char *)"\xD1\xBB" , CFLAG_NORMALCHAR }	/* U+47B (0xD1BB) unchanged ;    */
	, { (unsigned char *)"\xD1\xBC" , CFLAG_NORMALCHAR }	/* U+47C (0xD1BC) unchanged ;    */
	, { (unsigned char *)"\xD1\xBD" , CFLAG_NORMALCHAR }	/* U+47D (0xD1BD) unchanged ;    */
	, { (unsigned char *)"\xD1\xBE" , CFLAG_NORMALCHAR }	/* U+47E (0xD1BE) unchanged ;    */
	, { (unsigned char *)"\xD1\xBF" , CFLAG_NORMALCHAR }	/* U+47F (0xD1BF) unchanged ;    */
	, { (unsigned char *)"\xD2\x80" , CFLAG_NORMALCHAR }	/* U+480 (0xD280) unchanged ;    */
	, { (unsigned char *)"\xD2\x81" , CFLAG_NORMALCHAR }	/* U+481 (0xD281) unchanged ;    */
	, { (unsigned char *)"\xD2\x82" , CFLAG_NORMALCHAR }	/* U+482 (0xD282) unchanged ;    */
	, { (unsigned char *)"\xD2\x83" , CFLAG_NORMALCHAR }	/* U+483 (0xD283) unchanged ;    */
	, { (unsigned char *)"\xD2\x84" , CFLAG_NORMALCHAR }	/* U+484 (0xD284) unchanged ;    */
	, { (unsigned char *)"\xD2\x85" , CFLAG_NORMALCHAR }	/* U+485 (0xD285) unchanged ;    */
	, { (unsigned char *)"\xD2\x86" , CFLAG_NORMALCHAR }	/* U+486 (0xD286) unchanged ;    */
	, { (unsigned char *)"\xD2\x87" , CFLAG_NORMALCHAR }	/* U+487 (0xD287) unchanged ;    */
	, { (unsigned char *)"\xD2\x88" , CFLAG_NORMALCHAR }	/* U+488 (0xD288) unchanged ;    */
	, { (unsigned char *)"\xD2\x89" , CFLAG_NORMALCHAR }	/* U+489 (0xD289) unchanged ;    */
	, { (unsigned char *)"\xD2\x8A" , CFLAG_NORMALCHAR }	/* U+48A (0xD28A) unchanged ;    */
	, { (unsigned char *)"\xD2\x8B" , CFLAG_NORMALCHAR }	/* U+48B (0xD28B) unchanged ;    */
	, { (unsigned char *)"\xD2\x8C" , CFLAG_NORMALCHAR }	/* U+48C (0xD28C) unchanged ;    */
	, { (unsigned char *)"\xD2\x8D" , CFLAG_NORMALCHAR }	/* U+48D (0xD28D) unchanged ;    */
	, { (unsigned char *)"\xD2\x8E" , CFLAG_NORMALCHAR }	/* U+48E (0xD28E) unchanged ;    */
	, { (unsigned char *)"\xD2\x8F" , CFLAG_NORMALCHAR }	/* U+48F (0xD28F) unchanged ;    */
	, { (unsigned char *)"\xD2\x90" , CFLAG_NORMALCHAR }	/* U+490 (0xD290) unchanged ;    */
	, { (unsigned char *)"\xD2\x91" , CFLAG_NORMALCHAR }	/* U+491 (0xD291) unchanged ;    */
	, { (unsigned char *)"\xD2\x92" , CFLAG_NORMALCHAR }	/* U+492 (0xD292) unchanged ;    */
	, { (unsigned char *)"\xD2\x93" , CFLAG_NORMALCHAR }	/* U+493 (0xD293) unchanged ;    */
	, { (unsigned char *)"\xD2\x94" , CFLAG_NORMALCHAR }	/* U+494 (0xD294) unchanged ;    */
	, { (unsigned char *)"\xD2\x95" , CFLAG_NORMALCHAR }	/* U+495 (0xD295) unchanged ;    */
	, { (unsigned char *)"\xD2\x96" , CFLAG_NORMALCHAR }	/* U+496 (0xD296) unchanged ;    */
	, { (unsigned char *)"\xD2\x97" , CFLAG_NORMALCHAR }	/* U+497 (0xD297) unchanged ;    */
	, { (unsigned char *)"\xD2\x98" , CFLAG_NORMALCHAR }	/* U+498 (0xD298) unchanged ;    */
	, { (unsigned char *)"\xD2\x99" , CFLAG_NORMALCHAR }	/* U+499 (0xD299) unchanged ;    */
	, { (unsigned char *)"\xD2\x9A" , CFLAG_NORMALCHAR }	/* U+49A (0xD29A) unchanged ;    */
	, { (unsigned char *)"\xD2\x9B" , CFLAG_NORMALCHAR }	/* U+49B (0xD29B) unchanged ;    */
	, { (unsigned char *)"\xD2\x9C" , CFLAG_NORMALCHAR }	/* U+49C (0xD29C) unchanged ;    */
	, { (unsigned char *)"\xD2\x9D" , CFLAG_NORMALCHAR }	/* U+49D (0xD29D) unchanged ;    */
	, { (unsigned char *)"\xD2\x9E" , CFLAG_NORMALCHAR }	/* U+49E (0xD29E) unchanged ;    */
	, { (unsigned char *)"\xD2\x9F" , CFLAG_NORMALCHAR }	/* U+49F (0xD29F) unchanged ;    */
	, { (unsigned char *)"\xD2\xA0" , CFLAG_NORMALCHAR }	/* U+4A0 (0xD2A0) unchanged ;    */
	, { (unsigned char *)"\xD2\xA1" , CFLAG_NORMALCHAR }	/* U+4A1 (0xD2A1) unchanged ;    */
	, { (unsigned char *)"\xD2\xA2" , CFLAG_NORMALCHAR }	/* U+4A2 (0xD2A2) unchanged ;    */
	, { (unsigned char *)"\xD2\xA3" , CFLAG_NORMALCHAR }	/* U+4A3 (0xD2A3) unchanged ;    */
	, { (unsigned char *)"\xD2\xA4" , CFLAG_NORMALCHAR }	/* U+4A4 (0xD2A4) unchanged ;    */
	, { (unsigned char *)"\xD2\xA5" , CFLAG_NORMALCHAR }	/* U+4A5 (0xD2A5) unchanged ;    */
	, { (unsigned char *)"\xD2\xA6" , CFLAG_NORMALCHAR }	/* U+4A6 (0xD2A6) unchanged ;    */
	, { (unsigned char *)"\xD2\xA7" , CFLAG_NORMALCHAR }	/* U+4A7 (0xD2A7) unchanged ;    */
	, { (unsigned char *)"\xD2\xA8" , CFLAG_NORMALCHAR }	/* U+4A8 (0xD2A8) unchanged ;    */
	, { (unsigned char *)"\xD2\xA9" , CFLAG_NORMALCHAR }	/* U+4A9 (0xD2A9) unchanged ;    */
	, { (unsigned char *)"\xD2\xAA" , CFLAG_NORMALCHAR }	/* U+4AA (0xD2AA) unchanged ;    */
	, { (unsigned char *)"\xD2\xAB" , CFLAG_NORMALCHAR }	/* U+4AB (0xD2AB) unchanged ;    */
	, { (unsigned char *)"\xD2\xAC" , CFLAG_NORMALCHAR }	/* U+4AC (0xD2AC) unchanged ;    */
	, { (unsigned char *)"\xD2\xAD" , CFLAG_NORMALCHAR }	/* U+4AD (0xD2AD) unchanged ;    */
	, { (unsigned char *)"\xD2\xAE" , CFLAG_NORMALCHAR }	/* U+4AE (0xD2AE) unchanged ;    */
	, { (unsigned char *)"\xD2\xAF" , CFLAG_NORMALCHAR }	/* U+4AF (0xD2AF) unchanged ;    */
	, { (unsigned char *)"\xD2\xB0" , CFLAG_NORMALCHAR }	/* U+4B0 (0xD2B0) unchanged ;    */
	, { (unsigned char *)"\xD2\xB1" , CFLAG_NORMALCHAR }	/* U+4B1 (0xD2B1) unchanged ;    */
	, { (unsigned char *)"\xD2\xB2" , CFLAG_NORMALCHAR }	/* U+4B2 (0xD2B2) unchanged ;    */
	, { (unsigned char *)"\xD2\xB3" , CFLAG_NORMALCHAR }	/* U+4B3 (0xD2B3) unchanged ;    */
	, { (unsigned char *)"\xD2\xB4" , CFLAG_NORMALCHAR }	/* U+4B4 (0xD2B4) unchanged ;    */
	, { (unsigned char *)"\xD2\xB5" , CFLAG_NORMALCHAR }	/* U+4B5 (0xD2B5) unchanged ;    */
	, { (unsigned char *)"\xD2\xB6" , CFLAG_NORMALCHAR }	/* U+4B6 (0xD2B6) unchanged ;    */
	, { (unsigned char *)"\xD2\xB7" , CFLAG_NORMALCHAR }	/* U+4B7 (0xD2B7) unchanged ;    */
	, { (unsigned char *)"\xD2\xB8" , CFLAG_NORMALCHAR }	/* U+4B8 (0xD2B8) unchanged ;    */
	, { (unsigned char *)"\xD2\xB9" , CFLAG_NORMALCHAR }	/* U+4B9 (0xD2B9) unchanged ;    */
	, { (unsigned char *)"\xD2\xBA" , CFLAG_NORMALCHAR }	/* U+4BA (0xD2BA) unchanged ;    */
	, { (unsigned char *)"\xD2\xBB" , CFLAG_NORMALCHAR }	/* U+4BB (0xD2BB) unchanged ;    */
	, { (unsigned char *)"\xD2\xBC" , CFLAG_NORMALCHAR }	/* U+4BC (0xD2BC) unchanged ;    */
	, { (unsigned char *)"\xD2\xBD" , CFLAG_NORMALCHAR }	/* U+4BD (0xD2BD) unchanged ;    */
	, { (unsigned char *)"\xD2\xBE" , CFLAG_NORMALCHAR }	/* U+4BE (0xD2BE) unchanged ;    */
	, { (unsigned char *)"\xD2\xBF" , CFLAG_NORMALCHAR }	/* U+4BF (0xD2BF) unchanged ;    */
	, { (unsigned char *)"\xD3\x80" , CFLAG_NORMALCHAR }	/* U+4C0 (0xD380) unchanged ;    */
	, { (unsigned char *)"\xD3\x81" , CFLAG_NORMALCHAR }	/* U+4C1 (0xD381) unchanged ;    */
	, { (unsigned char *)"\xD3\x82" , CFLAG_NORMALCHAR }	/* U+4C2 (0xD382) unchanged ;    */
	, { (unsigned char *)"\xD3\x83" , CFLAG_NORMALCHAR }	/* U+4C3 (0xD383) unchanged ;    */
	, { (unsigned char *)"\xD3\x84" , CFLAG_NORMALCHAR }	/* U+4C4 (0xD384) unchanged ;    */
	, { (unsigned char *)"\xD3\x85" , CFLAG_NORMALCHAR }	/* U+4C5 (0xD385) unchanged ;    */
	, { (unsigned char *)"\xD3\x86" , CFLAG_NORMALCHAR }	/* U+4C6 (0xD386) unchanged ;    */
	, { (unsigned char *)"\xD3\x87" , CFLAG_NORMALCHAR }	/* U+4C7 (0xD387) unchanged ;    */
	, { (unsigned char *)"\xD3\x88" , CFLAG_NORMALCHAR }	/* U+4C8 (0xD388) unchanged ;    */
	, { (unsigned char *)"\xD3\x89" , CFLAG_NORMALCHAR }	/* U+4C9 (0xD389) unchanged ;    */
	, { (unsigned char *)"\xD3\x8A" , CFLAG_NORMALCHAR }	/* U+4CA (0xD38A) unchanged ;    */
	, { (unsigned char *)"\xD3\x8B" , CFLAG_NORMALCHAR }	/* U+4CB (0xD38B) unchanged ;    */
	, { (unsigned char *)"\xD3\x8C" , CFLAG_NORMALCHAR }	/* U+4CC (0xD38C) unchanged ;    */
	, { (unsigned char *)"\xD3\x8D" , CFLAG_NORMALCHAR }	/* U+4CD (0xD38D) unchanged ;    */
	, { (unsigned char *)"\xD3\x8E" , CFLAG_NORMALCHAR }	/* U+4CE (0xD38E) unchanged ;    */
	, { (unsigned char *)"\xD3\x8F" , CFLAG_NORMALCHAR }	/* U+4CF (0xD38F) unchanged ;    */
	, { (unsigned char *)"\xD3\x90" , CFLAG_NORMALCHAR }	/* U+4D0 (0xD390) unchanged ;    */
	, { (unsigned char *)"\xD3\x91" , CFLAG_NORMALCHAR }	/* U+4D1 (0xD391) unchanged ;    */
	, { (unsigned char *)"\xD3\x92" , CFLAG_NORMALCHAR }	/* U+4D2 (0xD392) unchanged ;    */
	, { (unsigned char *)"\xD3\x93" , CFLAG_NORMALCHAR }	/* U+4D3 (0xD393) unchanged ;    */
	, { (unsigned char *)"\xD3\x94" , CFLAG_NORMALCHAR }	/* U+4D4 (0xD394) unchanged ;    */
	, { (unsigned char *)"\xD3\x95" , CFLAG_NORMALCHAR }	/* U+4D5 (0xD395) unchanged ;    */
	, { (unsigned char *)"\xD3\x96" , CFLAG_NORMALCHAR }	/* U+4D6 (0xD396) unchanged ;    */
	, { (unsigned char *)"\xD3\x97" , CFLAG_NORMALCHAR }	/* U+4D7 (0xD397) unchanged ;    */
	, { (unsigned char *)"\xD3\x98" , CFLAG_NORMALCHAR }	/* U+4D8 (0xD398) unchanged ;    */
	, { (unsigned char *)"\xD3\x99" , CFLAG_NORMALCHAR }	/* U+4D9 (0xD399) unchanged ;    */
	, { (unsigned char *)"\xD3\x9A" , CFLAG_NORMALCHAR }	/* U+4DA (0xD39A) unchanged ;    */
	, { (unsigned char *)"\xD3\x9B" , CFLAG_NORMALCHAR }	/* U+4DB (0xD39B) unchanged ;    */
	, { (unsigned char *)"\xD3\x9C" , CFLAG_NORMALCHAR }	/* U+4DC (0xD39C) unchanged ;    */
	, { (unsigned char *)"\xD3\x9D" , CFLAG_NORMALCHAR }	/* U+4DD (0xD39D) unchanged ;    */
	, { (unsigned char *)"\xD3\x9E" , CFLAG_NORMALCHAR }	/* U+4DE (0xD39E) unchanged ;    */
	, { (unsigned char *)"\xD3\x9F" , CFLAG_NORMALCHAR }	/* U+4DF (0xD39F) unchanged ;    */
	, { (unsigned char *)"\xD3\xA0" , CFLAG_NORMALCHAR }	/* U+4E0 (0xD3A0) unchanged ;    */
	, { (unsigned char *)"\xD3\xA1" , CFLAG_NORMALCHAR }	/* U+4E1 (0xD3A1) unchanged ;    */
	, { (unsigned char *)"\xD3\xA2" , CFLAG_NORMALCHAR }	/* U+4E2 (0xD3A2) unchanged ;    */
	, { (unsigned char *)"\xD3\xA3" , CFLAG_NORMALCHAR }	/* U+4E3 (0xD3A3) unchanged ;    */
	, { (unsigned char *)"\xD3\xA4" , CFLAG_NORMALCHAR }	/* U+4E4 (0xD3A4) unchanged ;    */
	, { (unsigned char *)"\xD3\xA5" , CFLAG_NORMALCHAR }	/* U+4E5 (0xD3A5) unchanged ;    */
	, { (unsigned char *)"\xD3\xA6" , CFLAG_NORMALCHAR }	/* U+4E6 (0xD3A6) unchanged ;    */
	, { (unsigned char *)"\xD3\xA7" , CFLAG_NORMALCHAR }	/* U+4E7 (0xD3A7) unchanged ;    */
	, { (unsigned char *)"\xD3\xA8" , CFLAG_NORMALCHAR }	/* U+4E8 (0xD3A8) unchanged ;    */
	, { (unsigned char *)"\xD3\xA9" , CFLAG_NORMALCHAR }	/* U+4E9 (0xD3A9) unchanged ;    */
	, { (unsigned char *)"\xD3\xAA" , CFLAG_NORMALCHAR }	/* U+4EA (0xD3AA) unchanged ;    */
	, { (unsigned char *)"\xD3\xAB" , CFLAG_NORMALCHAR }	/* U+4EB (0xD3AB) unchanged ;    */
	, { (unsigned char *)"\xD3\xAC" , CFLAG_NORMALCHAR }	/* U+4EC (0xD3AC) unchanged ;    */
	, { (unsigned char *)"\xD3\xAD" , CFLAG_NORMALCHAR }	/* U+4ED (0xD3AD) unchanged ;    */
	, { (unsigned char *)"\xD3\xAE" , CFLAG_NORMALCHAR }	/* U+4EE (0xD3AE) unchanged ;    */
	, { (unsigned char *)"\xD3\xAF" , CFLAG_NORMALCHAR }	/* U+4EF (0xD3AF) unchanged ;    */
	, { (unsigned char *)"\xD3\xB0" , CFLAG_NORMALCHAR }	/* U+4F0 (0xD3B0) unchanged ;    */
	, { (unsigned char *)"\xD3\xB1" , CFLAG_NORMALCHAR }	/* U+4F1 (0xD3B1) unchanged ;    */
	, { (unsigned char *)"\xD3\xB2" , CFLAG_NORMALCHAR }	/* U+4F2 (0xD3B2) unchanged ;    */
	, { (unsigned char *)"\xD3\xB3" , CFLAG_NORMALCHAR }	/* U+4F3 (0xD3B3) unchanged ;    */
	, { (unsigned char *)"\xD3\xB4" , CFLAG_NORMALCHAR }	/* U+4F4 (0xD3B4) unchanged ;    */
	, { (unsigned char *)"\xD3\xB5" , CFLAG_NORMALCHAR }	/* U+4F5 (0xD3B5) unchanged ;    */
	, { (unsigned char *)"\xD3\xB6" , CFLAG_NORMALCHAR }	/* U+4F6 (0xD3B6) unchanged ;    */
	, { (unsigned char *)"\xD3\xB7" , CFLAG_NORMALCHAR }	/* U+4F7 (0xD3B7) unchanged ;    */
	, { (unsigned char *)"\xD3\xB8" , CFLAG_NORMALCHAR }	/* U+4F8 (0xD3B8) unchanged ;    */
	, { (unsigned char *)"\xD3\xB9" , CFLAG_NORMALCHAR }	/* U+4F9 (0xD3B9) unchanged ;    */
	, { (unsigned char *)"\xD3\xBA" , CFLAG_NORMALCHAR }	/* U+4FA (0xD3BA) unchanged ;    */
	, { (unsigned char *)"\xD3\xBB" , CFLAG_NORMALCHAR }	/* U+4FB (0xD3BB) unchanged ;    */
	, { (unsigned char *)"\xD3\xBC" , CFLAG_NORMALCHAR }	/* U+4FC (0xD3BC) unchanged ;    */
	, { (unsigned char *)"\xD3\xBD" , CFLAG_NORMALCHAR }	/* U+4FD (0xD3BD) unchanged ;    */
	, { (unsigned char *)"\xD3\xBE" , CFLAG_NORMALCHAR }	/* U+4FE (0xD3BE) unchanged ;    */
	, { (unsigned char *)"\xD3\xBF" , CFLAG_NORMALCHAR }	/* U+4FF (0xD3BF) unchanged ;    */
	, { (unsigned char *)"\xD4\x80" , CFLAG_NORMALCHAR }	/* U+500 (0xD480) unchanged ;    */
	, { (unsigned char *)"\xD4\x81" , CFLAG_NORMALCHAR }	/* U+501 (0xD481) unchanged ;    */
	, { (unsigned char *)"\xD4\x82" , CFLAG_NORMALCHAR }	/* U+502 (0xD482) unchanged ;    */
	, { (unsigned char *)"\xD4\x83" , CFLAG_NORMALCHAR }	/* U+503 (0xD483) unchanged ;    */
	, { (unsigned char *)"\xD4\x84" , CFLAG_NORMALCHAR }	/* U+504 (0xD484) unchanged ;    */
	, { (unsigned char *)"\xD4\x85" , CFLAG_NORMALCHAR }	/* U+505 (0xD485) unchanged ;    */
	, { (unsigned char *)"\xD4\x86" , CFLAG_NORMALCHAR }	/* U+506 (0xD486) unchanged ;    */
	, { (unsigned char *)"\xD4\x87" , CFLAG_NORMALCHAR }	/* U+507 (0xD487) unchanged ;    */
	, { (unsigned char *)"\xD4\x88" , CFLAG_NORMALCHAR }	/* U+508 (0xD488) unchanged ;    */
	, { (unsigned char *)"\xD4\x89" , CFLAG_NORMALCHAR }	/* U+509 (0xD489) unchanged ;    */
	, { (unsigned char *)"\xD4\x8A" , CFLAG_NORMALCHAR }	/* U+50A (0xD48A) unchanged ;    */
	, { (unsigned char *)"\xD4\x8B" , CFLAG_NORMALCHAR }	/* U+50B (0xD48B) unchanged ;    */
	, { (unsigned char *)"\xD4\x8C" , CFLAG_NORMALCHAR }	/* U+50C (0xD48C) unchanged ;    */
	, { (unsigned char *)"\xD4\x8D" , CFLAG_NORMALCHAR }	/* U+50D (0xD48D) unchanged ;    */
	, { (unsigned char *)"\xD4\x8E" , CFLAG_NORMALCHAR }	/* U+50E (0xD48E) unchanged ;    */
	, { (unsigned char *)"\xD4\x8F" , CFLAG_NORMALCHAR }	/* U+50F (0xD48F) unchanged ;    */
	, { (unsigned char *)"\xD4\x90" , CFLAG_NORMALCHAR }	/* U+510 (0xD490) unchanged ;    */
	, { (unsigned char *)"\xD4\x91" , CFLAG_NORMALCHAR }	/* U+511 (0xD491) unchanged ;    */
	, { (unsigned char *)"\xD4\x92" , CFLAG_NORMALCHAR }	/* U+512 (0xD492) unchanged ;    */
	, { (unsigned char *)"\xD4\x93" , CFLAG_NORMALCHAR }	/* U+513 (0xD493) unchanged ;    */
	, { (unsigned char *)"\xD4\x94" , CFLAG_NORMALCHAR }	/* U+514 (0xD494) unchanged ;    */
	, { (unsigned char *)"\xD4\x95" , CFLAG_NORMALCHAR }	/* U+515 (0xD495) unchanged ;    */
	, { (unsigned char *)"\xD4\x96" , CFLAG_NORMALCHAR }	/* U+516 (0xD496) unchanged ;    */
	, { (unsigned char *)"\xD4\x97" , CFLAG_NORMALCHAR }	/* U+517 (0xD497) unchanged ;    */
	, { (unsigned char *)"\xD4\x98" , CFLAG_NORMALCHAR }	/* U+518 (0xD498) unchanged ;    */
	, { (unsigned char *)"\xD4\x99" , CFLAG_NORMALCHAR }	/* U+519 (0xD499) unchanged ;    */
	, { (unsigned char *)"\xD4\x9A" , CFLAG_NORMALCHAR }	/* U+51A (0xD49A) unchanged ;    */
	, { (unsigned char *)"\xD4\x9B" , CFLAG_NORMALCHAR }	/* U+51B (0xD49B) unchanged ;    */
	, { (unsigned char *)"\xD4\x9C" , CFLAG_NORMALCHAR }	/* U+51C (0xD49C) unchanged ;    */
	, { (unsigned char *)"\xD4\x9D" , CFLAG_NORMALCHAR }	/* U+51D (0xD49D) unchanged ;    */
	, { (unsigned char *)"\xD4\x9E" , CFLAG_NORMALCHAR }	/* U+51E (0xD49E) unchanged ;    */
	, { (unsigned char *)"\xD4\x9F" , CFLAG_NORMALCHAR }	/* U+51F (0xD49F) unchanged ;    */
	, { (unsigned char *)"\xD4\xA0" , CFLAG_NORMALCHAR }	/* U+520 (0xD4A0) unchanged ;    */
	, { (unsigned char *)"\xD4\xA1" , CFLAG_NORMALCHAR }	/* U+521 (0xD4A1) unchanged ;    */
	, { (unsigned char *)"\xD4\xA2" , CFLAG_NORMALCHAR }	/* U+522 (0xD4A2) unchanged ;    */
	, { (unsigned char *)"\xD4\xA3" , CFLAG_NORMALCHAR }	/* U+523 (0xD4A3) unchanged ;    */
	, { (unsigned char *)"\xD4\xA4" , CFLAG_NORMALCHAR }	/* U+524 (0xD4A4) unchanged ;    */
	, { (unsigned char *)"\xD4\xA5" , CFLAG_NORMALCHAR }	/* U+525 (0xD4A5) unchanged ;    */
	, { (unsigned char *)"\xD4\xA6" , CFLAG_NORMALCHAR }	/* U+526 (0xD4A6) unchanged ;    */
	, { (unsigned char *)"\xD4\xA7" , CFLAG_NORMALCHAR }	/* U+527 (0xD4A7) unchanged ;    */
	, { (unsigned char *)"\xD4\xA8" , CFLAG_NORMALCHAR }	/* U+528 (0xD4A8) unchanged ;    */
	, { (unsigned char *)"\xD4\xA9" , CFLAG_NORMALCHAR }	/* U+529 (0xD4A9) unchanged ;    */
	, { (unsigned char *)"\xD4\xAA" , CFLAG_NORMALCHAR }	/* U+52A (0xD4AA) unchanged ;    */
	, { (unsigned char *)"\xD4\xAB" , CFLAG_NORMALCHAR }	/* U+52B (0xD4AB) unchanged ;    */
	, { (unsigned char *)"\xD4\xAC" , CFLAG_NORMALCHAR }	/* U+52C (0xD4AC) unchanged ;    */
	, { (unsigned char *)"\xD4\xAD" , CFLAG_NORMALCHAR }	/* U+52D (0xD4AD) unchanged ;    */
	, { (unsigned char *)"\xD4\xAE" , CFLAG_NORMALCHAR }	/* U+52E (0xD4AE) unchanged ;    */
	, { (unsigned char *)"\xD4\xAF" , CFLAG_NORMALCHAR }	/* U+52F (0xD4AF) unchanged ;    */
	, { (unsigned char *)"\xD4\xB0" , CFLAG_NORMALCHAR }	/* U+530 (0xD4B0) unchanged ;    */
	, { (unsigned char *)"\xD4\xB1" , CFLAG_NORMALCHAR }	/* U+531 (0xD4B1) unchanged ;    */
	, { (unsigned char *)"\xD4\xB2" , CFLAG_NORMALCHAR }	/* U+532 (0xD4B2) unchanged ;    */
	, { (unsigned char *)"\xD4\xB3" , CFLAG_NORMALCHAR }	/* U+533 (0xD4B3) unchanged ;    */
	, { (unsigned char *)"\xD4\xB4" , CFLAG_NORMALCHAR }	/* U+534 (0xD4B4) unchanged ;    */
	, { (unsigned char *)"\xD4\xB5" , CFLAG_NORMALCHAR }	/* U+535 (0xD4B5) unchanged ;    */
	, { (unsigned char *)"\xD4\xB6" , CFLAG_NORMALCHAR }	/* U+536 (0xD4B6) unchanged ;    */
	, { (unsigned char *)"\xD4\xB7" , CFLAG_NORMALCHAR }	/* U+537 (0xD4B7) unchanged ;    */
	, { (unsigned char *)"\xD4\xB8" , CFLAG_NORMALCHAR }	/* U+538 (0xD4B8) unchanged ;    */
	, { (unsigned char *)"\xD4\xB9" , CFLAG_NORMALCHAR }	/* U+539 (0xD4B9) unchanged ;    */
	, { (unsigned char *)"\xD4\xBA" , CFLAG_NORMALCHAR }	/* U+53A (0xD4BA) unchanged ;    */
	, { (unsigned char *)"\xD4\xBB" , CFLAG_NORMALCHAR }	/* U+53B (0xD4BB) unchanged ;    */
	, { (unsigned char *)"\xD4\xBC" , CFLAG_NORMALCHAR }	/* U+53C (0xD4BC) unchanged ;    */
	, { (unsigned char *)"\xD4\xBD" , CFLAG_NORMALCHAR }	/* U+53D (0xD4BD) unchanged ;    */
	, { (unsigned char *)"\xD4\xBE" , CFLAG_NORMALCHAR }	/* U+53E (0xD4BE) unchanged ;    */
	, { (unsigned char *)"\xD4\xBF" , CFLAG_NORMALCHAR }	/* U+53F (0xD4BF) unchanged ;    */
	, { (unsigned char *)"\xD5\x80" , CFLAG_NORMALCHAR }	/* U+540 (0xD580) unchanged ;    */
	, { (unsigned char *)"\xD5\x81" , CFLAG_NORMALCHAR }	/* U+541 (0xD581) unchanged ;    */
	, { (unsigned char *)"\xD5\x82" , CFLAG_NORMALCHAR }	/* U+542 (0xD582) unchanged ;    */
	, { (unsigned char *)"\xD5\x83" , CFLAG_NORMALCHAR }	/* U+543 (0xD583) unchanged ;    */
	, { (unsigned char *)"\xD5\x84" , CFLAG_NORMALCHAR }	/* U+544 (0xD584) unchanged ;    */
	, { (unsigned char *)"\xD5\x85" , CFLAG_NORMALCHAR }	/* U+545 (0xD585) unchanged ;    */
	, { (unsigned char *)"\xD5\x86" , CFLAG_NORMALCHAR }	/* U+546 (0xD586) unchanged ;    */
	, { (unsigned char *)"\xD5\x87" , CFLAG_NORMALCHAR }	/* U+547 (0xD587) unchanged ;    */
	, { (unsigned char *)"\xD5\x88" , CFLAG_NORMALCHAR }	/* U+548 (0xD588) unchanged ;    */
	, { (unsigned char *)"\xD5\x89" , CFLAG_NORMALCHAR }	/* U+549 (0xD589) unchanged ;    */
	, { (unsigned char *)"\xD5\x8A" , CFLAG_NORMALCHAR }	/* U+54A (0xD58A) unchanged ;    */
	, { (unsigned char *)"\xD5\x8B" , CFLAG_NORMALCHAR }	/* U+54B (0xD58B) unchanged ;    */
	, { (unsigned char *)"\xD5\x8C" , CFLAG_NORMALCHAR }	/* U+54C (0xD58C) unchanged ;    */
	, { (unsigned char *)"\xD5\x8D" , CFLAG_NORMALCHAR }	/* U+54D (0xD58D) unchanged ;    */
	, { (unsigned char *)"\xD5\x8E" , CFLAG_NORMALCHAR }	/* U+54E (0xD58E) unchanged ;    */
	, { (unsigned char *)"\xD5\x8F" , CFLAG_NORMALCHAR }	/* U+54F (0xD58F) unchanged ;    */
	, { (unsigned char *)"\xD5\x90" , CFLAG_NORMALCHAR }	/* U+550 (0xD590) unchanged ;    */
	, { (unsigned char *)"\xD5\x91" , CFLAG_NORMALCHAR }	/* U+551 (0xD591) unchanged ;    */
	, { (unsigned char *)"\xD5\x92" , CFLAG_NORMALCHAR }	/* U+552 (0xD592) unchanged ;    */
	, { (unsigned char *)"\xD5\x93" , CFLAG_NORMALCHAR }	/* U+553 (0xD593) unchanged ;    */
	, { (unsigned char *)"\xD5\x94" , CFLAG_NORMALCHAR }	/* U+554 (0xD594) unchanged ;    */
	, { (unsigned char *)"\xD5\x95" , CFLAG_NORMALCHAR }	/* U+555 (0xD595) unchanged ;    */
	, { (unsigned char *)"\xD5\x96" , CFLAG_NORMALCHAR }	/* U+556 (0xD596) unchanged ;    */
	, { (unsigned char *)"\xD5\x97" , CFLAG_NORMALCHAR }	/* U+557 (0xD597) unchanged ;    */
	, { (unsigned char *)"\xD5\x98" , CFLAG_NORMALCHAR }	/* U+558 (0xD598) unchanged ;    */
	, { (unsigned char *)"\xD5\x99" , CFLAG_NORMALCHAR }	/* U+559 (0xD599) unchanged ;    */
	, { (unsigned char *)"\xD5\x9A" , CFLAG_NORMALCHAR }	/* U+55A (0xD59A) unchanged ;    */
	, { (unsigned char *)"\xD5\x9B" , CFLAG_NORMALCHAR }	/* U+55B (0xD59B) unchanged ;    */
	, { (unsigned char *)"\xD5\x9C" , CFLAG_NORMALCHAR }	/* U+55C (0xD59C) unchanged ;    */
	, { (unsigned char *)"\xD5\x9D" , CFLAG_NORMALCHAR }	/* U+55D (0xD59D) unchanged ;    */
	, { (unsigned char *)"\xD5\x9E" , CFLAG_NORMALCHAR }	/* U+55E (0xD59E) unchanged ;    */
	, { (unsigned char *)"\xD5\x9F" , CFLAG_NORMALCHAR }	/* U+55F (0xD59F) unchanged ;    */
	, { (unsigned char *)"\xD5\xA0" , CFLAG_NORMALCHAR }	/* U+560 (0xD5A0) unchanged ;    */
	, { (unsigned char *)"\xD5\xA1" , CFLAG_NORMALCHAR }	/* U+561 (0xD5A1) unchanged ;    */
	, { (unsigned char *)"\xD5\xA2" , CFLAG_NORMALCHAR }	/* U+562 (0xD5A2) unchanged ;    */
	, { (unsigned char *)"\xD5\xA3" , CFLAG_NORMALCHAR }	/* U+563 (0xD5A3) unchanged ;    */
	, { (unsigned char *)"\xD5\xA4" , CFLAG_NORMALCHAR }	/* U+564 (0xD5A4) unchanged ;    */
	, { (unsigned char *)"\xD5\xA5" , CFLAG_NORMALCHAR }	/* U+565 (0xD5A5) unchanged ;    */
	, { (unsigned char *)"\xD5\xA6" , CFLAG_NORMALCHAR }	/* U+566 (0xD5A6) unchanged ;    */
	, { (unsigned char *)"\xD5\xA7" , CFLAG_NORMALCHAR }	/* U+567 (0xD5A7) unchanged ;    */
	, { (unsigned char *)"\xD5\xA8" , CFLAG_NORMALCHAR }	/* U+568 (0xD5A8) unchanged ;    */
	, { (unsigned char *)"\xD5\xA9" , CFLAG_NORMALCHAR }	/* U+569 (0xD5A9) unchanged ;    */
	, { (unsigned char *)"\xD5\xAA" , CFLAG_NORMALCHAR }	/* U+56A (0xD5AA) unchanged ;    */
	, { (unsigned char *)"\xD5\xAB" , CFLAG_NORMALCHAR }	/* U+56B (0xD5AB) unchanged ;    */
	, { (unsigned char *)"\xD5\xAC" , CFLAG_NORMALCHAR }	/* U+56C (0xD5AC) unchanged ;    */
	, { (unsigned char *)"\xD5\xAD" , CFLAG_NORMALCHAR }	/* U+56D (0xD5AD) unchanged ;    */
	, { (unsigned char *)"\xD5\xAE" , CFLAG_NORMALCHAR }	/* U+56E (0xD5AE) unchanged ;    */
	, { (unsigned char *)"\xD5\xAF" , CFLAG_NORMALCHAR }	/* U+56F (0xD5AF) unchanged ;    */
	, { (unsigned char *)"\xD5\xB0" , CFLAG_NORMALCHAR }	/* U+570 (0xD5B0) unchanged ;    */
	, { (unsigned char *)"\xD5\xB1" , CFLAG_NORMALCHAR }	/* U+571 (0xD5B1) unchanged ;    */
	, { (unsigned char *)"\xD5\xB2" , CFLAG_NORMALCHAR }	/* U+572 (0xD5B2) unchanged ;    */
	, { (unsigned char *)"\xD5\xB3" , CFLAG_NORMALCHAR }	/* U+573 (0xD5B3) unchanged ;    */
	, { (unsigned char *)"\xD5\xB4" , CFLAG_NORMALCHAR }	/* U+574 (0xD5B4) unchanged ;    */
	, { (unsigned char *)"\xD5\xB5" , CFLAG_NORMALCHAR }	/* U+575 (0xD5B5) unchanged ;    */
	, { (unsigned char *)"\xD5\xB6" , CFLAG_NORMALCHAR }	/* U+576 (0xD5B6) unchanged ;    */
	, { (unsigned char *)"\xD5\xB7" , CFLAG_NORMALCHAR }	/* U+577 (0xD5B7) unchanged ;    */
	, { (unsigned char *)"\xD5\xB8" , CFLAG_NORMALCHAR }	/* U+578 (0xD5B8) unchanged ;    */
	, { (unsigned char *)"\xD5\xB9" , CFLAG_NORMALCHAR }	/* U+579 (0xD5B9) unchanged ;    */
	, { (unsigned char *)"\xD5\xBA" , CFLAG_NORMALCHAR }	/* U+57A (0xD5BA) unchanged ;    */
	, { (unsigned char *)"\xD5\xBB" , CFLAG_NORMALCHAR }	/* U+57B (0xD5BB) unchanged ;    */
	, { (unsigned char *)"\xD5\xBC" , CFLAG_NORMALCHAR }	/* U+57C (0xD5BC) unchanged ;    */
	, { (unsigned char *)"\xD5\xBD" , CFLAG_NORMALCHAR }	/* U+57D (0xD5BD) unchanged ;    */
	, { (unsigned char *)"\xD5\xBE" , CFLAG_NORMALCHAR }	/* U+57E (0xD5BE) unchanged ;    */
	, { (unsigned char *)"\xD5\xBF" , CFLAG_NORMALCHAR }	/* U+57F (0xD5BF) unchanged ;    */
	, { (unsigned char *)"\xD6\x80" , CFLAG_NORMALCHAR }	/* U+580 (0xD680) unchanged ;    */
	, { (unsigned char *)"\xD6\x81" , CFLAG_NORMALCHAR }	/* U+581 (0xD681) unchanged ;    */
	, { (unsigned char *)"\xD6\x82" , CFLAG_NORMALCHAR }	/* U+582 (0xD682) unchanged ;    */
	, { (unsigned char *)"\xD6\x83" , CFLAG_NORMALCHAR }	/* U+583 (0xD683) unchanged ;    */
	, { (unsigned char *)"\xD6\x84" , CFLAG_NORMALCHAR }	/* U+584 (0xD684) unchanged ;    */
	, { (unsigned char *)"\xD6\x85" , CFLAG_NORMALCHAR }	/* U+585 (0xD685) unchanged ;    */
	, { (unsigned char *)"\xD6\x86" , CFLAG_NORMALCHAR }	/* U+586 (0xD686) unchanged ;    */
	, { (unsigned char *)"\xD6\x87" , CFLAG_NORMALCHAR }	/* U+587 (0xD687) unchanged ;    */
	, { (unsigned char *)"\xD6\x88" , CFLAG_NORMALCHAR }	/* U+588 (0xD688) unchanged ;    */
	, { (unsigned char *)"\xD6\x89" , CFLAG_NORMALCHAR }	/* U+589 (0xD689) unchanged ;    */
	, { (unsigned char *)"\xD6\x8A" , CFLAG_NORMALCHAR }	/* U+58A (0xD68A) unchanged ;    */
	, { (unsigned char *)"\xD6\x8B" , CFLAG_NORMALCHAR }	/* U+58B (0xD68B) unchanged ;    */
	, { (unsigned char *)"\xD6\x8C" , CFLAG_NORMALCHAR }	/* U+58C (0xD68C) unchanged ;    */
	, { (unsigned char *)"\xD6\x8D" , CFLAG_NORMALCHAR }	/* U+58D (0xD68D) unchanged ;    */
	, { (unsigned char *)"\xD6\x8E" , CFLAG_NORMALCHAR }	/* U+58E (0xD68E) unchanged ;    */
	, { (unsigned char *)"\xD6\x8F" , CFLAG_NORMALCHAR }	/* U+58F (0xD68F) unchanged ;    */
	, { (unsigned char *)"\xD6\x90" , CFLAG_NORMALCHAR }	/* U+590 (0xD690) unchanged ;    */
	, { (unsigned char *)"\xD6\x91" , CFLAG_NORMALCHAR }	/* U+591 (0xD691) unchanged ;    */
	, { (unsigned char *)"\xD6\x92" , CFLAG_NORMALCHAR }	/* U+592 (0xD692) unchanged ;    */
	, { (unsigned char *)"\xD6\x93" , CFLAG_NORMALCHAR }	/* U+593 (0xD693) unchanged ;    */
	, { (unsigned char *)"\xD6\x94" , CFLAG_NORMALCHAR }	/* U+594 (0xD694) unchanged ;    */
	, { (unsigned char *)"\xD6\x95" , CFLAG_NORMALCHAR }	/* U+595 (0xD695) unchanged ;    */
	, { (unsigned char *)"\xD6\x96" , CFLAG_NORMALCHAR }	/* U+596 (0xD696) unchanged ;    */
	, { (unsigned char *)"\xD6\x97" , CFLAG_NORMALCHAR }	/* U+597 (0xD697) unchanged ;    */
	, { (unsigned char *)"\xD6\x98" , CFLAG_NORMALCHAR }	/* U+598 (0xD698) unchanged ;    */
	, { (unsigned char *)"\xD6\x99" , CFLAG_NORMALCHAR }	/* U+599 (0xD699) unchanged ;    */
	, { (unsigned char *)"\xD6\x9A" , CFLAG_NORMALCHAR }	/* U+59A (0xD69A) unchanged ;    */
	, { (unsigned char *)"\xD6\x9B" , CFLAG_NORMALCHAR }	/* U+59B (0xD69B) unchanged ;    */
	, { (unsigned char *)"\xD6\x9C" , CFLAG_NORMALCHAR }	/* U+59C (0xD69C) unchanged ;    */
	, { (unsigned char *)"\xD6\x9D" , CFLAG_NORMALCHAR }	/* U+59D (0xD69D) unchanged ;    */
	, { (unsigned char *)"\xD6\x9E" , CFLAG_NORMALCHAR }	/* U+59E (0xD69E) unchanged ;    */
	, { (unsigned char *)"\xD6\x9F" , CFLAG_NORMALCHAR }	/* U+59F (0xD69F) unchanged ;    */
	, { (unsigned char *)"\xD6\xA0" , CFLAG_NORMALCHAR }	/* U+5A0 (0xD6A0) unchanged ;    */
	, { (unsigned char *)"\xD6\xA1" , CFLAG_NORMALCHAR }	/* U+5A1 (0xD6A1) unchanged ;    */
	, { (unsigned char *)"\xD6\xA2" , CFLAG_NORMALCHAR }	/* U+5A2 (0xD6A2) unchanged ;    */
	, { (unsigned char *)"\xD6\xA3" , CFLAG_NORMALCHAR }	/* U+5A3 (0xD6A3) unchanged ;    */
	, { (unsigned char *)"\xD6\xA4" , CFLAG_NORMALCHAR }	/* U+5A4 (0xD6A4) unchanged ;    */
	, { (unsigned char *)"\xD6\xA5" , CFLAG_NORMALCHAR }	/* U+5A5 (0xD6A5) unchanged ;    */
	, { (unsigned char *)"\xD6\xA6" , CFLAG_NORMALCHAR }	/* U+5A6 (0xD6A6) unchanged ;    */
	, { (unsigned char *)"\xD6\xA7" , CFLAG_NORMALCHAR }	/* U+5A7 (0xD6A7) unchanged ;    */
	, { (unsigned char *)"\xD6\xA8" , CFLAG_NORMALCHAR }	/* U+5A8 (0xD6A8) unchanged ;    */
	, { (unsigned char *)"\xD6\xA9" , CFLAG_NORMALCHAR }	/* U+5A9 (0xD6A9) unchanged ;    */
	, { (unsigned char *)"\xD6\xAA" , CFLAG_NORMALCHAR }	/* U+5AA (0xD6AA) unchanged ;    */
	, { (unsigned char *)"\xD6\xAB" , CFLAG_NORMALCHAR }	/* U+5AB (0xD6AB) unchanged ;    */
	, { (unsigned char *)"\xD6\xAC" , CFLAG_NORMALCHAR }	/* U+5AC (0xD6AC) unchanged ;    */
	, { (unsigned char *)"\xD6\xAD" , CFLAG_NORMALCHAR }	/* U+5AD (0xD6AD) unchanged ;    */
	, { (unsigned char *)"\xD6\xAE" , CFLAG_NORMALCHAR }	/* U+5AE (0xD6AE) unchanged ;    */
	, { (unsigned char *)"\xD6\xAF" , CFLAG_NORMALCHAR }	/* U+5AF (0xD6AF) unchanged ;    */
	, { (unsigned char *)"\xD6\xB0" , CFLAG_NORMALCHAR }	/* U+5B0 (0xD6B0) unchanged ;    */
	, { (unsigned char *)"\xD6\xB1" , CFLAG_NORMALCHAR }	/* U+5B1 (0xD6B1) unchanged ;    */
	, { (unsigned char *)"\xD6\xB2" , CFLAG_NORMALCHAR }	/* U+5B2 (0xD6B2) unchanged ;    */
	, { (unsigned char *)"\xD6\xB3" , CFLAG_NORMALCHAR }	/* U+5B3 (0xD6B3) unchanged ;    */
	, { (unsigned char *)"\xD6\xB4" , CFLAG_NORMALCHAR }	/* U+5B4 (0xD6B4) unchanged ;    */
	, { (unsigned char *)"\xD6\xB5" , CFLAG_NORMALCHAR }	/* U+5B5 (0xD6B5) unchanged ;    */
	, { (unsigned char *)"\xD6\xB6" , CFLAG_NORMALCHAR }	/* U+5B6 (0xD6B6) unchanged ;    */
	, { (unsigned char *)"\xD6\xB7" , CFLAG_NORMALCHAR }	/* U+5B7 (0xD6B7) unchanged ;    */
	, { (unsigned char *)"\xD6\xB8" , CFLAG_NORMALCHAR }	/* U+5B8 (0xD6B8) unchanged ;    */
	, { (unsigned char *)"\xD6\xB9" , CFLAG_NORMALCHAR }	/* U+5B9 (0xD6B9) unchanged ;    */
	, { (unsigned char *)"\xD6\xBA" , CFLAG_NORMALCHAR }	/* U+5BA (0xD6BA) unchanged ;    */
	, { (unsigned char *)"\xD6\xBB" , CFLAG_NORMALCHAR }	/* U+5BB (0xD6BB) unchanged ;    */
	, { (unsigned char *)"\xD6\xBC" , CFLAG_NORMALCHAR }	/* U+5BC (0xD6BC) unchanged ;    */
	, { (unsigned char *)"\xD6\xBD" , CFLAG_NORMALCHAR }	/* U+5BD (0xD6BD) unchanged ;    */
	, { (unsigned char *)"\xD6\xBE" , CFLAG_NORMALCHAR }	/* U+5BE (0xD6BE) unchanged ;    */
	, { (unsigned char *)"\xD6\xBF" , CFLAG_NORMALCHAR }	/* U+5BF (0xD6BF) unchanged ;    */
	, { (unsigned char *)"\xD7\x80" , CFLAG_NORMALCHAR }	/* U+5C0 (0xD780) unchanged ;    */
	, { (unsigned char *)"\xD7\x81" , CFLAG_NORMALCHAR }	/* U+5C1 (0xD781) unchanged ;    */
	, { (unsigned char *)"\xD7\x82" , CFLAG_NORMALCHAR }	/* U+5C2 (0xD782) unchanged ;    */
	, { (unsigned char *)"\xD7\x83" , CFLAG_NORMALCHAR }	/* U+5C3 (0xD783) unchanged ;    */
	, { (unsigned char *)"\xD7\x84" , CFLAG_NORMALCHAR }	/* U+5C4 (0xD784) unchanged ;    */
	, { (unsigned char *)"\xD7\x85" , CFLAG_NORMALCHAR }	/* U+5C5 (0xD785) unchanged ;    */
	, { (unsigned char *)"\xD7\x86" , CFLAG_NORMALCHAR }	/* U+5C6 (0xD786) unchanged ;    */
	, { (unsigned char *)"\xD7\x87" , CFLAG_NORMALCHAR }	/* U+5C7 (0xD787) unchanged ;    */
	, { (unsigned char *)"\xD7\x88" , CFLAG_NORMALCHAR }	/* U+5C8 (0xD788) unchanged ;    */
	, { (unsigned char *)"\xD7\x89" , CFLAG_NORMALCHAR }	/* U+5C9 (0xD789) unchanged ;    */
	, { (unsigned char *)"\xD7\x8A" , CFLAG_NORMALCHAR }	/* U+5CA (0xD78A) unchanged ;    */
	, { (unsigned char *)"\xD7\x8B" , CFLAG_NORMALCHAR }	/* U+5CB (0xD78B) unchanged ;    */
	, { (unsigned char *)"\xD7\x8C" , CFLAG_NORMALCHAR }	/* U+5CC (0xD78C) unchanged ;    */
	, { (unsigned char *)"\xD7\x8D" , CFLAG_NORMALCHAR }	/* U+5CD (0xD78D) unchanged ;    */
	, { (unsigned char *)"\xD7\x8E" , CFLAG_NORMALCHAR }	/* U+5CE (0xD78E) unchanged ;    */
	, { (unsigned char *)"\xD7\x8F" , CFLAG_NORMALCHAR }	/* U+5CF (0xD78F) unchanged ;    */
	, { (unsigned char *)"\xD7\x90" , CFLAG_NORMALCHAR }	/* U+5D0 (0xD790) unchanged ;    */
	, { (unsigned char *)"\xD7\x91" , CFLAG_NORMALCHAR }	/* U+5D1 (0xD791) unchanged ;    */
	, { (unsigned char *)"\xD7\x92" , CFLAG_NORMALCHAR }	/* U+5D2 (0xD792) unchanged ;    */
	, { (unsigned char *)"\xD7\x93" , CFLAG_NORMALCHAR }	/* U+5D3 (0xD793) unchanged ;    */
	, { (unsigned char *)"\xD7\x94" , CFLAG_NORMALCHAR }	/* U+5D4 (0xD794) unchanged ;    */
	, { (unsigned char *)"\xD7\x95" , CFLAG_NORMALCHAR }	/* U+5D5 (0xD795) unchanged ;    */
	, { (unsigned char *)"\xD7\x96" , CFLAG_NORMALCHAR }	/* U+5D6 (0xD796) unchanged ;    */
	, { (unsigned char *)"\xD7\x97" , CFLAG_NORMALCHAR }	/* U+5D7 (0xD797) unchanged ;    */
	, { (unsigned char *)"\xD7\x98" , CFLAG_NORMALCHAR }	/* U+5D8 (0xD798) unchanged ;    */
	, { (unsigned char *)"\xD7\x99" , CFLAG_NORMALCHAR }	/* U+5D9 (0xD799) unchanged ;    */
	, { (unsigned char *)"\xD7\x9A" , CFLAG_NORMALCHAR }	/* U+5DA (0xD79A) unchanged ;    */
	, { (unsigned char *)"\xD7\x9B" , CFLAG_NORMALCHAR }	/* U+5DB (0xD79B) unchanged ;    */
	, { (unsigned char *)"\xD7\x9C" , CFLAG_NORMALCHAR }	/* U+5DC (0xD79C) unchanged ;    */
	, { (unsigned char *)"\xD7\x9D" , CFLAG_NORMALCHAR }	/* U+5DD (0xD79D) unchanged ;    */
	, { (unsigned char *)"\xD7\x9E" , CFLAG_NORMALCHAR }	/* U+5DE (0xD79E) unchanged ;    */
	, { (unsigned char *)"\xD7\x9F" , CFLAG_NORMALCHAR }	/* U+5DF (0xD79F) unchanged ;    */
	, { (unsigned char *)"\xD7\xA0" , CFLAG_NORMALCHAR }	/* U+5E0 (0xD7A0) unchanged ;    */
	, { (unsigned char *)"\xD7\xA1" , CFLAG_NORMALCHAR }	/* U+5E1 (0xD7A1) unchanged ;    */
	, { (unsigned char *)"\xD7\xA2" , CFLAG_NORMALCHAR }	/* U+5E2 (0xD7A2) unchanged ;    */
	, { (unsigned char *)"\xD7\xA3" , CFLAG_NORMALCHAR }	/* U+5E3 (0xD7A3) unchanged ;    */
	, { (unsigned char *)"\xD7\xA4" , CFLAG_NORMALCHAR }	/* U+5E4 (0xD7A4) unchanged ;    */
	, { (unsigned char *)"\xD7\xA5" , CFLAG_NORMALCHAR }	/* U+5E5 (0xD7A5) unchanged ;    */
	, { (unsigned char *)"\xD7\xA6" , CFLAG_NORMALCHAR }	/* U+5E6 (0xD7A6) unchanged ;    */
	, { (unsigned char *)"\xD7\xA7" , CFLAG_NORMALCHAR }	/* U+5E7 (0xD7A7) unchanged ;    */
	, { (unsigned char *)"\xD7\xA8" , CFLAG_NORMALCHAR }	/* U+5E8 (0xD7A8) unchanged ;    */
	, { (unsigned char *)"\xD7\xA9" , CFLAG_NORMALCHAR }	/* U+5E9 (0xD7A9) unchanged ;    */
	, { (unsigned char *)"\xD7\xAA" , CFLAG_NORMALCHAR }	/* U+5EA (0xD7AA) unchanged ;    */
	, { (unsigned char *)"\xD7\xAB" , CFLAG_NORMALCHAR }	/* U+5EB (0xD7AB) unchanged ;    */
	, { (unsigned char *)"\xD7\xAC" , CFLAG_NORMALCHAR }	/* U+5EC (0xD7AC) unchanged ;    */
	, { (unsigned char *)"\xD7\xAD" , CFLAG_NORMALCHAR }	/* U+5ED (0xD7AD) unchanged ;    */
	, { (unsigned char *)"\xD7\xAE" , CFLAG_NORMALCHAR }	/* U+5EE (0xD7AE) unchanged ;    */
	, { (unsigned char *)"\xD7\xAF" , CFLAG_NORMALCHAR }	/* U+5EF (0xD7AF) unchanged ;    */
	, { (unsigned char *)"\xD7\xB0" , CFLAG_NORMALCHAR }	/* U+5F0 (0xD7B0) unchanged ;    */
	, { (unsigned char *)"\xD7\xB1" , CFLAG_NORMALCHAR }	/* U+5F1 (0xD7B1) unchanged ;    */
	, { (unsigned char *)"\xD7\xB2" , CFLAG_NORMALCHAR }	/* U+5F2 (0xD7B2) unchanged ;    */
	, { (unsigned char *)"\xD7\xB3" , CFLAG_NORMALCHAR }	/* U+5F3 (0xD7B3) unchanged ;    */
	, { (unsigned char *)"\xD7\xB4" , CFLAG_NORMALCHAR }	/* U+5F4 (0xD7B4) unchanged ;    */
	, { (unsigned char *)"\xD7\xB5" , CFLAG_NORMALCHAR }	/* U+5F5 (0xD7B5) unchanged ;    */
	, { (unsigned char *)"\xD7\xB6" , CFLAG_NORMALCHAR }	/* U+5F6 (0xD7B6) unchanged ;    */
	, { (unsigned char *)"\xD7\xB7" , CFLAG_NORMALCHAR }	/* U+5F7 (0xD7B7) unchanged ;    */
	, { (unsigned char *)"\xD7\xB8" , CFLAG_NORMALCHAR }	/* U+5F8 (0xD7B8) unchanged ;    */
	, { (unsigned char *)"\xD7\xB9" , CFLAG_NORMALCHAR }	/* U+5F9 (0xD7B9) unchanged ;    */
	, { (unsigned char *)"\xD7\xBA" , CFLAG_NORMALCHAR }	/* U+5FA (0xD7BA) unchanged ;    */
	, { (unsigned char *)"\xD7\xBB" , CFLAG_NORMALCHAR }	/* U+5FB (0xD7BB) unchanged ;    */
	, { (unsigned char *)"\xD7\xBC" , CFLAG_NORMALCHAR }	/* U+5FC (0xD7BC) unchanged ;    */
	, { (unsigned char *)"\xD7\xBD" , CFLAG_NORMALCHAR }	/* U+5FD (0xD7BD) unchanged ;    */
	, { (unsigned char *)"\xD7\xBE" , CFLAG_NORMALCHAR }	/* U+5FE (0xD7BE) unchanged ;    */
	, { (unsigned char *)"\xD7\xBF" , CFLAG_NORMALCHAR }	/* U+5FF (0xD7BF) unchanged ;    */
	, { (unsigned char *)"\xD8\x80" , CFLAG_NORMALCHAR }	/* U+600 (0xD880) unchanged ;    */
	, { (unsigned char *)"\xD8\x81" , CFLAG_NORMALCHAR }	/* U+601 (0xD881) unchanged ;    */
	, { (unsigned char *)"\xD8\x82" , CFLAG_NORMALCHAR }	/* U+602 (0xD882) unchanged ;    */
	, { (unsigned char *)"\xD8\x83" , CFLAG_NORMALCHAR }	/* U+603 (0xD883) unchanged ;    */
	, { (unsigned char *)"\xD8\x84" , CFLAG_NORMALCHAR }	/* U+604 (0xD884) unchanged ;    */
	, { (unsigned char *)"\xD8\x85" , CFLAG_NORMALCHAR }	/* U+605 (0xD885) unchanged ;    */
	, { (unsigned char *)"\xD8\x86" , CFLAG_NORMALCHAR }	/* U+606 (0xD886) unchanged ;    */
	, { (unsigned char *)"\xD8\x87" , CFLAG_NORMALCHAR }	/* U+607 (0xD887) unchanged ;    */
	, { (unsigned char *)"\xD8\x88" , CFLAG_NORMALCHAR }	/* U+608 (0xD888) unchanged ;    */
	, { (unsigned char *)"\xD8\x89" , CFLAG_NORMALCHAR }	/* U+609 (0xD889) unchanged ;    */
	, { (unsigned char *)"\xD8\x8A" , CFLAG_NORMALCHAR }	/* U+60A (0xD88A) unchanged ;    */
	, { (unsigned char *)"\xD8\x8B" , CFLAG_NORMALCHAR }	/* U+60B (0xD88B) unchanged ;    */
	, { (unsigned char *)"\xD8\x8C" , CFLAG_NORMALCHAR }	/* U+60C (0xD88C) unchanged ;    */
	, { (unsigned char *)"\xD8\x8D" , CFLAG_NORMALCHAR }	/* U+60D (0xD88D) unchanged ;    */
	, { (unsigned char *)"\xD8\x8E" , CFLAG_NORMALCHAR }	/* U+60E (0xD88E) unchanged ;    */
	, { (unsigned char *)"\xD8\x8F" , CFLAG_NORMALCHAR }	/* U+60F (0xD88F) unchanged ;    */
	, { (unsigned char *)"\xD8\x90" , CFLAG_NORMALCHAR }	/* U+610 (0xD890) unchanged ;    */
	, { (unsigned char *)"\xD8\x91" , CFLAG_NORMALCHAR }	/* U+611 (0xD891) unchanged ;    */
	, { (unsigned char *)"\xD8\x92" , CFLAG_NORMALCHAR }	/* U+612 (0xD892) unchanged ;    */
	, { (unsigned char *)"\xD8\x93" , CFLAG_NORMALCHAR }	/* U+613 (0xD893) unchanged ;    */
	, { (unsigned char *)"\xD8\x94" , CFLAG_NORMALCHAR }	/* U+614 (0xD894) unchanged ;    */
	, { (unsigned char *)"\xD8\x95" , CFLAG_NORMALCHAR }	/* U+615 (0xD895) unchanged ;    */
	, { (unsigned char *)"\xD8\x96" , CFLAG_NORMALCHAR }	/* U+616 (0xD896) unchanged ;    */
	, { (unsigned char *)"\xD8\x97" , CFLAG_NORMALCHAR }	/* U+617 (0xD897) unchanged ;    */
	, { (unsigned char *)"\xD8\x98" , CFLAG_NORMALCHAR }	/* U+618 (0xD898) unchanged ;    */
	, { (unsigned char *)"\xD8\x99" , CFLAG_NORMALCHAR }	/* U+619 (0xD899) unchanged ;    */
	, { (unsigned char *)"\xD8\x9A" , CFLAG_NORMALCHAR }	/* U+61A (0xD89A) unchanged ;    */
	, { (unsigned char *)"\xD8\x9B" , CFLAG_NORMALCHAR }	/* U+61B (0xD89B) unchanged ;    */
	, { (unsigned char *)"\xD8\x9C" , CFLAG_NORMALCHAR }	/* U+61C (0xD89C) unchanged ;    */
	, { (unsigned char *)"\xD8\x9D" , CFLAG_NORMALCHAR }	/* U+61D (0xD89D) unchanged ;    */
	, { (unsigned char *)"\xD8\x9E" , CFLAG_NORMALCHAR }	/* U+61E (0xD89E) unchanged ;    */
	, { (unsigned char *)"\xD8\x9F" , CFLAG_NORMALCHAR }	/* U+61F (0xD89F) unchanged ;    */
	, { (unsigned char *)"\xD8\xA0" , CFLAG_NORMALCHAR }	/* U+620 (0xD8A0) unchanged ;    */
	, { (unsigned char *)"\xD8\xA1" , CFLAG_NORMALCHAR }	/* U+621 (0xD8A1) unchanged ;    */
	, { (unsigned char *)"\xD8\xA2" , CFLAG_NORMALCHAR }	/* U+622 (0xD8A2) unchanged ;    */
	, { (unsigned char *)"\xD8\xA3" , CFLAG_NORMALCHAR }	/* U+623 (0xD8A3) unchanged ;    */
	, { (unsigned char *)"\xD8\xA4" , CFLAG_NORMALCHAR }	/* U+624 (0xD8A4) unchanged ;    */
	, { (unsigned char *)"\xD8\xA5" , CFLAG_NORMALCHAR }	/* U+625 (0xD8A5) unchanged ;    */
	, { (unsigned char *)"\xD8\xA6" , CFLAG_NORMALCHAR }	/* U+626 (0xD8A6) unchanged ;    */
	, { (unsigned char *)"\xD8\xA7" , CFLAG_NORMALCHAR }	/* U+627 (0xD8A7) unchanged ;    */
	, { (unsigned char *)"\xD8\xA8" , CFLAG_NORMALCHAR }	/* U+628 (0xD8A8) unchanged ;    */
	, { (unsigned char *)"\xD8\xA9" , CFLAG_NORMALCHAR }	/* U+629 (0xD8A9) unchanged ;    */
	, { (unsigned char *)"\xD8\xAA" , CFLAG_NORMALCHAR }	/* U+62A (0xD8AA) unchanged ;    */
	, { (unsigned char *)"\xD8\xAB" , CFLAG_NORMALCHAR }	/* U+62B (0xD8AB) unchanged ;    */
	, { (unsigned char *)"\xD8\xAC" , CFLAG_NORMALCHAR }	/* U+62C (0xD8AC) unchanged ;    */
	, { (unsigned char *)"\xD8\xAD" , CFLAG_NORMALCHAR }	/* U+62D (0xD8AD) unchanged ;    */
	, { (unsigned char *)"\xD8\xAE" , CFLAG_NORMALCHAR }	/* U+62E (0xD8AE) unchanged ;    */
	, { (unsigned char *)"\xD8\xAF" , CFLAG_NORMALCHAR }	/* U+62F (0xD8AF) unchanged ;    */
	, { (unsigned char *)"\xD8\xB0" , CFLAG_NORMALCHAR }	/* U+630 (0xD8B0) unchanged ;    */
	, { (unsigned char *)"\xD8\xB1" , CFLAG_NORMALCHAR }	/* U+631 (0xD8B1) unchanged ;    */
	, { (unsigned char *)"\xD8\xB2" , CFLAG_NORMALCHAR }	/* U+632 (0xD8B2) unchanged ;    */
	, { (unsigned char *)"\xD8\xB3" , CFLAG_NORMALCHAR }	/* U+633 (0xD8B3) unchanged ;    */
	, { (unsigned char *)"\xD8\xB4" , CFLAG_NORMALCHAR }	/* U+634 (0xD8B4) unchanged ;    */
	, { (unsigned char *)"\xD8\xB5" , CFLAG_NORMALCHAR }	/* U+635 (0xD8B5) unchanged ;    */
	, { (unsigned char *)"\xD8\xB6" , CFLAG_NORMALCHAR }	/* U+636 (0xD8B6) unchanged ;    */
	, { (unsigned char *)"\xD8\xB7" , CFLAG_NORMALCHAR }	/* U+637 (0xD8B7) unchanged ;    */
	, { (unsigned char *)"\xD8\xB8" , CFLAG_NORMALCHAR }	/* U+638 (0xD8B8) unchanged ;    */
	, { (unsigned char *)"\xD8\xB9" , CFLAG_NORMALCHAR }	/* U+639 (0xD8B9) unchanged ;    */
	, { (unsigned char *)"\xD8\xBA" , CFLAG_NORMALCHAR }	/* U+63A (0xD8BA) unchanged ;    */
	, { (unsigned char *)"\xD8\xBB" , CFLAG_NORMALCHAR }	/* U+63B (0xD8BB) unchanged ;    */
	, { (unsigned char *)"\xD8\xBC" , CFLAG_NORMALCHAR }	/* U+63C (0xD8BC) unchanged ;    */
	, { (unsigned char *)"\xD8\xBD" , CFLAG_NORMALCHAR }	/* U+63D (0xD8BD) unchanged ;    */
	, { (unsigned char *)"\xD8\xBE" , CFLAG_NORMALCHAR }	/* U+63E (0xD8BE) unchanged ;    */
	, { (unsigned char *)"\xD8\xBF" , CFLAG_NORMALCHAR }	/* U+63F (0xD8BF) unchanged ;    */
	, { (unsigned char *)"\xD9\x80" , CFLAG_NORMALCHAR }	/* U+640 (0xD980) unchanged ;    */
	, { (unsigned char *)"\xD9\x81" , CFLAG_NORMALCHAR }	/* U+641 (0xD981) unchanged ;    */
	, { (unsigned char *)"\xD9\x82" , CFLAG_NORMALCHAR }	/* U+642 (0xD982) unchanged ;    */
	, { (unsigned char *)"\xD9\x83" , CFLAG_NORMALCHAR }	/* U+643 (0xD983) unchanged ;    */
	, { (unsigned char *)"\xD9\x84" , CFLAG_NORMALCHAR }	/* U+644 (0xD984) unchanged ;    */
	, { (unsigned char *)"\xD9\x85" , CFLAG_NORMALCHAR }	/* U+645 (0xD985) unchanged ;    */
	, { (unsigned char *)"\xD9\x86" , CFLAG_NORMALCHAR }	/* U+646 (0xD986) unchanged ;    */
	, { (unsigned char *)"\xD9\x87" , CFLAG_NORMALCHAR }	/* U+647 (0xD987) unchanged ;    */
	, { (unsigned char *)"\xD9\x88" , CFLAG_NORMALCHAR }	/* U+648 (0xD988) unchanged ;    */
	, { (unsigned char *)"\xD9\x89" , CFLAG_NORMALCHAR }	/* U+649 (0xD989) unchanged ;    */
	, { (unsigned char *)"\xD9\x8A" , CFLAG_NORMALCHAR }	/* U+64A (0xD98A) unchanged ;    */
	, { (unsigned char *)"\xD9\x8B" , CFLAG_NORMALCHAR }	/* U+64B (0xD98B) unchanged ;    */
	, { (unsigned char *)"\xD9\x8C" , CFLAG_NORMALCHAR }	/* U+64C (0xD98C) unchanged ;    */
	, { (unsigned char *)"\xD9\x8D" , CFLAG_NORMALCHAR }	/* U+64D (0xD98D) unchanged ;    */
	, { (unsigned char *)"\xD9\x8E" , CFLAG_NORMALCHAR }	/* U+64E (0xD98E) unchanged ;    */
	, { (unsigned char *)"\xD9\x8F" , CFLAG_NORMALCHAR }	/* U+64F (0xD98F) unchanged ;    */
	, { (unsigned char *)"\xD9\x90" , CFLAG_NORMALCHAR }	/* U+650 (0xD990) unchanged ;    */
	, { (unsigned char *)"\xD9\x91" , CFLAG_NORMALCHAR }	/* U+651 (0xD991) unchanged ;    */
	, { (unsigned char *)"\xD9\x92" , CFLAG_NORMALCHAR }	/* U+652 (0xD992) unchanged ;    */
	, { (unsigned char *)"\xD9\x93" , CFLAG_NORMALCHAR }	/* U+653 (0xD993) unchanged ;    */
	, { (unsigned char *)"\xD9\x94" , CFLAG_NORMALCHAR }	/* U+654 (0xD994) unchanged ;    */
	, { (unsigned char *)"\xD9\x95" , CFLAG_NORMALCHAR }	/* U+655 (0xD995) unchanged ;    */
	, { (unsigned char *)"\xD9\x96" , CFLAG_NORMALCHAR }	/* U+656 (0xD996) unchanged ;    */
	, { (unsigned char *)"\xD9\x97" , CFLAG_NORMALCHAR }	/* U+657 (0xD997) unchanged ;    */
	, { (unsigned char *)"\xD9\x98" , CFLAG_NORMALCHAR }	/* U+658 (0xD998) unchanged ;    */
	, { (unsigned char *)"\xD9\x99" , CFLAG_NORMALCHAR }	/* U+659 (0xD999) unchanged ;    */
	, { (unsigned char *)"\xD9\x9A" , CFLAG_NORMALCHAR }	/* U+65A (0xD99A) unchanged ;    */
	, { (unsigned char *)"\xD9\x9B" , CFLAG_NORMALCHAR }	/* U+65B (0xD99B) unchanged ;    */
	, { (unsigned char *)"\xD9\x9C" , CFLAG_NORMALCHAR }	/* U+65C (0xD99C) unchanged ;    */
	, { (unsigned char *)"\xD9\x9D" , CFLAG_NORMALCHAR }	/* U+65D (0xD99D) unchanged ;    */
	, { (unsigned char *)"\xD9\x9E" , CFLAG_NORMALCHAR }	/* U+65E (0xD99E) unchanged ;    */
	, { (unsigned char *)"\xD9\x9F" , CFLAG_NORMALCHAR }	/* U+65F (0xD99F) unchanged ;    */
	, { (unsigned char *)"\xD9\xA0" , CFLAG_NORMALCHAR }	/* U+660 (0xD9A0) unchanged ;    */
	, { (unsigned char *)"\xD9\xA1" , CFLAG_NORMALCHAR }	/* U+661 (0xD9A1) unchanged ;    */
	, { (unsigned char *)"\xD9\xA2" , CFLAG_NORMALCHAR }	/* U+662 (0xD9A2) unchanged ;    */
	, { (unsigned char *)"\xD9\xA3" , CFLAG_NORMALCHAR }	/* U+663 (0xD9A3) unchanged ;    */
	, { (unsigned char *)"\xD9\xA4" , CFLAG_NORMALCHAR }	/* U+664 (0xD9A4) unchanged ;    */
	, { (unsigned char *)"\xD9\xA5" , CFLAG_NORMALCHAR }	/* U+665 (0xD9A5) unchanged ;    */
	, { (unsigned char *)"\xD9\xA6" , CFLAG_NORMALCHAR }	/* U+666 (0xD9A6) unchanged ;    */
	, { (unsigned char *)"\xD9\xA7" , CFLAG_NORMALCHAR }	/* U+667 (0xD9A7) unchanged ;    */
	, { (unsigned char *)"\xD9\xA8" , CFLAG_NORMALCHAR }	/* U+668 (0xD9A8) unchanged ;    */
	, { (unsigned char *)"\xD9\xA9" , CFLAG_NORMALCHAR }	/* U+669 (0xD9A9) unchanged ;    */
	, { (unsigned char *)"\xD9\xAA" , CFLAG_NORMALCHAR }	/* U+66A (0xD9AA) unchanged ;    */
	, { (unsigned char *)"\xD9\xAB" , CFLAG_NORMALCHAR }	/* U+66B (0xD9AB) unchanged ;    */
	, { (unsigned char *)"\xD9\xAC" , CFLAG_NORMALCHAR }	/* U+66C (0xD9AC) unchanged ;    */
	, { (unsigned char *)"\xD9\xAD" , CFLAG_NORMALCHAR }	/* U+66D (0xD9AD) unchanged ;    */
	, { (unsigned char *)"\xD9\xAE" , CFLAG_NORMALCHAR }	/* U+66E (0xD9AE) unchanged ;    */
	, { (unsigned char *)"\xD9\xAF" , CFLAG_NORMALCHAR }	/* U+66F (0xD9AF) unchanged ;    */
	, { (unsigned char *)"\xD9\xB0" , CFLAG_NORMALCHAR }	/* U+670 (0xD9B0) unchanged ;    */
	, { (unsigned char *)"\xD9\xB1" , CFLAG_NORMALCHAR }	/* U+671 (0xD9B1) unchanged ;    */
	, { (unsigned char *)"\xD9\xB2" , CFLAG_NORMALCHAR }	/* U+672 (0xD9B2) unchanged ;    */
	, { (unsigned char *)"\xD9\xB3" , CFLAG_NORMALCHAR }	/* U+673 (0xD9B3) unchanged ;    */
	, { (unsigned char *)"\xD9\xB4" , CFLAG_NORMALCHAR }	/* U+674 (0xD9B4) unchanged ;    */
	, { (unsigned char *)"\xD9\xB5" , CFLAG_NORMALCHAR }	/* U+675 (0xD9B5) unchanged ;    */
	, { (unsigned char *)"\xD9\xB6" , CFLAG_NORMALCHAR }	/* U+676 (0xD9B6) unchanged ;    */
	, { (unsigned char *)"\xD9\xB7" , CFLAG_NORMALCHAR }	/* U+677 (0xD9B7) unchanged ;    */
	, { (unsigned char *)"\xD9\xB8" , CFLAG_NORMALCHAR }	/* U+678 (0xD9B8) unchanged ;    */
	, { (unsigned char *)"\xD9\xB9" , CFLAG_NORMALCHAR }	/* U+679 (0xD9B9) unchanged ;    */
	, { (unsigned char *)"\xD9\xBA" , CFLAG_NORMALCHAR }	/* U+67A (0xD9BA) unchanged ;    */
	, { (unsigned char *)"\xD9\xBB" , CFLAG_NORMALCHAR }	/* U+67B (0xD9BB) unchanged ;    */
	, { (unsigned char *)"\xD9\xBC" , CFLAG_NORMALCHAR }	/* U+67C (0xD9BC) unchanged ;    */
	, { (unsigned char *)"\xD9\xBD" , CFLAG_NORMALCHAR }	/* U+67D (0xD9BD) unchanged ;    */
	, { (unsigned char *)"\xD9\xBE" , CFLAG_NORMALCHAR }	/* U+67E (0xD9BE) unchanged ;    */
	, { (unsigned char *)"\xD9\xBF" , CFLAG_NORMALCHAR }	/* U+67F (0xD9BF) unchanged ;    */
	, { (unsigned char *)"\xDA\x80" , CFLAG_NORMALCHAR }	/* U+680 (0xDA80) unchanged ;    */
	, { (unsigned char *)"\xDA\x81" , CFLAG_NORMALCHAR }	/* U+681 (0xDA81) unchanged ;    */
	, { (unsigned char *)"\xDA\x82" , CFLAG_NORMALCHAR }	/* U+682 (0xDA82) unchanged ;    */
	, { (unsigned char *)"\xDA\x83" , CFLAG_NORMALCHAR }	/* U+683 (0xDA83) unchanged ;    */
	, { (unsigned char *)"\xDA\x84" , CFLAG_NORMALCHAR }	/* U+684 (0xDA84) unchanged ;    */
	, { (unsigned char *)"\xDA\x85" , CFLAG_NORMALCHAR }	/* U+685 (0xDA85) unchanged ;    */
	, { (unsigned char *)"\xDA\x86" , CFLAG_NORMALCHAR }	/* U+686 (0xDA86) unchanged ;    */
	, { (unsigned char *)"\xDA\x87" , CFLAG_NORMALCHAR }	/* U+687 (0xDA87) unchanged ;    */
	, { (unsigned char *)"\xDA\x88" , CFLAG_NORMALCHAR }	/* U+688 (0xDA88) unchanged ;    */
	, { (unsigned char *)"\xDA\x89" , CFLAG_NORMALCHAR }	/* U+689 (0xDA89) unchanged ;    */
	, { (unsigned char *)"\xDA\x8A" , CFLAG_NORMALCHAR }	/* U+68A (0xDA8A) unchanged ;    */
	, { (unsigned char *)"\xDA\x8B" , CFLAG_NORMALCHAR }	/* U+68B (0xDA8B) unchanged ;    */
	, { (unsigned char *)"\xDA\x8C" , CFLAG_NORMALCHAR }	/* U+68C (0xDA8C) unchanged ;    */
	, { (unsigned char *)"\xDA\x8D" , CFLAG_NORMALCHAR }	/* U+68D (0xDA8D) unchanged ;    */
	, { (unsigned char *)"\xDA\x8E" , CFLAG_NORMALCHAR }	/* U+68E (0xDA8E) unchanged ;    */
	, { (unsigned char *)"\xDA\x8F" , CFLAG_NORMALCHAR }	/* U+68F (0xDA8F) unchanged ;    */
	, { (unsigned char *)"\xDA\x90" , CFLAG_NORMALCHAR }	/* U+690 (0xDA90) unchanged ;    */
	, { (unsigned char *)"\xDA\x91" , CFLAG_NORMALCHAR }	/* U+691 (0xDA91) unchanged ;    */
	, { (unsigned char *)"\xDA\x92" , CFLAG_NORMALCHAR }	/* U+692 (0xDA92) unchanged ;    */
	, { (unsigned char *)"\xDA\x93" , CFLAG_NORMALCHAR }	/* U+693 (0xDA93) unchanged ;    */
	, { (unsigned char *)"\xDA\x94" , CFLAG_NORMALCHAR }	/* U+694 (0xDA94) unchanged ;    */
	, { (unsigned char *)"\xDA\x95" , CFLAG_NORMALCHAR }	/* U+695 (0xDA95) unchanged ;    */
	, { (unsigned char *)"\xDA\x96" , CFLAG_NORMALCHAR }	/* U+696 (0xDA96) unchanged ;    */
	, { (unsigned char *)"\xDA\x97" , CFLAG_NORMALCHAR }	/* U+697 (0xDA97) unchanged ;    */
	, { (unsigned char *)"\xDA\x98" , CFLAG_NORMALCHAR }	/* U+698 (0xDA98) unchanged ;    */
	, { (unsigned char *)"\xDA\x99" , CFLAG_NORMALCHAR }	/* U+699 (0xDA99) unchanged ;    */
	, { (unsigned char *)"\xDA\x9A" , CFLAG_NORMALCHAR }	/* U+69A (0xDA9A) unchanged ;    */
	, { (unsigned char *)"\xDA\x9B" , CFLAG_NORMALCHAR }	/* U+69B (0xDA9B) unchanged ;    */
	, { (unsigned char *)"\xDA\x9C" , CFLAG_NORMALCHAR }	/* U+69C (0xDA9C) unchanged ;    */
	, { (unsigned char *)"\xDA\x9D" , CFLAG_NORMALCHAR }	/* U+69D (0xDA9D) unchanged ;    */
	, { (unsigned char *)"\xDA\x9E" , CFLAG_NORMALCHAR }	/* U+69E (0xDA9E) unchanged ;    */
	, { (unsigned char *)"\xDA\x9F" , CFLAG_NORMALCHAR }	/* U+69F (0xDA9F) unchanged ;    */
	, { (unsigned char *)"\xDA\xA0" , CFLAG_NORMALCHAR }	/* U+6A0 (0xDAA0) unchanged ;    */
	, { (unsigned char *)"\xDA\xA1" , CFLAG_NORMALCHAR }	/* U+6A1 (0xDAA1) unchanged ;    */
	, { (unsigned char *)"\xDA\xA2" , CFLAG_NORMALCHAR }	/* U+6A2 (0xDAA2) unchanged ;    */
	, { (unsigned char *)"\xDA\xA3" , CFLAG_NORMALCHAR }	/* U+6A3 (0xDAA3) unchanged ;    */
	, { (unsigned char *)"\xDA\xA4" , CFLAG_NORMALCHAR }	/* U+6A4 (0xDAA4) unchanged ;    */
	, { (unsigned char *)"\xDA\xA5" , CFLAG_NORMALCHAR }	/* U+6A5 (0xDAA5) unchanged ;    */
	, { (unsigned char *)"\xDA\xA6" , CFLAG_NORMALCHAR }	/* U+6A6 (0xDAA6) unchanged ;    */
	, { (unsigned char *)"\xDA\xA7" , CFLAG_NORMALCHAR }	/* U+6A7 (0xDAA7) unchanged ;    */
	, { (unsigned char *)"\xDA\xA8" , CFLAG_NORMALCHAR }	/* U+6A8 (0xDAA8) unchanged ;    */
	, { (unsigned char *)"\xDA\xA9" , CFLAG_NORMALCHAR }	/* U+6A9 (0xDAA9) unchanged ;    */
	, { (unsigned char *)"\xDA\xAA" , CFLAG_NORMALCHAR }	/* U+6AA (0xDAAA) unchanged ;    */
	, { (unsigned char *)"\xDA\xAB" , CFLAG_NORMALCHAR }	/* U+6AB (0xDAAB) unchanged ;    */
	, { (unsigned char *)"\xDA\xAC" , CFLAG_NORMALCHAR }	/* U+6AC (0xDAAC) unchanged ;    */
	, { (unsigned char *)"\xDA\xAD" , CFLAG_NORMALCHAR }	/* U+6AD (0xDAAD) unchanged ;    */
	, { (unsigned char *)"\xDA\xAE" , CFLAG_NORMALCHAR }	/* U+6AE (0xDAAE) unchanged ;    */
	, { (unsigned char *)"\xDA\xAF" , CFLAG_NORMALCHAR }	/* U+6AF (0xDAAF) unchanged ;    */
	, { (unsigned char *)"\xDA\xB0" , CFLAG_NORMALCHAR }	/* U+6B0 (0xDAB0) unchanged ;    */
	, { (unsigned char *)"\xDA\xB1" , CFLAG_NORMALCHAR }	/* U+6B1 (0xDAB1) unchanged ;    */
	, { (unsigned char *)"\xDA\xB2" , CFLAG_NORMALCHAR }	/* U+6B2 (0xDAB2) unchanged ;    */
	, { (unsigned char *)"\xDA\xB3" , CFLAG_NORMALCHAR }	/* U+6B3 (0xDAB3) unchanged ;    */
	, { (unsigned char *)"\xDA\xB4" , CFLAG_NORMALCHAR }	/* U+6B4 (0xDAB4) unchanged ;    */
	, { (unsigned char *)"\xDA\xB5" , CFLAG_NORMALCHAR }	/* U+6B5 (0xDAB5) unchanged ;    */
	, { (unsigned char *)"\xDA\xB6" , CFLAG_NORMALCHAR }	/* U+6B6 (0xDAB6) unchanged ;    */
	, { (unsigned char *)"\xDA\xB7" , CFLAG_NORMALCHAR }	/* U+6B7 (0xDAB7) unchanged ;    */
	, { (unsigned char *)"\xDA\xB8" , CFLAG_NORMALCHAR }	/* U+6B8 (0xDAB8) unchanged ;    */
	, { (unsigned char *)"\xDA\xB9" , CFLAG_NORMALCHAR }	/* U+6B9 (0xDAB9) unchanged ;    */
	, { (unsigned char *)"\xDA\xBA" , CFLAG_NORMALCHAR }	/* U+6BA (0xDABA) unchanged ;    */
	, { (unsigned char *)"\xDA\xBB" , CFLAG_NORMALCHAR }	/* U+6BB (0xDABB) unchanged ;    */
	, { (unsigned char *)"\xDA\xBC" , CFLAG_NORMALCHAR }	/* U+6BC (0xDABC) unchanged ;    */
	, { (unsigned char *)"\xDA\xBD" , CFLAG_NORMALCHAR }	/* U+6BD (0xDABD) unchanged ;    */
	, { (unsigned char *)"\xDA\xBE" , CFLAG_NORMALCHAR }	/* U+6BE (0xDABE) unchanged ;    */
	, { (unsigned char *)"\xDA\xBF" , CFLAG_NORMALCHAR }	/* U+6BF (0xDABF) unchanged ;    */
	, { (unsigned char *)"\xDB\x80" , CFLAG_NORMALCHAR }	/* U+6C0 (0xDB80) unchanged ;    */
	, { (unsigned char *)"\xDB\x81" , CFLAG_NORMALCHAR }	/* U+6C1 (0xDB81) unchanged ;    */
	, { (unsigned char *)"\xDB\x82" , CFLAG_NORMALCHAR }	/* U+6C2 (0xDB82) unchanged ;    */
	, { (unsigned char *)"\xDB\x83" , CFLAG_NORMALCHAR }	/* U+6C3 (0xDB83) unchanged ;    */
	, { (unsigned char *)"\xDB\x84" , CFLAG_NORMALCHAR }	/* U+6C4 (0xDB84) unchanged ;    */
	, { (unsigned char *)"\xDB\x85" , CFLAG_NORMALCHAR }	/* U+6C5 (0xDB85) unchanged ;    */
	, { (unsigned char *)"\xDB\x86" , CFLAG_NORMALCHAR }	/* U+6C6 (0xDB86) unchanged ;    */
	, { (unsigned char *)"\xDB\x87" , CFLAG_NORMALCHAR }	/* U+6C7 (0xDB87) unchanged ;    */
	, { (unsigned char *)"\xDB\x88" , CFLAG_NORMALCHAR }	/* U+6C8 (0xDB88) unchanged ;    */
	, { (unsigned char *)"\xDB\x89" , CFLAG_NORMALCHAR }	/* U+6C9 (0xDB89) unchanged ;    */
	, { (unsigned char *)"\xDB\x8A" , CFLAG_NORMALCHAR }	/* U+6CA (0xDB8A) unchanged ;    */
	, { (unsigned char *)"\xDB\x8B" , CFLAG_NORMALCHAR }	/* U+6CB (0xDB8B) unchanged ;    */
	, { (unsigned char *)"\xDB\x8C" , CFLAG_NORMALCHAR }	/* U+6CC (0xDB8C) unchanged ;    */
	, { (unsigned char *)"\xDB\x8D" , CFLAG_NORMALCHAR }	/* U+6CD (0xDB8D) unchanged ;    */
	, { (unsigned char *)"\xDB\x8E" , CFLAG_NORMALCHAR }	/* U+6CE (0xDB8E) unchanged ;    */
	, { (unsigned char *)"\xDB\x8F" , CFLAG_NORMALCHAR }	/* U+6CF (0xDB8F) unchanged ;    */
	, { (unsigned char *)"\xDB\x90" , CFLAG_NORMALCHAR }	/* U+6D0 (0xDB90) unchanged ;    */
	, { (unsigned char *)"\xDB\x91" , CFLAG_NORMALCHAR }	/* U+6D1 (0xDB91) unchanged ;    */
	, { (unsigned char *)"\xDB\x92" , CFLAG_NORMALCHAR }	/* U+6D2 (0xDB92) unchanged ;    */
	, { (unsigned char *)"\xDB\x93" , CFLAG_NORMALCHAR }	/* U+6D3 (0xDB93) unchanged ;    */
	, { (unsigned char *)"\xDB\x94" , CFLAG_NORMALCHAR }	/* U+6D4 (0xDB94) unchanged ;    */
	, { (unsigned char *)"\xDB\x95" , CFLAG_NORMALCHAR }	/* U+6D5 (0xDB95) unchanged ;    */
	, { (unsigned char *)"\xDB\x96" , CFLAG_NORMALCHAR }	/* U+6D6 (0xDB96) unchanged ;    */
	, { (unsigned char *)"\xDB\x97" , CFLAG_NORMALCHAR }	/* U+6D7 (0xDB97) unchanged ;    */
	, { (unsigned char *)"\xDB\x98" , CFLAG_NORMALCHAR }	/* U+6D8 (0xDB98) unchanged ;    */
	, { (unsigned char *)"\xDB\x99" , CFLAG_NORMALCHAR }	/* U+6D9 (0xDB99) unchanged ;    */
	, { (unsigned char *)"\xDB\x9A" , CFLAG_NORMALCHAR }	/* U+6DA (0xDB9A) unchanged ;    */
	, { (unsigned char *)"\xDB\x9B" , CFLAG_NORMALCHAR }	/* U+6DB (0xDB9B) unchanged ;    */
	, { (unsigned char *)"\xDB\x9C" , CFLAG_NORMALCHAR }	/* U+6DC (0xDB9C) unchanged ;    */
	, { (unsigned char *)"\xDB\x9D" , CFLAG_NORMALCHAR }	/* U+6DD (0xDB9D) unchanged ;    */
	, { (unsigned char *)"\xDB\x9E" , CFLAG_NORMALCHAR }	/* U+6DE (0xDB9E) unchanged ;    */
	, { (unsigned char *)"\xDB\x9F" , CFLAG_NORMALCHAR }	/* U+6DF (0xDB9F) unchanged ;    */
	, { (unsigned char *)"\xDB\xA0" , CFLAG_NORMALCHAR }	/* U+6E0 (0xDBA0) unchanged ;    */
	, { (unsigned char *)"\xDB\xA1" , CFLAG_NORMALCHAR }	/* U+6E1 (0xDBA1) unchanged ;    */
	, { (unsigned char *)"\xDB\xA2" , CFLAG_NORMALCHAR }	/* U+6E2 (0xDBA2) unchanged ;    */
	, { (unsigned char *)"\xDB\xA3" , CFLAG_NORMALCHAR }	/* U+6E3 (0xDBA3) unchanged ;    */
	, { (unsigned char *)"\xDB\xA4" , CFLAG_NORMALCHAR }	/* U+6E4 (0xDBA4) unchanged ;    */
	, { (unsigned char *)"\xDB\xA5" , CFLAG_NORMALCHAR }	/* U+6E5 (0xDBA5) unchanged ;    */
	, { (unsigned char *)"\xDB\xA6" , CFLAG_NORMALCHAR }	/* U+6E6 (0xDBA6) unchanged ;    */
	, { (unsigned char *)"\xDB\xA7" , CFLAG_NORMALCHAR }	/* U+6E7 (0xDBA7) unchanged ;    */
	, { (unsigned char *)"\xDB\xA8" , CFLAG_NORMALCHAR }	/* U+6E8 (0xDBA8) unchanged ;    */
	, { (unsigned char *)"\xDB\xA9" , CFLAG_NORMALCHAR }	/* U+6E9 (0xDBA9) unchanged ;    */
	, { (unsigned char *)"\xDB\xAA" , CFLAG_NORMALCHAR }	/* U+6EA (0xDBAA) unchanged ;    */
	, { (unsigned char *)"\xDB\xAB" , CFLAG_NORMALCHAR }	/* U+6EB (0xDBAB) unchanged ;    */
	, { (unsigned char *)"\xDB\xAC" , CFLAG_NORMALCHAR }	/* U+6EC (0xDBAC) unchanged ;    */
	, { (unsigned char *)"\xDB\xAD" , CFLAG_NORMALCHAR }	/* U+6ED (0xDBAD) unchanged ;    */
	, { (unsigned char *)"\xDB\xAE" , CFLAG_NORMALCHAR }	/* U+6EE (0xDBAE) unchanged ;    */
	, { (unsigned char *)"\xDB\xAF" , CFLAG_NORMALCHAR }	/* U+6EF (0xDBAF) unchanged ;    */
	, { (unsigned char *)"\xDB\xB0" , CFLAG_NORMALCHAR }	/* U+6F0 (0xDBB0) unchanged ;    */
	, { (unsigned char *)"\xDB\xB1" , CFLAG_NORMALCHAR }	/* U+6F1 (0xDBB1) unchanged ;    */
	, { (unsigned char *)"\xDB\xB2" , CFLAG_NORMALCHAR }	/* U+6F2 (0xDBB2) unchanged ;    */
	, { (unsigned char *)"\xDB\xB3" , CFLAG_NORMALCHAR }	/* U+6F3 (0xDBB3) unchanged ;    */
	, { (unsigned char *)"\xDB\xB4" , CFLAG_NORMALCHAR }	/* U+6F4 (0xDBB4) unchanged ;    */
	, { (unsigned char *)"\xDB\xB5" , CFLAG_NORMALCHAR }	/* U+6F5 (0xDBB5) unchanged ;    */
	, { (unsigned char *)"\xDB\xB6" , CFLAG_NORMALCHAR }	/* U+6F6 (0xDBB6) unchanged ;    */
	, { (unsigned char *)"\xDB\xB7" , CFLAG_NORMALCHAR }	/* U+6F7 (0xDBB7) unchanged ;    */
	, { (unsigned char *)"\xDB\xB8" , CFLAG_NORMALCHAR }	/* U+6F8 (0xDBB8) unchanged ;    */
	, { (unsigned char *)"\xDB\xB9" , CFLAG_NORMALCHAR }	/* U+6F9 (0xDBB9) unchanged ;    */
	, { (unsigned char *)"\xDB\xBA" , CFLAG_NORMALCHAR }	/* U+6FA (0xDBBA) unchanged ;    */
	, { (unsigned char *)"\xDB\xBB" , CFLAG_NORMALCHAR }	/* U+6FB (0xDBBB) unchanged ;    */
	, { (unsigned char *)"\xDB\xBC" , CFLAG_NORMALCHAR }	/* U+6FC (0xDBBC) unchanged ;    */
	, { (unsigned char *)"\xDB\xBD" , CFLAG_NORMALCHAR }	/* U+6FD (0xDBBD) unchanged ;    */
	, { (unsigned char *)"\xDB\xBE" , CFLAG_NORMALCHAR }	/* U+6FE (0xDBBE) unchanged ;    */
	, { (unsigned char *)"\xDB\xBF" , CFLAG_NORMALCHAR }	/* U+6FF (0xDBBF) unchanged ;    */
	, { (unsigned char *)"\xDC\x80" , CFLAG_NORMALCHAR }	/* U+700 (0xDC80) unchanged ;    */
	, { (unsigned char *)"\xDC\x81" , CFLAG_NORMALCHAR }	/* U+701 (0xDC81) unchanged ;    */
	, { (unsigned char *)"\xDC\x82" , CFLAG_NORMALCHAR }	/* U+702 (0xDC82) unchanged ;    */
	, { (unsigned char *)"\xDC\x83" , CFLAG_NORMALCHAR }	/* U+703 (0xDC83) unchanged ;    */
	, { (unsigned char *)"\xDC\x84" , CFLAG_NORMALCHAR }	/* U+704 (0xDC84) unchanged ;    */
	, { (unsigned char *)"\xDC\x85" , CFLAG_NORMALCHAR }	/* U+705 (0xDC85) unchanged ;    */
	, { (unsigned char *)"\xDC\x86" , CFLAG_NORMALCHAR }	/* U+706 (0xDC86) unchanged ;    */
	, { (unsigned char *)"\xDC\x87" , CFLAG_NORMALCHAR }	/* U+707 (0xDC87) unchanged ;    */
	, { (unsigned char *)"\xDC\x88" , CFLAG_NORMALCHAR }	/* U+708 (0xDC88) unchanged ;    */
	, { (unsigned char *)"\xDC\x89" , CFLAG_NORMALCHAR }	/* U+709 (0xDC89) unchanged ;    */
	, { (unsigned char *)"\xDC\x8A" , CFLAG_NORMALCHAR }	/* U+70A (0xDC8A) unchanged ;    */
	, { (unsigned char *)"\xDC\x8B" , CFLAG_NORMALCHAR }	/* U+70B (0xDC8B) unchanged ;    */
	, { (unsigned char *)"\xDC\x8C" , CFLAG_NORMALCHAR }	/* U+70C (0xDC8C) unchanged ;    */
	, { (unsigned char *)"\xDC\x8D" , CFLAG_NORMALCHAR }	/* U+70D (0xDC8D) unchanged ;    */
	, { (unsigned char *)"\xDC\x8E" , CFLAG_NORMALCHAR }	/* U+70E (0xDC8E) unchanged ;    */
	, { (unsigned char *)"\xDC\x8F" , CFLAG_NORMALCHAR }	/* U+70F (0xDC8F) unchanged ;    */
	, { (unsigned char *)"\xDC\x90" , CFLAG_NORMALCHAR }	/* U+710 (0xDC90) unchanged ;    */
	, { (unsigned char *)"\xDC\x91" , CFLAG_NORMALCHAR }	/* U+711 (0xDC91) unchanged ;    */
	, { (unsigned char *)"\xDC\x92" , CFLAG_NORMALCHAR }	/* U+712 (0xDC92) unchanged ;    */
	, { (unsigned char *)"\xDC\x93" , CFLAG_NORMALCHAR }	/* U+713 (0xDC93) unchanged ;    */
	, { (unsigned char *)"\xDC\x94" , CFLAG_NORMALCHAR }	/* U+714 (0xDC94) unchanged ;    */
	, { (unsigned char *)"\xDC\x95" , CFLAG_NORMALCHAR }	/* U+715 (0xDC95) unchanged ;    */
	, { (unsigned char *)"\xDC\x96" , CFLAG_NORMALCHAR }	/* U+716 (0xDC96) unchanged ;    */
	, { (unsigned char *)"\xDC\x97" , CFLAG_NORMALCHAR }	/* U+717 (0xDC97) unchanged ;    */
	, { (unsigned char *)"\xDC\x98" , CFLAG_NORMALCHAR }	/* U+718 (0xDC98) unchanged ;    */
	, { (unsigned char *)"\xDC\x99" , CFLAG_NORMALCHAR }	/* U+719 (0xDC99) unchanged ;    */
	, { (unsigned char *)"\xDC\x9A" , CFLAG_NORMALCHAR }	/* U+71A (0xDC9A) unchanged ;    */
	, { (unsigned char *)"\xDC\x9B" , CFLAG_NORMALCHAR }	/* U+71B (0xDC9B) unchanged ;    */
	, { (unsigned char *)"\xDC\x9C" , CFLAG_NORMALCHAR }	/* U+71C (0xDC9C) unchanged ;    */
	, { (unsigned char *)"\xDC\x9D" , CFLAG_NORMALCHAR }	/* U+71D (0xDC9D) unchanged ;    */
	, { (unsigned char *)"\xDC\x9E" , CFLAG_NORMALCHAR }	/* U+71E (0xDC9E) unchanged ;    */
	, { (unsigned char *)"\xDC\x9F" , CFLAG_NORMALCHAR }	/* U+71F (0xDC9F) unchanged ;    */
	, { (unsigned char *)"\xDC\xA0" , CFLAG_NORMALCHAR }	/* U+720 (0xDCA0) unchanged ;    */
	, { (unsigned char *)"\xDC\xA1" , CFLAG_NORMALCHAR }	/* U+721 (0xDCA1) unchanged ;    */
	, { (unsigned char *)"\xDC\xA2" , CFLAG_NORMALCHAR }	/* U+722 (0xDCA2) unchanged ;    */
	, { (unsigned char *)"\xDC\xA3" , CFLAG_NORMALCHAR }	/* U+723 (0xDCA3) unchanged ;    */
	, { (unsigned char *)"\xDC\xA4" , CFLAG_NORMALCHAR }	/* U+724 (0xDCA4) unchanged ;    */
	, { (unsigned char *)"\xDC\xA5" , CFLAG_NORMALCHAR }	/* U+725 (0xDCA5) unchanged ;    */
	, { (unsigned char *)"\xDC\xA6" , CFLAG_NORMALCHAR }	/* U+726 (0xDCA6) unchanged ;    */
	, { (unsigned char *)"\xDC\xA7" , CFLAG_NORMALCHAR }	/* U+727 (0xDCA7) unchanged ;    */
	, { (unsigned char *)"\xDC\xA8" , CFLAG_NORMALCHAR }	/* U+728 (0xDCA8) unchanged ;    */
	, { (unsigned char *)"\xDC\xA9" , CFLAG_NORMALCHAR }	/* U+729 (0xDCA9) unchanged ;    */
	, { (unsigned char *)"\xDC\xAA" , CFLAG_NORMALCHAR }	/* U+72A (0xDCAA) unchanged ;    */
	, { (unsigned char *)"\xDC\xAB" , CFLAG_NORMALCHAR }	/* U+72B (0xDCAB) unchanged ;    */
	, { (unsigned char *)"\xDC\xAC" , CFLAG_NORMALCHAR }	/* U+72C (0xDCAC) unchanged ;    */
	, { (unsigned char *)"\xDC\xAD" , CFLAG_NORMALCHAR }	/* U+72D (0xDCAD) unchanged ;    */
	, { (unsigned char *)"\xDC\xAE" , CFLAG_NORMALCHAR }	/* U+72E (0xDCAE) unchanged ;    */
	, { (unsigned char *)"\xDC\xAF" , CFLAG_NORMALCHAR }	/* U+72F (0xDCAF) unchanged ;    */
	, { (unsigned char *)"\xDC\xB0" , CFLAG_NORMALCHAR }	/* U+730 (0xDCB0) unchanged ;    */
	, { (unsigned char *)"\xDC\xB1" , CFLAG_NORMALCHAR }	/* U+731 (0xDCB1) unchanged ;    */
	, { (unsigned char *)"\xDC\xB2" , CFLAG_NORMALCHAR }	/* U+732 (0xDCB2) unchanged ;    */
	, { (unsigned char *)"\xDC\xB3" , CFLAG_NORMALCHAR }	/* U+733 (0xDCB3) unchanged ;    */
	, { (unsigned char *)"\xDC\xB4" , CFLAG_NORMALCHAR }	/* U+734 (0xDCB4) unchanged ;    */
	, { (unsigned char *)"\xDC\xB5" , CFLAG_NORMALCHAR }	/* U+735 (0xDCB5) unchanged ;    */
	, { (unsigned char *)"\xDC\xB6" , CFLAG_NORMALCHAR }	/* U+736 (0xDCB6) unchanged ;    */
	, { (unsigned char *)"\xDC\xB7" , CFLAG_NORMALCHAR }	/* U+737 (0xDCB7) unchanged ;    */
	, { (unsigned char *)"\xDC\xB8" , CFLAG_NORMALCHAR }	/* U+738 (0xDCB8) unchanged ;    */
	, { (unsigned char *)"\xDC\xB9" , CFLAG_NORMALCHAR }	/* U+739 (0xDCB9) unchanged ;    */
	, { (unsigned char *)"\xDC\xBA" , CFLAG_NORMALCHAR }	/* U+73A (0xDCBA) unchanged ;    */
	, { (unsigned char *)"\xDC\xBB" , CFLAG_NORMALCHAR }	/* U+73B (0xDCBB) unchanged ;    */
	, { (unsigned char *)"\xDC\xBC" , CFLAG_NORMALCHAR }	/* U+73C (0xDCBC) unchanged ;    */
	, { (unsigned char *)"\xDC\xBD" , CFLAG_NORMALCHAR }	/* U+73D (0xDCBD) unchanged ;    */
	, { (unsigned char *)"\xDC\xBE" , CFLAG_NORMALCHAR }	/* U+73E (0xDCBE) unchanged ;    */
	, { (unsigned char *)"\xDC\xBF" , CFLAG_NORMALCHAR }	/* U+73F (0xDCBF) unchanged ;    */
	, { (unsigned char *)"\xDD\x80" , CFLAG_NORMALCHAR }	/* U+740 (0xDD80) unchanged ;    */
	, { (unsigned char *)"\xDD\x81" , CFLAG_NORMALCHAR }	/* U+741 (0xDD81) unchanged ;    */
	, { (unsigned char *)"\xDD\x82" , CFLAG_NORMALCHAR }	/* U+742 (0xDD82) unchanged ;    */
	, { (unsigned char *)"\xDD\x83" , CFLAG_NORMALCHAR }	/* U+743 (0xDD83) unchanged ;    */
	, { (unsigned char *)"\xDD\x84" , CFLAG_NORMALCHAR }	/* U+744 (0xDD84) unchanged ;    */
	, { (unsigned char *)"\xDD\x85" , CFLAG_NORMALCHAR }	/* U+745 (0xDD85) unchanged ;    */
	, { (unsigned char *)"\xDD\x86" , CFLAG_NORMALCHAR }	/* U+746 (0xDD86) unchanged ;    */
	, { (unsigned char *)"\xDD\x87" , CFLAG_NORMALCHAR }	/* U+747 (0xDD87) unchanged ;    */
	, { (unsigned char *)"\xDD\x88" , CFLAG_NORMALCHAR }	/* U+748 (0xDD88) unchanged ;    */
	, { (unsigned char *)"\xDD\x89" , CFLAG_NORMALCHAR }	/* U+749 (0xDD89) unchanged ;    */
	, { (unsigned char *)"\xDD\x8A" , CFLAG_NORMALCHAR }	/* U+74A (0xDD8A) unchanged ;    */
	, { (unsigned char *)"\xDD\x8B" , CFLAG_NORMALCHAR }	/* U+74B (0xDD8B) unchanged ;    */
	, { (unsigned char *)"\xDD\x8C" , CFLAG_NORMALCHAR }	/* U+74C (0xDD8C) unchanged ;    */
	, { (unsigned char *)"\xDD\x8D" , CFLAG_NORMALCHAR }	/* U+74D (0xDD8D) unchanged ;    */
	, { (unsigned char *)"\xDD\x8E" , CFLAG_NORMALCHAR }	/* U+74E (0xDD8E) unchanged ;    */
	, { (unsigned char *)"\xDD\x8F" , CFLAG_NORMALCHAR }	/* U+74F (0xDD8F) unchanged ;    */
	, { (unsigned char *)"\xDD\x90" , CFLAG_NORMALCHAR }	/* U+750 (0xDD90) unchanged ;    */
	, { (unsigned char *)"\xDD\x91" , CFLAG_NORMALCHAR }	/* U+751 (0xDD91) unchanged ;    */
	, { (unsigned char *)"\xDD\x92" , CFLAG_NORMALCHAR }	/* U+752 (0xDD92) unchanged ;    */
	, { (unsigned char *)"\xDD\x93" , CFLAG_NORMALCHAR }	/* U+753 (0xDD93) unchanged ;    */
	, { (unsigned char *)"\xDD\x94" , CFLAG_NORMALCHAR }	/* U+754 (0xDD94) unchanged ;    */
	, { (unsigned char *)"\xDD\x95" , CFLAG_NORMALCHAR }	/* U+755 (0xDD95) unchanged ;    */
	, { (unsigned char *)"\xDD\x96" , CFLAG_NORMALCHAR }	/* U+756 (0xDD96) unchanged ;    */
	, { (unsigned char *)"\xDD\x97" , CFLAG_NORMALCHAR }	/* U+757 (0xDD97) unchanged ;    */
	, { (unsigned char *)"\xDD\x98" , CFLAG_NORMALCHAR }	/* U+758 (0xDD98) unchanged ;    */
	, { (unsigned char *)"\xDD\x99" , CFLAG_NORMALCHAR }	/* U+759 (0xDD99) unchanged ;    */
	, { (unsigned char *)"\xDD\x9A" , CFLAG_NORMALCHAR }	/* U+75A (0xDD9A) unchanged ;    */
	, { (unsigned char *)"\xDD\x9B" , CFLAG_NORMALCHAR }	/* U+75B (0xDD9B) unchanged ;    */
	, { (unsigned char *)"\xDD\x9C" , CFLAG_NORMALCHAR }	/* U+75C (0xDD9C) unchanged ;    */
	, { (unsigned char *)"\xDD\x9D" , CFLAG_NORMALCHAR }	/* U+75D (0xDD9D) unchanged ;    */
	, { (unsigned char *)"\xDD\x9E" , CFLAG_NORMALCHAR }	/* U+75E (0xDD9E) unchanged ;    */
	, { (unsigned char *)"\xDD\x9F" , CFLAG_NORMALCHAR }	/* U+75F (0xDD9F) unchanged ;    */
	, { (unsigned char *)"\xDD\xA0" , CFLAG_NORMALCHAR }	/* U+760 (0xDDA0) unchanged ;    */
	, { (unsigned char *)"\xDD\xA1" , CFLAG_NORMALCHAR }	/* U+761 (0xDDA1) unchanged ;    */
	, { (unsigned char *)"\xDD\xA2" , CFLAG_NORMALCHAR }	/* U+762 (0xDDA2) unchanged ;    */
	, { (unsigned char *)"\xDD\xA3" , CFLAG_NORMALCHAR }	/* U+763 (0xDDA3) unchanged ;    */
	, { (unsigned char *)"\xDD\xA4" , CFLAG_NORMALCHAR }	/* U+764 (0xDDA4) unchanged ;    */
	, { (unsigned char *)"\xDD\xA5" , CFLAG_NORMALCHAR }	/* U+765 (0xDDA5) unchanged ;    */
	, { (unsigned char *)"\xDD\xA6" , CFLAG_NORMALCHAR }	/* U+766 (0xDDA6) unchanged ;    */
	, { (unsigned char *)"\xDD\xA7" , CFLAG_NORMALCHAR }	/* U+767 (0xDDA7) unchanged ;    */
	, { (unsigned char *)"\xDD\xA8" , CFLAG_NORMALCHAR }	/* U+768 (0xDDA8) unchanged ;    */
	, { (unsigned char *)"\xDD\xA9" , CFLAG_NORMALCHAR }	/* U+769 (0xDDA9) unchanged ;    */
	, { (unsigned char *)"\xDD\xAA" , CFLAG_NORMALCHAR }	/* U+76A (0xDDAA) unchanged ;    */
	, { (unsigned char *)"\xDD\xAB" , CFLAG_NORMALCHAR }	/* U+76B (0xDDAB) unchanged ;    */
	, { (unsigned char *)"\xDD\xAC" , CFLAG_NORMALCHAR }	/* U+76C (0xDDAC) unchanged ;    */
	, { (unsigned char *)"\xDD\xAD" , CFLAG_NORMALCHAR }	/* U+76D (0xDDAD) unchanged ;    */
	, { (unsigned char *)"\xDD\xAE" , CFLAG_NORMALCHAR }	/* U+76E (0xDDAE) unchanged ;    */
	, { (unsigned char *)"\xDD\xAF" , CFLAG_NORMALCHAR }	/* U+76F (0xDDAF) unchanged ;    */
	, { (unsigned char *)"\xDD\xB0" , CFLAG_NORMALCHAR }	/* U+770 (0xDDB0) unchanged ;    */
	, { (unsigned char *)"\xDD\xB1" , CFLAG_NORMALCHAR }	/* U+771 (0xDDB1) unchanged ;    */
	, { (unsigned char *)"\xDD\xB2" , CFLAG_NORMALCHAR }	/* U+772 (0xDDB2) unchanged ;    */
	, { (unsigned char *)"\xDD\xB3" , CFLAG_NORMALCHAR }	/* U+773 (0xDDB3) unchanged ;    */
	, { (unsigned char *)"\xDD\xB4" , CFLAG_NORMALCHAR }	/* U+774 (0xDDB4) unchanged ;    */
	, { (unsigned char *)"\xDD\xB5" , CFLAG_NORMALCHAR }	/* U+775 (0xDDB5) unchanged ;    */
	, { (unsigned char *)"\xDD\xB6" , CFLAG_NORMALCHAR }	/* U+776 (0xDDB6) unchanged ;    */
	, { (unsigned char *)"\xDD\xB7" , CFLAG_NORMALCHAR }	/* U+777 (0xDDB7) unchanged ;    */
	, { (unsigned char *)"\xDD\xB8" , CFLAG_NORMALCHAR }	/* U+778 (0xDDB8) unchanged ;    */
	, { (unsigned char *)"\xDD\xB9" , CFLAG_NORMALCHAR }	/* U+779 (0xDDB9) unchanged ;    */
	, { (unsigned char *)"\xDD\xBA" , CFLAG_NORMALCHAR }	/* U+77A (0xDDBA) unchanged ;    */
	, { (unsigned char *)"\xDD\xBB" , CFLAG_NORMALCHAR }	/* U+77B (0xDDBB) unchanged ;    */
	, { (unsigned char *)"\xDD\xBC" , CFLAG_NORMALCHAR }	/* U+77C (0xDDBC) unchanged ;    */
	, { (unsigned char *)"\xDD\xBD" , CFLAG_NORMALCHAR }	/* U+77D (0xDDBD) unchanged ;    */
	, { (unsigned char *)"\xDD\xBE" , CFLAG_NORMALCHAR }	/* U+77E (0xDDBE) unchanged ;    */
	, { (unsigned char *)"\xDD\xBF" , CFLAG_NORMALCHAR }	/* U+77F (0xDDBF) unchanged ;    */
	, { (unsigned char *)"\xDE\x80" , CFLAG_NORMALCHAR }	/* U+780 (0xDE80) unchanged ;    */
	, { (unsigned char *)"\xDE\x81" , CFLAG_NORMALCHAR }	/* U+781 (0xDE81) unchanged ;    */
	, { (unsigned char *)"\xDE\x82" , CFLAG_NORMALCHAR }	/* U+782 (0xDE82) unchanged ;    */
	, { (unsigned char *)"\xDE\x83" , CFLAG_NORMALCHAR }	/* U+783 (0xDE83) unchanged ;    */
	, { (unsigned char *)"\xDE\x84" , CFLAG_NORMALCHAR }	/* U+784 (0xDE84) unchanged ;    */
	, { (unsigned char *)"\xDE\x85" , CFLAG_NORMALCHAR }	/* U+785 (0xDE85) unchanged ;    */
	, { (unsigned char *)"\xDE\x86" , CFLAG_NORMALCHAR }	/* U+786 (0xDE86) unchanged ;    */
	, { (unsigned char *)"\xDE\x87" , CFLAG_NORMALCHAR }	/* U+787 (0xDE87) unchanged ;    */
	, { (unsigned char *)"\xDE\x88" , CFLAG_NORMALCHAR }	/* U+788 (0xDE88) unchanged ;    */
	, { (unsigned char *)"\xDE\x89" , CFLAG_NORMALCHAR }	/* U+789 (0xDE89) unchanged ;    */
	, { (unsigned char *)"\xDE\x8A" , CFLAG_NORMALCHAR }	/* U+78A (0xDE8A) unchanged ;    */
	, { (unsigned char *)"\xDE\x8B" , CFLAG_NORMALCHAR }	/* U+78B (0xDE8B) unchanged ;    */
	, { (unsigned char *)"\xDE\x8C" , CFLAG_NORMALCHAR }	/* U+78C (0xDE8C) unchanged ;    */
	, { (unsigned char *)"\xDE\x8D" , CFLAG_NORMALCHAR }	/* U+78D (0xDE8D) unchanged ;    */
	, { (unsigned char *)"\xDE\x8E" , CFLAG_NORMALCHAR }	/* U+78E (0xDE8E) unchanged ;    */
	, { (unsigned char *)"\xDE\x8F" , CFLAG_NORMALCHAR }	/* U+78F (0xDE8F) unchanged ;    */
	, { (unsigned char *)"\xDE\x90" , CFLAG_NORMALCHAR }	/* U+790 (0xDE90) unchanged ;    */
	, { (unsigned char *)"\xDE\x91" , CFLAG_NORMALCHAR }	/* U+791 (0xDE91) unchanged ;    */
	, { (unsigned char *)"\xDE\x92" , CFLAG_NORMALCHAR }	/* U+792 (0xDE92) unchanged ;    */
	, { (unsigned char *)"\xDE\x93" , CFLAG_NORMALCHAR }	/* U+793 (0xDE93) unchanged ;    */
	, { (unsigned char *)"\xDE\x94" , CFLAG_NORMALCHAR }	/* U+794 (0xDE94) unchanged ;    */
	, { (unsigned char *)"\xDE\x95" , CFLAG_NORMALCHAR }	/* U+795 (0xDE95) unchanged ;    */
	, { (unsigned char *)"\xDE\x96" , CFLAG_NORMALCHAR }	/* U+796 (0xDE96) unchanged ;    */
	, { (unsigned char *)"\xDE\x97" , CFLAG_NORMALCHAR }	/* U+797 (0xDE97) unchanged ;    */
	, { (unsigned char *)"\xDE\x98" , CFLAG_NORMALCHAR }	/* U+798 (0xDE98) unchanged ;    */
	, { (unsigned char *)"\xDE\x99" , CFLAG_NORMALCHAR }	/* U+799 (0xDE99) unchanged ;    */
	, { (unsigned char *)"\xDE\x9A" , CFLAG_NORMALCHAR }	/* U+79A (0xDE9A) unchanged ;    */
	, { (unsigned char *)"\xDE\x9B" , CFLAG_NORMALCHAR }	/* U+79B (0xDE9B) unchanged ;    */
	, { (unsigned char *)"\xDE\x9C" , CFLAG_NORMALCHAR }	/* U+79C (0xDE9C) unchanged ;    */
	, { (unsigned char *)"\xDE\x9D" , CFLAG_NORMALCHAR }	/* U+79D (0xDE9D) unchanged ;    */
	, { (unsigned char *)"\xDE\x9E" , CFLAG_NORMALCHAR }	/* U+79E (0xDE9E) unchanged ;    */
	, { (unsigned char *)"\xDE\x9F" , CFLAG_NORMALCHAR }	/* U+79F (0xDE9F) unchanged ;    */
	, { (unsigned char *)"\xDE\xA0" , CFLAG_NORMALCHAR }	/* U+7A0 (0xDEA0) unchanged ;    */
	, { (unsigned char *)"\xDE\xA1" , CFLAG_NORMALCHAR }	/* U+7A1 (0xDEA1) unchanged ;    */
	, { (unsigned char *)"\xDE\xA2" , CFLAG_NORMALCHAR }	/* U+7A2 (0xDEA2) unchanged ;    */
	, { (unsigned char *)"\xDE\xA3" , CFLAG_NORMALCHAR }	/* U+7A3 (0xDEA3) unchanged ;    */
	, { (unsigned char *)"\xDE\xA4" , CFLAG_NORMALCHAR }	/* U+7A4 (0xDEA4) unchanged ;    */
	, { (unsigned char *)"\xDE\xA5" , CFLAG_NORMALCHAR }	/* U+7A5 (0xDEA5) unchanged ;    */
	, { (unsigned char *)"\xDE\xA6" , CFLAG_NORMALCHAR }	/* U+7A6 (0xDEA6) unchanged ;    */
	, { (unsigned char *)"\xDE\xA7" , CFLAG_NORMALCHAR }	/* U+7A7 (0xDEA7) unchanged ;    */
	, { (unsigned char *)"\xDE\xA8" , CFLAG_NORMALCHAR }	/* U+7A8 (0xDEA8) unchanged ;    */
	, { (unsigned char *)"\xDE\xA9" , CFLAG_NORMALCHAR }	/* U+7A9 (0xDEA9) unchanged ;    */
	, { (unsigned char *)"\xDE\xAA" , CFLAG_NORMALCHAR }	/* U+7AA (0xDEAA) unchanged ;    */
	, { (unsigned char *)"\xDE\xAB" , CFLAG_NORMALCHAR }	/* U+7AB (0xDEAB) unchanged ;    */
	, { (unsigned char *)"\xDE\xAC" , CFLAG_NORMALCHAR }	/* U+7AC (0xDEAC) unchanged ;    */
	, { (unsigned char *)"\xDE\xAD" , CFLAG_NORMALCHAR }	/* U+7AD (0xDEAD) unchanged ;    */
	, { (unsigned char *)"\xDE\xAE" , CFLAG_NORMALCHAR }	/* U+7AE (0xDEAE) unchanged ;    */
	, { (unsigned char *)"\xDE\xAF" , CFLAG_NORMALCHAR }	/* U+7AF (0xDEAF) unchanged ;    */
	, { (unsigned char *)"\xDE\xB0" , CFLAG_NORMALCHAR }	/* U+7B0 (0xDEB0) unchanged ;    */
	, { (unsigned char *)"\xDE\xB1" , CFLAG_NORMALCHAR }	/* U+7B1 (0xDEB1) unchanged ;    */
	, { (unsigned char *)"\xDE\xB2" , CFLAG_NORMALCHAR }	/* U+7B2 (0xDEB2) unchanged ;    */
	, { (unsigned char *)"\xDE\xB3" , CFLAG_NORMALCHAR }	/* U+7B3 (0xDEB3) unchanged ;    */
	, { (unsigned char *)"\xDE\xB4" , CFLAG_NORMALCHAR }	/* U+7B4 (0xDEB4) unchanged ;    */
	, { (unsigned char *)"\xDE\xB5" , CFLAG_NORMALCHAR }	/* U+7B5 (0xDEB5) unchanged ;    */
	, { (unsigned char *)"\xDE\xB6" , CFLAG_NORMALCHAR }	/* U+7B6 (0xDEB6) unchanged ;    */
	, { (unsigned char *)"\xDE\xB7" , CFLAG_NORMALCHAR }	/* U+7B7 (0xDEB7) unchanged ;    */
	, { (unsigned char *)"\xDE\xB8" , CFLAG_NORMALCHAR }	/* U+7B8 (0xDEB8) unchanged ;    */
	, { (unsigned char *)"\xDE\xB9" , CFLAG_NORMALCHAR }	/* U+7B9 (0xDEB9) unchanged ;    */
	, { (unsigned char *)"\xDE\xBA" , CFLAG_NORMALCHAR }	/* U+7BA (0xDEBA) unchanged ;    */
	, { (unsigned char *)"\xDE\xBB" , CFLAG_NORMALCHAR }	/* U+7BB (0xDEBB) unchanged ;    */
	, { (unsigned char *)"\xDE\xBC" , CFLAG_NORMALCHAR }	/* U+7BC (0xDEBC) unchanged ;    */
	, { (unsigned char *)"\xDE\xBD" , CFLAG_NORMALCHAR }	/* U+7BD (0xDEBD) unchanged ;    */
	, { (unsigned char *)"\xDE\xBE" , CFLAG_NORMALCHAR }	/* U+7BE (0xDEBE) unchanged ;    */
	, { (unsigned char *)"\xDE\xBF" , CFLAG_NORMALCHAR }	/* U+7BF (0xDEBF) unchanged ;    */
	, { (unsigned char *)"\xDF\x80" , CFLAG_NORMALCHAR }	/* U+7C0 (0xDF80) unchanged ;    */
	, { (unsigned char *)"\xDF\x81" , CFLAG_NORMALCHAR }	/* U+7C1 (0xDF81) unchanged ;    */
	, { (unsigned char *)"\xDF\x82" , CFLAG_NORMALCHAR }	/* U+7C2 (0xDF82) unchanged ;    */
	, { (unsigned char *)"\xDF\x83" , CFLAG_NORMALCHAR }	/* U+7C3 (0xDF83) unchanged ;    */
	, { (unsigned char *)"\xDF\x84" , CFLAG_NORMALCHAR }	/* U+7C4 (0xDF84) unchanged ;    */
	, { (unsigned char *)"\xDF\x85" , CFLAG_NORMALCHAR }	/* U+7C5 (0xDF85) unchanged ;    */
	, { (unsigned char *)"\xDF\x86" , CFLAG_NORMALCHAR }	/* U+7C6 (0xDF86) unchanged ;    */
	, { (unsigned char *)"\xDF\x87" , CFLAG_NORMALCHAR }	/* U+7C7 (0xDF87) unchanged ;    */
	, { (unsigned char *)"\xDF\x88" , CFLAG_NORMALCHAR }	/* U+7C8 (0xDF88) unchanged ;    */
	, { (unsigned char *)"\xDF\x89" , CFLAG_NORMALCHAR }	/* U+7C9 (0xDF89) unchanged ;    */
	, { (unsigned char *)"\xDF\x8A" , CFLAG_NORMALCHAR }	/* U+7CA (0xDF8A) unchanged ;    */
	, { (unsigned char *)"\xDF\x8B" , CFLAG_NORMALCHAR }	/* U+7CB (0xDF8B) unchanged ;    */
	, { (unsigned char *)"\xDF\x8C" , CFLAG_NORMALCHAR }	/* U+7CC (0xDF8C) unchanged ;    */
	, { (unsigned char *)"\xDF\x8D" , CFLAG_NORMALCHAR }	/* U+7CD (0xDF8D) unchanged ;    */
	, { (unsigned char *)"\xDF\x8E" , CFLAG_NORMALCHAR }	/* U+7CE (0xDF8E) unchanged ;    */
	, { (unsigned char *)"\xDF\x8F" , CFLAG_NORMALCHAR }	/* U+7CF (0xDF8F) unchanged ;    */
	, { (unsigned char *)"\xDF\x90" , CFLAG_NORMALCHAR }	/* U+7D0 (0xDF90) unchanged ;    */
	, { (unsigned char *)"\xDF\x91" , CFLAG_NORMALCHAR }	/* U+7D1 (0xDF91) unchanged ;    */
	, { (unsigned char *)"\xDF\x92" , CFLAG_NORMALCHAR }	/* U+7D2 (0xDF92) unchanged ;    */
	, { (unsigned char *)"\xDF\x93" , CFLAG_NORMALCHAR }	/* U+7D3 (0xDF93) unchanged ;    */
	, { (unsigned char *)"\xDF\x94" , CFLAG_NORMALCHAR }	/* U+7D4 (0xDF94) unchanged ;    */
	, { (unsigned char *)"\xDF\x95" , CFLAG_NORMALCHAR }	/* U+7D5 (0xDF95) unchanged ;    */
	, { (unsigned char *)"\xDF\x96" , CFLAG_NORMALCHAR }	/* U+7D6 (0xDF96) unchanged ;    */
	, { (unsigned char *)"\xDF\x97" , CFLAG_NORMALCHAR }	/* U+7D7 (0xDF97) unchanged ;    */
	, { (unsigned char *)"\xDF\x98" , CFLAG_NORMALCHAR }	/* U+7D8 (0xDF98) unchanged ;    */
	, { (unsigned char *)"\xDF\x99" , CFLAG_NORMALCHAR }	/* U+7D9 (0xDF99) unchanged ;    */
	, { (unsigned char *)"\xDF\x9A" , CFLAG_NORMALCHAR }	/* U+7DA (0xDF9A) unchanged ;    */
	, { (unsigned char *)"\xDF\x9B" , CFLAG_NORMALCHAR }	/* U+7DB (0xDF9B) unchanged ;    */
	, { (unsigned char *)"\xDF\x9C" , CFLAG_NORMALCHAR }	/* U+7DC (0xDF9C) unchanged ;    */
	, { (unsigned char *)"\xDF\x9D" , CFLAG_NORMALCHAR }	/* U+7DD (0xDF9D) unchanged ;    */
	, { (unsigned char *)"\xDF\x9E" , CFLAG_NORMALCHAR }	/* U+7DE (0xDF9E) unchanged ;    */
	, { (unsigned char *)"\xDF\x9F" , CFLAG_NORMALCHAR }	/* U+7DF (0xDF9F) unchanged ;    */
	, { (unsigned char *)"\xDF\xA0" , CFLAG_NORMALCHAR }	/* U+7E0 (0xDFA0) unchanged ;    */
	, { (unsigned char *)"\xDF\xA1" , CFLAG_NORMALCHAR }	/* U+7E1 (0xDFA1) unchanged ;    */
	, { (unsigned char *)"\xDF\xA2" , CFLAG_NORMALCHAR }	/* U+7E2 (0xDFA2) unchanged ;    */
	, { (unsigned char *)"\xDF\xA3" , CFLAG_NORMALCHAR }	/* U+7E3 (0xDFA3) unchanged ;    */
	, { (unsigned char *)"\xDF\xA4" , CFLAG_NORMALCHAR }	/* U+7E4 (0xDFA4) unchanged ;    */
	, { (unsigned char *)"\xDF\xA5" , CFLAG_NORMALCHAR }	/* U+7E5 (0xDFA5) unchanged ;    */
	, { (unsigned char *)"\xDF\xA6" , CFLAG_NORMALCHAR }	/* U+7E6 (0xDFA6) unchanged ;    */
	, { (unsigned char *)"\xDF\xA7" , CFLAG_NORMALCHAR }	/* U+7E7 (0xDFA7) unchanged ;    */
	, { (unsigned char *)"\xDF\xA8" , CFLAG_NORMALCHAR }	/* U+7E8 (0xDFA8) unchanged ;    */
	, { (unsigned char *)"\xDF\xA9" , CFLAG_NORMALCHAR }	/* U+7E9 (0xDFA9) unchanged ;    */
	, { (unsigned char *)"\xDF\xAA" , CFLAG_NORMALCHAR }	/* U+7EA (0xDFAA) unchanged ;    */
	, { (unsigned char *)"\xDF\xAB" , CFLAG_NORMALCHAR }	/* U+7EB (0xDFAB) unchanged ;    */
	, { (unsigned char *)"\xDF\xAC" , CFLAG_NORMALCHAR }	/* U+7EC (0xDFAC) unchanged ;    */
	, { (unsigned char *)"\xDF\xAD" , CFLAG_NORMALCHAR }	/* U+7ED (0xDFAD) unchanged ;    */
	, { (unsigned char *)"\xDF\xAE" , CFLAG_NORMALCHAR }	/* U+7EE (0xDFAE) unchanged ;    */
	, { (unsigned char *)"\xDF\xAF" , CFLAG_NORMALCHAR }	/* U+7EF (0xDFAF) unchanged ;    */
	, { (unsigned char *)"\xDF\xB0" , CFLAG_NORMALCHAR }	/* U+7F0 (0xDFB0) unchanged ;    */
	, { (unsigned char *)"\xDF\xB1" , CFLAG_NORMALCHAR }	/* U+7F1 (0xDFB1) unchanged ;    */
	, { (unsigned char *)"\xDF\xB2" , CFLAG_NORMALCHAR }	/* U+7F2 (0xDFB2) unchanged ;    */
	, { (unsigned char *)"\xDF\xB3" , CFLAG_NORMALCHAR }	/* U+7F3 (0xDFB3) unchanged ;    */
	, { (unsigned char *)"\xDF\xB4" , CFLAG_NORMALCHAR }	/* U+7F4 (0xDFB4) unchanged ;    */
	, { (unsigned char *)"\xDF\xB5" , CFLAG_NORMALCHAR }	/* U+7F5 (0xDFB5) unchanged ;    */
	, { (unsigned char *)"\xDF\xB6" , CFLAG_NORMALCHAR }	/* U+7F6 (0xDFB6) unchanged ;    */
	, { (unsigned char *)"\xDF\xB7" , CFLAG_NORMALCHAR }	/* U+7F7 (0xDFB7) unchanged ;    */
	, { (unsigned char *)"\xDF\xB8" , CFLAG_NORMALCHAR }	/* U+7F8 (0xDFB8) unchanged ;    */
	, { (unsigned char *)"\xDF\xB9" , CFLAG_NORMALCHAR }	/* U+7F9 (0xDFB9) unchanged ;    */
	, { (unsigned char *)"\xDF\xBA" , CFLAG_NORMALCHAR }	/* U+7FA (0xDFBA) unchanged ;    */
	, { (unsigned char *)"\xDF\xBB" , CFLAG_NORMALCHAR }	/* U+7FB (0xDFBB) unchanged ;    */
	, { (unsigned char *)"\xDF\xBC" , CFLAG_NORMALCHAR }	/* U+7FC (0xDFBC) unchanged ;    */
	, { (unsigned char *)"\xDF\xBD" , CFLAG_NORMALCHAR }	/* U+7FD (0xDFBD) unchanged ;    */
	, { (unsigned char *)"\xDF\xBE" , CFLAG_NORMALCHAR }	/* U+7FE (0xDFBE) unchanged ;    */
	, { (unsigned char *)"\xDF\xBF" , CFLAG_NORMALCHAR }	/* U+7FF (0xDFBF) unchanged ;    */
};
