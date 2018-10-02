<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core;

final class PhraseaTokens
{
    const MAKE_SUBDEF = 1;
    const WRITE_META_DOC = 2;
    const WRITE_META_SUBDEF = 4;
    const WRITE_META = 6; // Equivalent to WRITE_META_DOC | WRITE_META_SUBDEF
    const TO_INDEX = 8;
    const INDEXING = 16;
}
