<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\H264PseudoStreaming;

use Alchemy\Phrasea\Http\ServerModeInterface;
use Guzzle\Http\Url;

interface H264Interface extends ServerModeInterface
{
    /**
     * @param $pathfile
     *
     * @return Url|null
     */
    public function getUrl($pathfile);
}
