<?php

namespace Entities;

use Alchemy\Phrasea\Application;

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

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    private $checks;

    /**
     * @var string $originalName
     */
    private $originalName;

    /**
     * @var boolean $forced
     */
    private $forced = false;

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
     * Set base_id
     *
     * @param  integer     $baseId
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
     * @param  string      $uuid
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
     * @param  string      $sha256
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
     * @param  datetime    $created
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
     * @param  datetime    $updated
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
     * @param  Entities\LazaretAttribute $attributes
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
     * @param  Entities\LazaretSession $session
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
     * Set originalName
     *
     * @param  string      $originalName
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
     * Add checks
     *
     * @param  Entities\LazaretCheck $checks
     * @return LazaretFile
     */
    public function addLazaretCheck(\Entities\LazaretCheck $checks)
    {
        $this->checks[] = $checks;

        return $this;
    }

    /**
     * Get checks
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getChecks()
    {
        return $this->checks;
    }

    /**
     * Set forced
     *
     * @param  boolean     $forced
     * @return LazaretFile
     */
    public function setForced($forced)
    {
        $this->forced = $forced;

        return $this;
    }

    /**
     * Get forced
     *
     * @return boolean
     */
    public function getForced()
    {
        return $this->forced;
    }

    /**
     * Get the Destination Collection
     *
     * @return \collection
     */
    public function getCollection(Application $app)
    {
        return \collection::get_from_base_id($app, $this->getBaseId());
    }

    /**
     * Get an array of records that can be substitued by the Lazaret file
     *
     * @return array
     */
    public function getRecordsToSubstitute(Application $app)
    {
        $ret = array();

        $shaRecords = \record_adapter::get_record_by_sha(
                $app, $this->getCollection($app)->get_sbas_id(), $this->getSha256()
        );

        $uuidRecords = \record_adapter::get_record_by_uuid(
                $app, $this->getCollection($app)->get_databox(), $this->getUuid()
        );

        $merged = array_merge($uuidRecords, $shaRecords);

        foreach ($merged as $record) {
            if ( ! in_array($record, $ret)) {
                $ret[] = $record;
            }
        }

        return $ret;
    }

    /**
     * @var string $filename
     */
    private $filename;

    /**
     * @var string $thumbFilename
     */
    private $thumbFilename;


    /**
     * Set filename
     *
     * @param string $filename
     * @return LazaretFile
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set thumbFilename
     *
     * @param string $thumbFilename
     * @return LazaretFile
     */
    public function setThumbFilename($thumbFilename)
    {
        $this->thumbFilename = $thumbFilename;
        return $this;
    }

    /**
     * Get thumbFilename
     *
     * @return string
     */
    public function getThumbFilename()
    {
        return $this->thumbFilename;
    }
}