<?php

/*
 * SphinxSearch Unicode maps
 * Courtesy of http://speeple.com/unicode-maps.txt
 */

namespace Alchemy\Phrasea\SearchEngine\SphinxSearch\Charset;

use Alchemy\Phrasea\SearchEngine\SphinxSearch\AbstractCharset;

class tamil extends AbstractCharset
{
    protected $name = 'Tamil';
    protected $table = '
    #################################################
    # Tamil
    U+0B94->U+0B92, U+0B85..U+0B8A, U+0B8E..U+0B90, U+0B92, U+0B93, U+0B95, U+0B99,
    U+0B9A, U+0B9C, U+0B9E, U+0B9F, U+0BA3, U+0BA4, U+0BA8..U+0BAA, U+0BAE..U+0BB9,
    U+0BE6..U+0BEF
';

}
