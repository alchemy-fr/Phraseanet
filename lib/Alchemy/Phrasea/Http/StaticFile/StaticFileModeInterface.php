<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\StaticFile;

use Alchemy\Phrasea\Http\ServerModeInterface;
use Guzzle\Http\Url;

interface StaticFileModeInterface extends ServerModeInterface
{
    /**
     * @param $pathFile
     *
     * @return Url|null
     */
    public function getUrl($pathFile);
}
