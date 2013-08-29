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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="LazaretFiles")
 * @ORM\Entity(repositoryClass="Repositories\LazaretFileRepository")
 */
class LazaretFile
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private $filename;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private $thumbFilename;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $originalName;

    /**
     * @ORM\Column(type="integer")
     */
    private $base_id;

    /**
     * @ORM\Column(type="string", length=36)
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $sha256;

    /**
     * @ORM\Column(type="boolean")
     */
    private $forced = false;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * @ORM\OneToMany(targetEntity="LazaretAttribute", mappedBy="lazaretFile", cascade={"all"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $attributes;

    /**
     * @ORM\OneToMany(targetEntity="LazaretCheck", mappedBy="lazaretFile", cascade={"all"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $checks;

    /**
     * @ORM\ManyToOne(targetEntity="LazaretSession", inversedBy="files", cascade={"persist"})
     * @ORM\JoinColumn(name="lazaret_session_id", referencedColumnName="id")
     */
    private $session;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->attributes = new ArrayCollection();
        $this->checks = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  string      $filename
     * 
     * @return LazaretFile
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param  string      $thumbFilename
     * 
     * @return LazaretFile
     */
    public function setThumbFilename($thumbFilename)
    {
        $this->thumbFilename = $thumbFilename;

        return $this;
    }

    /**
     * @return string
     */
    public function getThumbFilename()
    {
        return $this->thumbFilename;
    }

    /**
     * @param  string      $originalName
     * 
     * @return LazaretFile
     */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * @param  integer     $baseId
     * @return LazaretFile
     */
    public function setBaseId($baseId)
    {
        $this->base_id = $baseId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getBaseId()
    {
        return $this->base_id;
    }

    /**
     * @return \collection
     */
    public function getCollection(Application $app)
    {
        return \collection::get_from_base_id($app, $this->getBaseId());
    }

    /**
     * @param  string      $uuid
     * 
     * @return LazaretFile
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param  string      $sha256
     * 
     * @return LazaretFile
     */
    public function setSha256($sha256)
    {
        $this->sha256 = $sha256;

        return $this;
    }

    /**
     * @return string
     */
    public function getSha256()
    {
        return $this->sha256;
    }

    /**
     * @param  boolean     $forced
     * 
     * @return LazaretFile
     */
    public function setForced($forced)
    {
        $this->forced = $forced;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getForced()
    {
        return $this->forced;
    }

    /**
     * @param  \DateTime   $created
     * 
     * @return LazaretFile
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param  \DateTime   $updated
     * 
     * @return LazaretFile
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param  LazaretAttribute $attributes
     * 
     * @return LazaretFile
     */
    public function addAttribute(LazaretAttribute $attributes)
    {
        $this->attributes[] = $attributes;

        return $this;
    }

    /**
     * @param LazaretAttribute $attributes
     */
    public function removeAttribute(LazaretAttribute $attributes)
    {
        $this->attributes->removeElement($attributes);
    }

    /**
     * @return LazaretAttribute[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param  LazaretCheck $checks
     * 
     * @return LazaretFile
     */
    public function addCheck(LazaretCheck $checks)
    {
        $this->checks[] = $checks;

        return $this;
    }

    /**
     * @param LazaretCheck $checks
     */
    public function removeCheck(LazaretCheck $checks)
    {
        $this->checks->removeElement($checks);
    }

    /**
     * @return Collection
     */
    public function getChecks()
    {
        return $this->checks;
    }

    /**
     * @param  LazaretSession $session
     * 
     * @return LazaretFile
     */
    public function setSession(LazaretSession $session = null)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @return LazaretSession
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Returns the associated record to substitute.
     * 
     * @return \record_adapter[]
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
}
