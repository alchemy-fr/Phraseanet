<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class API_V1_exception_methodnotallowed extends API_V1_exception_abstract
{
    protected static $details = 'Attempting to use POST with a GET-only endpoint, or vice-versa';

}
