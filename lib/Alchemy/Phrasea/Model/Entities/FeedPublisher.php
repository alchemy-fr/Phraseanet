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

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="FeedPublishers")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\FeedPublisherRepository")
 */
class FeedPublisher
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @return User
     **/
    private $user;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $owner = false;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", name="created_on")
     */
    private $createdOn;

    /**
     * @ORM\ManyToOne(targetEntity="Feed", inversedBy="publishers", cascade={"persist"})
     * @ORM\JoinColumn(name="feed_id", referencedColumnName="id")
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
     * @param User $user
     *
     * @return FeedPublisher
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
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
     * @param  Feed          $feed
     * @return FeedPublisher
     */
    public function setFeed(Feed $feed = null)
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * Get feed
     *
     * @return Feed
     */
    public function getFeed()
    {
        return $this->feed;
    }

    /**
     * Set created_on
     *
     * @param  \DateTime     $createdOn
     * @return FeedPublisher
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
}
