<?php

/*
 * SphinxSearch Unicode maps
 * Courtesy of http://speeple.com/unicode-maps.txt
 */

namespace Alchemy\Phrasea\SearchEngine\SphinxSearch\Charset;

use Alchemy\Phrasea\SearchEngine\SphinxSearch\AbstractCharset;

class armenian extends AbstractCharset
{
    protected $name = 'Armenian';
    protected $table = '
    ##################################################
    # Armenian
    U+0531..U+0556->U+0561..U+0586, U+0561..U+0586, U+0587
';

}
