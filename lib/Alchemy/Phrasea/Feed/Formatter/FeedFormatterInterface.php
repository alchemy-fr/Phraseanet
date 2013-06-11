<?php

namespace Alchemy\Phrasea\Feed\Formatter;

use Alchemy\Phrasea\Feed\FeedInterface;

interface FeedFormatterInterface
{
    /**
     * Returns a string representation of the feed
     *
     * @param FeedInterface $feed
     * @param type $page
     * @param \User_Adapter $user
     * @param type $generator
     *
     * @return string
     */
    public function format(FeedInterface $feed, $page, \User_Adapter $user = null, $generator = 'Phraseanet');
}
