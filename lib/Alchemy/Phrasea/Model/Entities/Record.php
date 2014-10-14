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

use Doctrine\Common\Collections\ArrayCollection;

class Record implements RecordInterface
{
    private $databoxId;
    private $recordId;
    private $collectionId;
    private $baseId;
    private $mimeType;
    private $title;
    private $originalName;
    private $updated;
    private $created;
    private $sha256;
    private $uuid;
    private $type;
    private $isStory;
    private $caption;
    private $exif;
    private $subdefs;

    public function getId()
    {
        return sprintf('%s_%s', $this->getDataboxId(), $this->getRecordId());
    }
    /**
     * @return mixed
     */
    public function getBaseId()
    {
        return $this->baseId;
    }

    /**
     * @param mixed $baseId
     */
    public function setBaseId($baseId)
    {
        $this->baseId = $baseId;
    }

    /**
     * @return mixed
     */
    public function getCollectionId()
    {
        return $this->collectionId;
    }

    /**
     * @param mixed $collectionId
     */
    public function setCollectionId($collectionId)
    {
        $this->collectionId = $collectionId;
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param mixed $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return mixed
     */
    public function getDataboxId()
    {
        return $this->databoxId;
    }

    /**
     * @param mixed $databoxId
     */
    public function setDataboxId($databoxId)
    {
        $this->databoxId = $databoxId;
    }

    /**
     * @return mixed
     */
    public function isStory()
    {
        return $this->isStory;
    }

    /**
     * @param mixed $isStory
     */
    public function setIsStory($isStory)
    {
        $this->isStory = $isStory;
    }

    /**
     * @return mixed
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @param mixed $mimeType
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
    }

    /**
     * @return mixed
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * @param mixed $originalName
     */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;
    }

    /**
     * @return mixed
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * @param mixed $recordId
     */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;
    }

    /**
     * @return mixed
     */
    public function getSha256()
    {
        return $this->sha256;
    }

    /**
     * @param mixed $sha256
     */
    public function setSha256($sha256)
    {
        $this->sha256 = $sha256;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param mixed $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return mixed
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param mixed $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return mixed
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * @param mixed $caption
     */
    public function setCaption(ArrayCollection $caption)
    {
        $this->caption = $caption;
    }

    /**
     * @return mixed
     */
    public function getExif()
    {
        return $this->exif;
    }

    /**
     * @param mixed $exif
     */
    public function setExif(ArrayCollection $exif)
    {
        $this->exif = $exif;
    }

    /**
     * @return mixed
     */
    public function getSubdefs()
    {
        return $this->subdefs;
    }

    /**
     * @param mixed $subdefs
     */
    public function setSubdefs(ArrayCollection $subdefs)
    {
        $this->subdefs = $subdefs;
    }
}