<?php

/*
 * SphinxSearch Unicode maps
 * Courtesy of http://speeple.com/unicode-maps.txt
 */

namespace Alchemy\Phrasea\SearchEngine\SphinxSearch\Charset;

use Alchemy\Phrasea\SearchEngine\SphinxSearch\AbstractCharset;

class limbu extends AbstractCharset
{
    protected $name = 'Limbu';
    protected $table = '
    #################################################
    # Limbu
    U+1900..U+191C, U+1930..U+1938, U+1946..U+194F
';

}
