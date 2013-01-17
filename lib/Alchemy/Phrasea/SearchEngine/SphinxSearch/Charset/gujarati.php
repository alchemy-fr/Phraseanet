<?php

/*
 * SphinxSearch Unicode maps
 * Courtesy of http://speeple.com/unicode-maps.txt
 */

namespace Alchemy\Phrasea\SearchEngine\SphinxSearch\Charset;

use Alchemy\Phrasea\SearchEngine\SphinxSearch\AbstractCharset;

class gujarati extends AbstractCharset
{
    protected $name = 'Gujarati';
    protected $table = '
    ##################################################
    # Gujarati
    U+0A85..U+0A8C, U+0A8F, U+0A90, U+0A93..U+0AB0, U+0AB2, U+0AB3, U+0AB5..U+0AB9,
    U+0AE0, U+0AE1, U+0AE6..U+0AEF
';

}
