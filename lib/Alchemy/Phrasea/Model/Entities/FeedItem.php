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

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="FeedItems", uniqueConstraints={@ORM\UniqueConstraint(name="lookup_unique_idx", columns={"entry_id","sbas_id","record_id"})})
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\FeedItemRepository")
 * @ORM\HasLifecycleCallbacks
 */
class FeedItem
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="integer", name="record_id")
     */
    private $recordId;

    /**
     * @ORM\Column(type="integer", name="sbas_id")
     */
    private $sbasId;

    /**
     * @ORM\Column(type="integer")
     */
    private $ord;

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
     * @ORM\ManyToOne(targetEntity="FeedEntry", inversedBy="items", cascade={"persist"})
     * @ORM\JoinColumn(name="entry_id", referencedColumnName="id")
     */
    private $entry;

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
     * Set record_id
     *
     * @param  integer  $recordId
     * @return FeedItem
     */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;

        return $this;
    }

    /**
     * Get record_id
     *
     * @return integer
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * Set sbas_id
     *
     * @param  integer  $sbasId
     * @return FeedItem
     */
    public function setSbasId($sbasId)
    {
        $this->sbasId = $sbasId;

        return $this;
    }

    /**
     * Get sbas_id
     *
     * @return integer
     */
    public function getSbasId()
    {
        return $this->sbasId;
    }

    /**
     * Set entry
     *
     * @param  FeedEntry $entry
     * @return FeedItem
     */
    public function setEntry(FeedEntry $entry = null)
    {
        $this->entry = $entry;

        return $this;
    }

    /**
     * Get entry
     *
     * @return FeedEntry
     */
    public function getEntry()
    {
        return $this->entry;
    }

    /**
     * Set ord
     *
     * @param  integer  $ord
     * @return FeedItem
     */
    public function setOrd($ord)
    {
        $this->ord = $ord;

        return $this;
    }

    /**
     * Get ord
     *
     * @return integer
     */
    public function getOrd()
    {
        return $this->ord;
    }

    /**
     * Set created_on
     *
     * @param  \DateTime $createdOn
     * @return FeedItem
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
     * @return FeedItem
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
     * @ORM\PrePersist
     */
    public function setLastInFeedItem()
    {
        $this->setOrd($this->getEntry()->getItems()->count());
    }

    /**
     * Returns the record_adapter associated to this FeedItem.
     *
     * @param Application $app
     *
     * @return \record_adapter
     */
    public function getRecord(Application $app)
    {
        return new \record_adapter($app, $this->getSbasId(), $this->getRecordId(), $this->getOrd());
    }
}
