<?php

namespace Alchemy\Phrasea\Feed;

interface FeedInterface
{

    public function getIconUrl();

    public function getTitle();

    public function getEntries($offset_start, $how_many);

    public function getSubtitle();

    public function isAggregated();

    public function getCreatedOn();

    public function getUpdatedOn();
}
