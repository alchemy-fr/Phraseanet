<?php

/*
 * SphinxSearch Unicode maps
 * Courtesy of http://speeple.com/unicode-maps.txt
 */

namespace Alchemy\Phrasea\SearchEngine\SphinxSearch\Charset;

use Alchemy\Phrasea\SearchEngine\SphinxSearch\AbstractCharset;

class thai extends AbstractCharset
{
    protected $name = 'Thai';
    protected $table = '
    #################################################
    # Thai
    U+0E01..U+0E30, U+0E32, U+0E33, U+0E40..U+0E46, U+0E50..U+0E5B
';

}
