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

class NullMode implements H264Interface
{
    /**
     * {@inheritdoc}
     */
    public function getUrl($pathfile)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualHostConfiguration()
    {
        return "\n";
    }
}
