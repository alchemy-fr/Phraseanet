<?php

namespace Entities;

use Alchemy\Phrasea\Application;
use Doctrine\ORM\Mapping as ORM;

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
    private $owner;

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
     * @param integer $usrId
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
     * @param boolean $owner
     * @return FeedPublisher
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    
        return $this;
    }

    /**
     * Get owner
     *
     * @return boolean 
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set feed
     *
     * @param \Entities\Feed $feed
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
    
    public function getUser(Application $app)
    {
        $user = \User_Adapter::getInstance($this->getUsrId(), $app);
        
        return $user;
    }

    /**
     * Set created_on
     *
     * @param \DateTime $createdOn
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