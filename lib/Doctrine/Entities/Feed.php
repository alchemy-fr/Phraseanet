<?php

namespace Entities;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Feed\FeedInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Feed
 */
class Feed implements FeedInterface
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var boolean
     */
    private $public = false;

    /**
     * @var string
     */
    private $icon_url = false;

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
    private $subtitle;

    /**
     * @var \DateTime
     */
    private $created_on;

    /**
     * @var \DateTime
     */
    private $updated_on;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $publishers;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $entries;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $tokens;

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
        if (!$this->icon_url) {
            return '/skins/icons/rss32.gif';
        }

        return '/www/custom/feed_' . $this->getId() . '.jpg';
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
    public function addEntry(\Entities\FeedEntry $entries)
    {
        $this->entries[] = $entries;

        return $this;
    }

    /**
     * Remove entries
     *
     * @param \Entities\FeedEntry $entries
     */
    public function removeEntry(\Entities\FeedEntry $entries)
    {
        $this->entries->removeElement($entries);
    }

    /**
     * Get entries
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEntries($offset_start = 0, $how_many = null)
    {
        if (null === $how_many) {
            return $this->entries;
        }
        return $this->entries->slice($offset_start, $how_many);
    }

    public function getOwner()
    {
        foreach ($this->getPublishers() as $publisher) {
            if ($publisher->getOwner()) {
                return $publisher;
            }
        }
    }

    public function isOwner(\User_Adapter $user)
    {
        $owner = $this->getOwner();
        if ($owner !== null && $user->get_id() === $owner->getUsrId()) {
            return true;
        }

        return false;
    }

    public function getCollection(Application $app)
    {
        if ($this->getBaseId() !== null) {
          return \collection::get_from_base_id($app, $this->getBaseId());
        }
    }

    public function setCollection(\collection $collection = null)
    {
        if ($collection === null) {
            $this->base_id = null;
            return;
        }
        $this->base_id = $collection->get_base_id();
    }

    /**
     * Set created_on
     *
     * @param \DateTime $createdOn
     * @return Feed
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

    /**
     * Set updated_on
     *
     * @param \DateTime $updatedOn
     * @return Feed
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updated_on = $updatedOn;

        return $this;
    }

    /**
     * Get updated_on
     *
     * @return \DateTime
     */
    public function getUpdatedOn()
    {
        return $this->updated_on;
    }

    public function isPublisher(\User_Adapter $user)
    {
        foreach ($this->getPublishers() as $publisher) {
            if ($publisher->getUsrId() == $user->get_id()) {
                return true;
            }
        }

        return false;
    }

    public function getPublisher(\User_Adapter $user)
    {
        foreach ($this->getPublishers() as $publisher) {
            if ($publisher->getUsrId() == $user->get_id()) {
                return $publisher;
            }
        }

        return null;
    }

    /**
     * Set subtitle
     *
     * @param string $subtitle
     * @return Feed
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    /**
     * Get subtitle
     *
     * @return string
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    public function isAggregated()
    {
        return false;
    }

    public function getCountTotalEntries()
    {
        return (count($this->entries));
    }

    public function hasAccess(\User_Adapter $user, Application $app)
    {
        if ($this->getCollection($app) instanceof collection) {
            return $user->ACL()->has_access_to_base($this->collection->get_base_id());
        }

        return true;
    }

    /**
     * Add tokens
     *
     * @param \Entities\FeedToken $tokens
     * @return Feed
     */
    public function addToken(\Entities\FeedToken $tokens)
    {
        $this->tokens[] = $tokens;

        return $this;
    }

    /**
     * Remove tokens
     *
     * @param \Entities\FeedToken $tokens
     */
    public function removeToken(\Entities\FeedToken $tokens)
    {
        $this->tokens->removeElement($tokens);
    }

    /**
     * Get tokens
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTokens()
    {
        return $this->tokens;
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

    public function hasPage($page, $pageSize)
    {
        $count = $this->getCountTotalEntries();
        if ($page >= $count / $pageSize + $pageSize)
            return true;
        return false;
    }
}
