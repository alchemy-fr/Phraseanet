<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\XSendFile;

use Symfony\Component\HttpFoundation\Request;

interface ModeInterface
{
    /**
     * Sets XSendFile headers.
     *
     * @params Request $request
     */
    public function setHeaders(Request $request);

    /**
     * Prints virtualhost configuration for current XSendFile mode.
     *
     * @return string
     */
    public function getVirtualHostConfiguration();
}
