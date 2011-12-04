<?php

/*
 * SphinxSearch Unicode maps
 * Courtesy of http://speeple.com/unicode-maps.txt
 */

class sphinx_charsetTable_common extends sphinx_charsetTableAbstract
{

  protected $name = 'Default';
  protected $table = '
    ##################################################
    # Common
    U+FF10..U+FF19->0..9, U+FF21..U+FF3A->a..z, U+FF41..U+FF5A->a..z, 0..9,
    A..Z->a..z, a..z
';

}
