<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core;

final class PhraseaEvents
{
    const LOGOUT = 'phrasea.logout';
    const API_OAUTH2_START = 'api.oauth2.start';
    const API_OAUTH2_END = 'api.oauth2.end';
    const API_LOAD_START = 'api.load.start';
    const API_LOAD_END = 'api.load.end';
    const API_RESULT = 'api.result';
}