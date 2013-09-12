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

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="FeedEntries")
 * @ORM\Entity(repositoryClass="Repositories\FeedEntryRepository")
 */
class FeedEntry
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $subtitle;

    /**
     * @ORM\Column(type="string", length=128, name="author_name")
     */
    private $authorName;

    /**
     * @ORM\Column(type="string", length=128, name="author_email")
     */
    private $authorEmail;

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
     * @ORM\OneToMany(targetEntity="FeedItem", mappedBy="entry", cascade={"ALL"})
     * @ORM\OrderBy({"ord" = "ASC"})
     */
    private $items;

    /**
     * @ORM\ManyToOne(targetEntity="FeedPublisher", cascade={"persist"})
     * @ORM\JoinColumn(name="publisher_id", referencedColumnName="id")
     */
    private $publisher;

    /**
     * @ORM\ManyToOne(targetEntity="Feed", inversedBy="entries", cascade={"persist"})
     * @ORM\JoinColumn(name="feed_id", referencedColumnName="id")
     */
    private $feed;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->items = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set title
     *
     * @param  string    $title
     * @return FeedEntry
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
     * Set subtitle
     *
     * @param  string    $subtitle
     * @return FeedEntry
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
     * Set author_name
     *
     * @param  string    $authorName
     * @return FeedEntry
     */
    public function setAuthorName($authorName)
    {
        $this->authorName = $authorName;

        return $this;
    }

    /**
     * Get author_name
     *
     * @return string
     */
    public function getAuthorName()
    {
        return $this->authorName;
    }

    /**
     * Set author_email
     *
     * @param  string    $authorEmail
     * @return FeedEntry
     */
    public function setAuthorEmail($authorEmail)
    {
        $this->authorEmail = $authorEmail;

        return $this;
    }

    /**
     * Get author_email
     *
     * @return string
     */
    public function getAuthorEmail()
    {
        return $this->authorEmail;
    }

    /**
     * Set created
     *
     * @param  \DateTime $createdOn
     * @return FeedEntry
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Set updated
     *
     * @param  \DateTime $updated
     * @return FeedEntry
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * Add items
     *
     * @param  \Entities\FeedItem $items
     * @return FeedEntry
     */
    public function addItem(\Entities\FeedItem $items)
    {
        $this->items[] = $items;

        return $this;
    }

    /**
     * Remove items
     *
     * @param \Entities\FeedItem $items
     */
    public function removeItem(\Entities\FeedItem $items)
    {
        $this->items->removeElement($items);
    }

    /**
     * Get items
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Set publisher
     *
     * @param  \Entities\FeedPublisher $publisher
     * @return FeedEntry
     */
    public function setPublisher(\Entities\FeedPublisher $publisher = null)
    {
        $this->publisher = $publisher;

        return $this;
    }

    /**
     * Get publisher
     *
     * @return \Entities\FeedPublisher
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

    /**
     * Set feed
     *
     * @param  \Entities\Feed $feed
     * @return FeedEntry
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
     * Returns a boolean indicating whether the given User_Adapter is the publisher of the entry.
     *
     * @param \User_Adapter $user
     *
     * @return boolean
     */
    public function isPublisher(\User_Adapter $user)
    {
        if ($this->publisher) {
            if ($this->publisher->getUsrId() === $user->get_id()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the item from a given id.
     *
     * @param int $id
     *
     * @return null
     */
    public function getItem($id)
    {
        foreach ($this->items as $item) {
            if ($item->getId() == $id) {
                return ($item);
            }
        }

        return null;
    }
}
