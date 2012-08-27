<?php

/*
 * SphinxSearch Unicode maps
 * Courtesy of http://speeple.com/unicode-maps.txt
 */

namespace Alchemy\Phrasea\SearchEngine\SphinxSearch\Charset;

use Alchemy\Phrasea\SearchEngine\SphinxSearch\AbstractCharset;

class bengali extends AbstractCharset
{
    protected $name = 'Bengali';
    protected $table = '
    #################################################
    # Bengali
    U+09DC->U+09A1, U+09DD->U+09A2, U+09DF->U+09AF, U+09F0->U+09AC, U+09F1->U+09AC,
    U+0985..U+0990, U+0993..U+09B0, U+09B2, U+09B6..U+09B9, U+09CE, U+09E0, U+09E1,
    U+09E6..U+09EF
';

}
