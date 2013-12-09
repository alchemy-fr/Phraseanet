<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class API_V1_exception_unauthorized extends API_V1_exception_abstract
{
    protected static $details = 'The OAuth token was provided but was invalid.';

}
