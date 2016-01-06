<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http;

interface ServerModeInterface
{
    /**
     * Prints virtualhost configuration for current XSendFile mode.
     *
     * @return string
     */
    public function getVirtualHostConfiguration();
}
