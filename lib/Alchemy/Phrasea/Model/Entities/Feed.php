<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Entities;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Feed\FeedInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="Feeds")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\FeedRepository")
 */
class Feed implements FeedInterface
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $public = false;

    /**
     * @ORM\Column(type="boolean", name="icon_url", options={"default" = 0})
     */
    private $iconUrl = false;

    /**
     * @ORM\Column(type="integer", nullable=true, name="base_id")
     */
    private $baseId;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $subtitle;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", name="created_on")
     */
    private $createdOn;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", name="updated_on")
     */
    private $updatedOn;

    /**
     * @ORM\OneToMany(targetEntity="FeedPublisher", mappedBy="feed", cascade={"ALL"})
     */
    private $publishers;

    /**
     * @ORM\OneToMany(targetEntity="FeedEntry", mappedBy="feed", cascade={"ALL"})
     * @ORM\OrderBy({"createdOn" = "ASC"})
     */
    private $entries;

    /**
     * @ORM\OneToMany(targetEntity="FeedToken", mappedBy="feed", cascade={"ALL"})
     */
    private $tokens;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->publishers = new ArrayCollection();
        $this->entries = new ArrayCollection();
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
     * @param  boolean $public
     * @return Feed
     */
    public function setIsPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Get public
     *
     * @return boolean
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * Set icon_url
     *
     * @param  boolean $iconUrl
     * @return Feed
     */
    public function setIconUrl($iconUrl)
    {
        $this->iconUrl = $iconUrl;

        return $this;
    }

    /**
     * Get icon_url
     *
     * @return boolean
     */
    public function getIconUrl()
    {
        return $this->iconUrl;
    }

    /**
     * Set base_id
     *
     * @param  integer $baseId
     * @return Feed
     */
    public function setBaseId($baseId)
    {
        $this->baseId = $baseId;

        return $this;
    }

    /**
     * Get base_id
     *
     * @return integer
     */
    public function getBaseId()
    {
        return $this->baseId;
    }

    /**
     * Set title
     *
     * @param  string $title
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
     * @param  FeedPublisher $publishers
     * @return Feed
     */
    public function addPublisher(FeedPublisher $publishers)
    {
        $this->publishers[] = $publishers;

        return $this;
    }

    /**
     * Remove publishers
     *
     * @param FeedPublisher $publishers
     */
    public function removePublisher(FeedPublisher $publishers)
    {
        $this->publishers->removeElement($publishers);
    }

    /**
     * Get publishers
     *
     * @return Collection
     */
    public function getPublishers()
    {
        return $this->publishers;
    }

    /**
     * Add entries
     *
     * @param  FeedEntry $entries
     * @return Feed
     */
    public function addEntry(FeedEntry $entries)
    {
        $this->entries[] = $entries;

        return $this;
    }

    /**
     * Remove entries
     *
     * @param FeedEntry $entries
     */
    public function removeEntry(FeedEntry $entries)
    {
        $this->entries->removeElement($entries);
    }

    /**
     * Get entries
     *
     * @return FeedEntry[]|Collection
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * Returns the owner of the feed.
     *
     * @return FeedPublisher
     */
    public function getOwner()
    {
        foreach ($this->getPublishers() as $publisher) {
            if ($publisher->isOwner()) {
                return $publisher;
            }
        }
    }

    /**
     * Returns a boolean indicating whether the given user is the owner of the feed.
     *
     * @param User $user
     *
     * @return boolean
     */
    public function isOwner(User $user)
    {
        $owner = $this->getOwner();
        if ($owner !== null && $user->getId() === $owner->getUser()->getId()) {
            return true;
        }

        return false;
    }

    /**
     * Returns the collection to which the feed belongs.
     *
     * @param Application $app
     *
     * @return \collection
     */
    public function getCollection(Application $app)
    {
        if ($this->getBaseId() !== null) {
          return \collection::getByBaseId($app, $this->getBaseId());
        }
    }

    /**
     * Sets the collection.
     *
     * @param \collection $collection
     *
     * @return void
     */
    public function setCollection(\collection $collection = null)
    {
        if ($collection === null) {
            $this->baseId = null;

            return;
        }
        $this->baseId = $collection->get_base_id();
    }

    /**
     * Set created_on
     *
     * @param  \DateTime $createdOn
     * @return Feed
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    /**
     * Get created_on
     *
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Set updated_on
     *
     * @param  \DateTime $updatedOn
     * @return Feed
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updated_on
     *
     * @return \DateTime
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * Returns a boolean indicating whether the given user is a publisher of the feed.
     *
     * @param User $user
     *
     * @return boolean
     */
    public function isPublisher(User $user)
    {
        foreach ($this->getPublishers() as $publisher) {
            if ($publisher->getUser()->getId() == $user->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an instance of FeedPublisher matching to the given user.
     *
     * @param User $user
     *
     * @return FeedPublisher
     */
    public function getPublisher(User $user)
    {
        foreach ($this->getPublishers() as $publisher) {
            if ($publisher->getUser()->getId() == $user->getId()) {
                return $publisher;
            }
        }

        return null;
    }

    /**
     * Set subtitle
     *
     * @param  string $subtitle
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

    /**
     * Returns a boolean indicating whether the feed is aggregated.
     *
     * @return boolean
     */
    public function isAggregated()
    {
        return false;
    }

    /**
     * Returns the number of entries the feed contains
     *
     * @return integer
     */
    public function getCountTotalEntries()
    {
        return (count($this->entries));
    }

    /**
     * Returns a boolean indicating whether the given user has access to the feed.
     *
     * @param User        $user
     * @param Application $app
     *
     * @return boolean
     */
    public function hasAccess(User $user, Application $app)
    {
        if ($this->getCollection($app) instanceof collection) {
            return $app->getAclForUser($user)->has_access_to_base($this->collection->get_base_id());
        }

        return true;
    }

    /**
     * Add tokens
     *
     * @param  FeedToken $tokens
     * @return Feed
     */
    public function addToken(FeedToken $tokens)
    {
        $this->tokens[] = $tokens;

        return $this;
    }

    /**
     * Remove tokens
     *
     * @param FeedToken $tokens
     */
    public function removeToken(FeedToken $tokens)
    {
        $this->tokens->removeElement($tokens);
    }

    /**
     * Get tokens
     *
     * @return Collection
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * Add entries
     *
     * @param  FeedEntry $entries
     * @return Feed
     */
    public function addEntrie(FeedEntry $entries)
    {
        $this->entries[] = $entries;

        return $this;
    }

    /**
     * Remove entries
     *
     * @param FeedEntry $entries
     */
    public function removeEntrie(FeedEntry $entries)
    {
        $this->entries->removeElement($entries);
    }

    /**
     * Returns a boolean indicating whether the feed contains a given page, assuming a given page size.
     *
     * @param integer $page
     * @param integer $pageSize
     *
     * @return boolean
     */
    public function hasPage($pageNumber, $nbEntriesByPage)
    {
        if (0 >= $nbEntriesByPage) {
            throw new LogicException;
        }
        $count = $this->getCountTotalEntries();
        if (0 > $pageNumber && $pageNumber <= $count / $nbEntriesByPage) {
            return true;
        }

        return false;
    }

    /**
     *
     * Returns a boolean indicating whether a given user has access to the feed
     *
     * @param User                         $user
     * @param \Alchemy\Phrasea\Application $app
     *
     * @return boolean
     */
    public function isAccessible(User $user, Application $app)
    {
        $coll = $this->getCollection($app);
        if ($this->isPublic()
            || $coll === null
            || in_array($coll->get_base_id(), array_keys($app->getAclForUser($user)->get_granted_base()))) {
                return true;
            }

        return false;
    }
}
