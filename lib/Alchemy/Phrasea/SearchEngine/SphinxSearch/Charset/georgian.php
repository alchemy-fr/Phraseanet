<?php

/*
 * SphinxSearch Unicode maps
 * Courtesy of http://speeple.com/unicode-maps.txt
 */

namespace Alchemy\Phrasea\SearchEngine\SphinxSearch\Charset;

use Alchemy\Phrasea\SearchEngine\SphinxSearch\AbstractCharset;

class georgian extends AbstractCharset
{
    protected $name = 'Georgian';
    protected $table = '
    ##################################################
    # Georgian
    U+10FC->U+10DC, U+10D0..U+10FA, U+10A0..U+10C5->U+2D00..U+2D25, U+2D00..U+2D25
';

}
