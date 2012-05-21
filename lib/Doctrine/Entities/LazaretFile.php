<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entities\LazaretFile
 */
class LazaretFile
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $pathname
     */
    private $pathname;

    /**
     * @var integer $base_id
     */
    private $base_id;

    /**
     * @var string $uuid
     */
    private $uuid;

    /**
     * @var string $sha256
     */
    private $sha256;

    /**
     * @var datetime $created
     */
    private $created;

    /**
     * @var datetime $updated
     */
    private $updated;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    private $attributes;

    /**
     * @var Entities\LazaretSession
     */
    private $session;

    public function __construct()
    {
        $this->attributes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set pathname
     *
     * @param string $pathname
     * @return LazaretFile
     */
    public function setPathname($pathname)
    {
        $this->pathname = $pathname;
        return $this;
    }

    /**
     * Get pathname
     *
     * @return string
     */
    public function getPathname()
    {
        return $this->pathname;
    }

    /**
     * Set base_id
     *
     * @param integer $baseId
     * @return LazaretFile
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
     * Set uuid
     *
     * @param string $uuid
     * @return LazaretFile
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * Get uuid
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set sha256
     *
     * @param string $sha256
     * @return LazaretFile
     */
    public function setSha256($sha256)
    {
        $this->sha256 = $sha256;
        return $this;
    }

    /**
     * Get sha256
     *
     * @return string
     */
    public function getSha256()
    {
        return $this->sha256;
    }

    /**
     * Set created
     *
     * @param datetime $created
     * @return LazaretFile
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * Get created
     *
     * @return datetime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param datetime $updated
     * @return LazaretFile
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * Get updated
     *
     * @return datetime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Add attributes
     *
     * @param Entities\LazaretAttribute $attributes
     * @return LazaretFile
     */
    public function addLazaretAttribute(\Entities\LazaretAttribute $attributes)
    {
        $this->attributes[] = $attributes;
        return $this;
    }

    /**
     * Get attributes
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set session
     *
     * @param Entities\LazaretSession $session
     * @return LazaretFile
     */
    public function setSession(\Entities\LazaretSession $session = null)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * Get session
     *
     * @return Entities\LazaretSession
     */
    public function getSession()
    {
        return $this->session;
    }
    /**
     * @var string $originalName
     */
    private $originalName;


    /**
     * Set originalName
     *
     * @param string $originalName
     * @return LazaretFile
     */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;
        return $this;
    }

    /**
     * Get originalName
     *
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Get the Destination Collection
     *
     * @return \collection
     */
    public function getCollection()
    {
        return collection::get_from_base_id($this->getBaseId());
    }

    /**
     * Get an array of records that can be substitued by the Lazaret file
     *
     * @return array
     */
    public function getRecordsToSubstitute()
    {
        return \record_adapter::get_record_by_uuid(
            $this->getCollection()->get_databox(), $this->getUuid()
        );
    }
}