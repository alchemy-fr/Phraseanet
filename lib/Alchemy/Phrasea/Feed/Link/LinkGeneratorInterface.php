<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed\Link;

use Alchemy\Phrasea\Feed\FeedInterface;

interface LinkGeneratorInterface
{
    /**
     * Generates a FeedLink based on given FeedInterface and User_Adapter.
     *
     * @param FeedInterface $feed
     * @param \User_Adapter $user
     * @param type          $format
     * @param type          $page
     * @param type          $renew
     *
     * @return FeedLink
     *
     * @throws InvalidArgumentException
     */
    public function generate(FeedInterface $feed, \User_Adapter $user, $format, $page = null, $renew = false);

    /**
     * Generates a public FeedLink based on given FeedInterface.
     *
     * @param FeedInterface $feed
     * @param type          $format
     * @param type          $page
     *
     * @return FeedLink
     *
     * @throws InvalidArgumentException
     */
    public function generatePublic(FeedInterface $feed, $format, $page = null);

    /**
     * Returns an instance of FeedInterface supported by the class.
     *
     * @param \Alchemy\Phrasea\Feed\FeedInterface $feed
     *
     * @return FeedInterface
     */
    public function supports(FeedInterface $feed);
}
