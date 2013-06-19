<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

use Alchemy\Phrasea\Application;

/**
 * FeedPublisher
 */
class FeedPublisher
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $usr_id;

    /**
     * @var boolean
     */
    private $owner = false;

    /**
     * @var \DateTime
     */
    private $created_on;

    /**
     * @var \Entities\Feed
     */
    private $feed;

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
     * @param  integer       $usrId
     * @return FeedPublisher
     */
    public function setUsrId($usrId)
    {
        $this->usr_id = $usrId;

        return $this;
    }

    /**
     * Get usr_id
     *
     * @return integer
     */
    public function getUsrId()
    {
        return $this->usr_id;
    }

    /**
     * Set owner
     *
     * @param  boolean       $owner
     * @return FeedPublisher
     */
    public function setIsOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return boolean
     */
    public function isOwner()
    {
        return $this->owner;
    }

    /**
     * Set feed
     *
     * @param  \Entities\Feed $feed
     * @return FeedPublisher
     */
    public function setFeed(\Entities\Feed $feed = null)
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * Get feed
     *
     * @return \Entities\Feed
     */
    public function getFeed()
    {
        return $this->feed;
    }

    /**
     * Get user
     *
     * @return \User_Adapter
     */
    public function getUser(Application $app)
    {
        $user = \User_Adapter::getInstance($this->getUsrId(), $app);

        return $user;
    }

    /**
     * Set created_on
     *
     * @param  \DateTime     $createdOn
     * @return FeedPublisher
     */
    public function setCreatedOn($createdOn)
    {
        $this->created_on = $createdOn;

        return $this;
    }

    /**
     * Get created_on
     *
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->created_on;
    }
}
