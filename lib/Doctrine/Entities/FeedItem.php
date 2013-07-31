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
 * @ORM\Table(name="FeedItems", uniqueConstraints={@ORM\UniqueConstraint(name="lookup_unique_idx", columns={
 *         "entry_id","sbas_id","record_id"
 * }})
 * @ORM\Entity(repositoryClass="Repositories\FeedItemRepository")
 * @HasLifecycleCallbacks
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
     * @ORM\Column(type="integer")
     */
    private $record_id;

    /**
     * @ORM\Column(type="integer")
     */
    private $sbas_id;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created_on;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated_on;

    /**
     * @ORM\ManyToOne(targetEntity="FeedEntry", inversedBy="items", cascade={"persist"})
     * @ORM\JoinColumn(name="items", referencedColumnName="id")
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
        $this->record_id = $recordId;

        return $this;
    }

    /**
     * Get record_id
     *
     * @return integer
     */
    public function getRecordId()
    {
        return $this->record_id;
    }

    /**
     * Set sbas_id
     *
     * @param  integer  $sbasId
     * @return FeedItem
     */
    public function setSbasId($sbasId)
    {
        $this->sbas_id = $sbasId;

        return $this;
    }

    /**
     * Get sbas_id
     *
     * @return integer
     */
    public function getSbasId()
    {
        return $this->sbas_id;
    }

    /**
     * Set entry
     *
     * @param  \Entities\FeedEntry $entry
     * @return FeedItem
     */
    public function setEntry(\Entities\FeedEntry $entry = null)
    {
        $this->entry = $entry;

        return $this;
    }

    /**
     * Get entry
     *
     * @return \Entities\FeedEntry
     */
    public function getEntry()
    {
        return $this->entry;
    }
    /**
     * @var integer
     */
    private $ord;

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
     * @param  \DateTime $updatedOn
     * @return FeedItem
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

    /**
     * @PrePersist
     */
    public function setLastInFeedItem()
    {
        $this->setOrd($this->getEntry()->getItems()->count() + 1);
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
