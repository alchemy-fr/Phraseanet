<?php

/*
 * SphinxSearch Unicode maps
 * Courtesy of http://speeple.com/unicode-maps.txt
 */

class sphinx_charsetTable_devanagari extends sphinx_charsetTableAbstract
{
    protected $name = 'Devanagari';
    protected $table = '
    ##################################################
    # Devanagari
    U+0929->U+0928, U+0931->U+0930, U+0934->U+0933, U+0958->U+0915, U+0959->U+0916,
    U+095A->U+0917, U+095B->U+091C, U+095C->U+0921, U+095D->U+0922, U+095E->U+092B,
    U+095F->U+092F, U+0904..U+0928, U+092A..U+0930, U+0932, U+0933, U+0935..U+0939,
    U+0960, U+0961, U+0966..U+096F, U+097B..U+097F
';

}
