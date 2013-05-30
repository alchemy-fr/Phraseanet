<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed;

interface FeedLinkInterface
{
    /**
     * @return string
     */
    public function getMimetype();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return string
     */
    public function getURI();
}
