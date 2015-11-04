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
 * @ORM\Table(
 *      name="FtpExportElements",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="unique_ftp_export", columns={"export_id","base_id","record_id", "subdef"})
 *      },
 *      indexes={
 *          @ORM\index(name="ftp_export_element_done", columns={"done"}),
 *          @ORM\index(name="ftp_export_element_error", columns={"error"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\FtpExportElementRepository")
 */
class FtpExportElement
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
     * @ORM\Column(type="integer", name="base_id")
     */
    private $baseId;

    /**
     * @ORM\Column(type="string")
     */
    private $subdef;

    /**
     * @ORM\Column(type="string")
     */
    private $filename;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $folder;

    /**
     * @var Boolean
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $error = false;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $done = false;

    /**
     * @ORM\ManyToOne(targetEntity="FtpExport", inversedBy="elements", cascade={"persist"})
     * @ORM\JoinColumn(name="export_id", referencedColumnName="id")
     */
    private $export;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $businessfields = false;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set recordId
     *
     * @param integer $recordId
     *
     * @return FtpExportElement
     */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;

        return $this;
    }

    /**
     * Get recordId
     *
     * @return integer
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * Set baseId
     *
     * @param integer $baseId
     *
     * @return FtpExportElement
     */
    public function setBaseId($baseId)
    {
        $this->baseId = $baseId;

        return $this;
    }

    /**
     * Get baseId
     *
     * @return integer
     */
    public function getBaseId()
    {
        return $this->baseId;
    }

    /**
     * Set subdef
     *
     * @param string $subdef
     *
     * @return FtpExportElement
     */
    public function setSubdef($subdef)
    {
        $this->subdef = $subdef;

        return $this;
    }

    /**
     * Get subdef
     *
     * @return string
     */
    public function getSubdef()
    {
        return $this->subdef;
    }

    /**
     * Set filename
     *
     * @param string $filename
     *
     * @return FtpExportElement
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
     * Set folder
     *
     * @param string $folder
     *
     * @return FtpExportElement
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Get folder
     *
     * @return string
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * Set error
     *
     * @param boolean $error
     *
     * @return FtpExportElement
     */
    public function setError($error)
    {
        $this->error = (Boolean) $error;

        return $this;
    }

    /**
     * Get error
     *
     * @return boolean
     */
    public function isError()
    {
        return $this->error;
    }

    /**
     * Set done
     *
     * @param boolean $done
     *
     * @return FtpExportElement
     */
    public function setDone($done)
    {
        $this->done = (Boolean) $done;

        return $this;
    }

    /**
     * Get done
     *
     * @return boolean
     */
    public function isDone()
    {
        return $this->done;
    }

    /**
     * Set businessfields
     *
     * @param boolean $businessfields
     *
     * @return FtpExportElement
     */
    public function setBusinessfields($businessfields)
    {
        $this->businessfields = (Boolean) $businessfields;

        return $this;
    }

    /**
     * Get businessfields
     *
     * @return boolean
     */
    public function isBusinessfields()
    {
        return $this->businessfields;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return FtpExportElement
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return FtpExportElement
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set export
     *
     * @param FtpExport $export
     *
     * @return FtpExportElement
     */
    public function setExport(FtpExport $export = null)
    {
        $this->export = $export;

        return $this;
    }

    /**
     * Get export
     *
     * @return FtpExport
     */
    public function getExport()
    {
        return $this->export;
    }
}
