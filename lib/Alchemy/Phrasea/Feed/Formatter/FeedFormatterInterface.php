<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed\Formatter;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Feed\FeedInterface;

interface FeedFormatterInterface
{
    /**
     * Returns a string representation of the feed.
     *
     * @param FeedInterface $feed
     * @param type          $page
     * @param \User_Adapter $user
     * @param type          $generator
     * @param Application   $app
     *
     * @return string
     */
    public function format(FeedInterface $feed, $page, \User_Adapter $user = null, $generator = 'Phraseanet', Application $app);

    /**
     * Returns an HTTP Response containing a string representation of the feed.
     *
     * @param FeedInterface $feed
     * @param type          $page
     * @param \User_Adapter $user
     * @param type          $generator
     * @param Application   $app
     *
     * @return string
     */
    public function createResponse(Application $app, FeedInterface $feed, $page, \User_Adapter $user = null, $generator = 'Phraseanet');
}
