<?php

namespace Alchemy\Phrasea\Feed\Link;

use Alchemy\Phrasea\Feed\FeedInterface;

interface LinkGeneratorInterface
{
    /**
     *
     * @param \Alchemy\Phrasea\Feed\FeedInterface $feed
     * @param \User_Adapter $user
     * @param type $format
     * @param type $page
     * @param type $renew
     *
     * @throws InvalidArgumentException
     */
    public function generate(FeedInterface $feed, \User_Adapter $user, $format, $page = null, $renew = false);

    /**
     *
     * @param \Alchemy\Phrasea\Feed\FeedInterface $feed
     * @param type $format
     * @param type $page
     *
     * @throws InvalidArgumentException
     */
    public function generatePublic(FeedInterface $feed, $format, $page = null);

    public function supports(FeedInterface $feed);
}