<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed;

use Alchemy\Phrasea\Model\Entities\Feed;

/**
 * AggregateToken
 */
class AggregateToken
{
    /** @var integer */
    private $id;

    /** @var string */
    private $usrId;

    /** @var Aggregate */
    private $aggregatedFeed;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set usr_id
     *
     * @param  integer        $usrId
     * @return AggregateToken
     */
    public function setUsrId($usrId)
    {
        $this->usrId = $usrId;

        return $this;
    }

    /**
     * Get usr_id
     *
     * @return integer
     */
    public function getUsrId()
    {
        return $this->usrId;
    }

    /**
     * Set feed
     *
     * @param  Feed           $feed
     * @return AggregateToken
     */
    public function setFeed(Feed $feed = null)
    {
        $this->aggregatedFeed = $feed;

        return $this;
    }

    /**
     * Get feed
     *
     * @return Aggregate
     */
    public function getFeed()
    {
        return $this->aggregatedFeed;
    }
    /**
     * @var string
     */
    private $value;

    /**
     * Set value
     *
     * @param  string         $value
     * @return AggregateToken
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
