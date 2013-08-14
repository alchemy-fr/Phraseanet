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
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="FeedPublishers")
 * @ORM\Entity(repositoryClass="Repositories\FeedPublisherRepository")
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
     * @ORM\Column(type="integer", name="usr_id")
     */
    private $usrId;

    /**
     * @ORM\Column(type="boolean")
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
     * Set usr_id
     *
     * @param  integer       $usrId
     * @return FeedPublisher
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
