<?php

namespace Entities;

use Alchemy\Phrasea\Application;
use Doctrine\ORM\Mapping as ORM;

/**
 * Feed
 */
class Feed
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var boolean
     */
    private $public;

    /**
     * @var string
     */
    private $icon_url;

    /**
     * @var integer
     */
    private $base_id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $publishers;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $entries;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->publishers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->entries = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
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
     * Set public
     *
     * @param boolean $public
     * @return Feed
     */
    public function setPublic($public)
    {
        $this->public = $public;
    
        return $this;
    }

    /**
     * Get public
     *
     * @return boolean 
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * Set icon_url
     *
     * @param string $iconUrl
     * @return Feed
     */
    public function setIconUrl($iconUrl)
    {
        $this->icon_url = $iconUrl;
    
        return $this;
    }

    /**
     * Get icon_url
     *
     * @return string 
     */
    public function getIconUrl()
    {
        return $this->icon_url;
    }

    /**
     * Set base_id
     *
     * @param integer $baseId
     * @return Feed
     */
    public function setBaseId($baseId)
    {
        $this->base_id = $baseId;
    
        return $this;
    }

    /**
     * Get base_id
     *
     * @return integer 
     */
    public function getBaseId()
    {
        return $this->base_id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Feed
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Feed
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Feed
     */
    public function setCreated($created)
    {
        $this->created = $created;
    
        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return Feed
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    
        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime 
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Add publishers
     *
     * @param \Entities\FeedPublisher $publishers
     * @return Feed
     */
    public function addPublisher(\Entities\FeedPublisher $publishers)
    {
        $this->publishers[] = $publishers;
    
        return $this;
    }

    /**
     * Remove publishers
     *
     * @param \Entities\FeedPublisher $publishers
     */
    public function removePublisher(\Entities\FeedPublisher $publishers)
    {
        $this->publishers->removeElement($publishers);
    }

    /**
     * Get publishers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPublishers()
    {
        return $this->publishers;
    }

    /**
     * Add entries
     *
     * @param \Entities\FeedEntry $entries
     * @return Feed
     */
    public function addEntrie(\Entities\FeedEntry $entries)
    {
        $this->entries[] = $entries;
    
        return $this;
    }

    /**
     * Remove entries
     *
     * @param \Entities\FeedEntry $entries
     */
    public function removeEntrie(\Entities\FeedEntry $entries)
    {
        $this->entries->removeElement($entries);
    }

    /**
     * Get entries
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getEntries()
    {
        return $this->entries;
    }
    
    public function getOwner()
    {   
        foreach ($this->getPublishers() as $publisher) {
            if ($publisher->isOwner()) {
                return $publisher;
            }
        }
    }
    
    public function isOwner(\User_Adapter $user)
    {
        $owner = $this->getOwner();
        if ($owner !== null && $user->get_id() === $owner->getId()) {
            return true;
        }
        
        return false;
    }
    
    public function getCollection(Application $app)
    {
        if ($this->getBaseId() !== null)
          return \collection::get_from_base_id($app, $this->getBaseId());
    }
}