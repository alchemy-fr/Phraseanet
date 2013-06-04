<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * FeedEntry
 */
class FeedEntry
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $subtitle;

    /**
     * @var string
     */
    private $author_name;

    /**
     * @var string
     */
    private $author_email;

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
    private $items;

    /**
     * @var \Entities\FeedPublisher
     */
    private $publisher;

    /**
     * @var \Entities\Feed
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
     * @param string $title
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
     * @param string $subtitle
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
     * @param string $authorName
     * @return FeedEntry
     */
    public function setAuthorName($authorName)
    {
        $this->author_name = $authorName;

        return $this;
    }

    /**
     * Get author_name
     *
     * @return string
     */
    public function getAuthorName()
    {
        return $this->author_name;
    }

    /**
     * Set author_email
     *
     * @param string $authorEmail
     * @return FeedEntry
     */
    public function setAuthorEmail($authorEmail)
    {
        $this->author_email = $authorEmail;

        return $this;
    }

    /**
     * Get author_email
     *
     * @return string
     */
    public function getAuthorEmail()
    {
        return $this->author_email;
    }

    /**
     * Set created
     *
     * @param \DateTime $createdOn
     * @return FeedEntry
     */
    public function setCreatedOn($createdOn)
    {
        $this->created_on = $createdOn;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->created_on;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return FeedEntry
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updated_on = $updatedOn;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdatedOn()
    {
        return $this->updated_on;
    }

    /**
     * Add items
     *
     * @param \Entities\FeedItem $items
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
     * @param \Entities\FeedPublisher $publisher
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
     * @param \Entities\Feed $feed
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

    public function isPublisher(\User_Adapter $user)
    {
        if ($this->publisher) {
            if ($this->publisher->getUsrId() === $user->get_id())
                return true;
        }

        return false;
    }

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