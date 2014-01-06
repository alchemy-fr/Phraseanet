<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed\Link;

interface FeedLinkInterface
{
    /**
     * Returns the mimetype of the link.
     *
     * @return string
     */
    public function getMimetype();

    /**
     * Returns the title of the link.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Returns the URI of the link.
     *
     * @return string
     */
    public function getURI();
}
