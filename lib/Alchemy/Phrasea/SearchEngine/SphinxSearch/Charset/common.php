<?php

/*
 * SphinxSearch Unicode maps
 * Courtesy of http://speeple.com/unicode-maps.txt
 */

namespace Alchemy\Phrasea\SearchEngine\SphinxSearch\Charset;

use Alchemy\Phrasea\SearchEngine\SphinxSearch\AbstractCharset;

class common extends AbstractCharset
{
    protected $name = 'Default';
    protected $table = '
    ##################################################
    # Common
    U+FF10..U+FF19->0..9, U+FF21..U+FF3A->a..z, U+FF41..U+FF5A->a..z, 0..9,
    A..Z->a..z, a..z
';

}
