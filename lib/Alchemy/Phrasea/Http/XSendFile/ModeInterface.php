<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\XSendFile;

use Alchemy\Phrasea\Http\ServerModeInterface;
use Symfony\Component\HttpFoundation\Request;

interface ModeInterface extends ServerModeInterface
{
    /**
     * Sets XSendFile headers.
     *
     * @params Request $request
     */
    public function setHeaders(Request $request);
}
