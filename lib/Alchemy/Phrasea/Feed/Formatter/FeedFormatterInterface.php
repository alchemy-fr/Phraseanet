<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed\Formatter;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Feed\FeedInterface;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\HttpFoundation\Response;

interface FeedFormatterInterface
{
    /**
     * Returns a string representation of the feed.
     *
     * @param FeedInterface $feed
     * @param int           $page
     * @param User          $user
     * @param string        $generator
     * @param Application   $app
     *
     * @return string
     */
    public function format(FeedInterface $feed, $page, User $user = null, $generator = 'Phraseanet', Application $app);

    /**
     * Returns an HTTP Response containing a string representation of the feed.
     *
     * @param FeedInterface $feed
     * @param int           $page
     * @param User          $user
     * @param string        $generator
     * @param Application   $app
     *
     * @return Response
     */
    public function createResponse(Application $app, FeedInterface $feed, $page, User $user = null, $generator = 'Phraseanet');
}
