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
use Alchemy\Phrasea\Model\RecordInterface;
use Alchemy\Phrasea\Model\MutableRecordInterface;

/**
 * Record entity from elastic search
 */
class ElasticsearchRecord implements RecordInterface, MutableRecordInterface
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
    private $position;
    private $type;
    private $status;
    private $isStory;
    /** @var ArrayCollection */
    private $caption;
    /** @var ArrayCollection */
    private $exif;
    /** @var ArrayCollection */
    private $subdefs;

    /** {@inheritdoc} */
    public function getId()
    {
        return sprintf('%s_%s', $this->getDataboxId(), $this->getRecordId());
    }

    /** {@inheritdoc} */
    public function getBaseId()
    {
        return $this->baseId;
    }

    /** {@inheritdoc} */
    public function setBaseId($baseId)
    {
        $this->baseId = $baseId;
    }

    /** {@inheritdoc} */
    public function getCollectionId()
    {
        return $this->collectionId;
    }

    /** {@inheritdoc} */
    public function setCollectionId($collectionId)
    {
        $this->collectionId = $collectionId;
    }

    /** {@inheritdoc} */
    public function getCreated()
    {
        return $this->created;
    }

    /** {@inheritdoc} */
    public function setCreated(\DateTime $created = null)
    {
        $this->created = $created;
    }

    /** {@inheritdoc} */
    public function getDataboxId()
    {
        return $this->databoxId;
    }

    /** {@inheritdoc} */
    public function setDataboxId($databoxId)
    {
        $this->databoxId = $databoxId;
    }

    /** {@inheritdoc} */
    public function isStory()
    {
        return $this->isStory;
    }

    /** {@inheritdoc} */
    public function setIsStory($isStory)
    {
        $this->isStory = (bool) $isStory;
    }

    /** {@inheritdoc} */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /** {@inheritdoc} */
    public function setMimeType($mimeType)
    {
        if (null === $mimeType || '' === $mimeType) {
            $mimeType = 'application/octet-stream';
        }
        $this->mimeType = $mimeType;
    }

    /** {@inheritdoc} */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /** {@inheritdoc} */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;
    }

    /** {@inheritdoc} */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /** {@inheritdoc} */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;
    }

    /** {@inheritdoc} */
    public function getSha256()
    {
        return $this->sha256;
    }

    /** {@inheritdoc} */
    public function setSha256($sha256)
    {
        $this->sha256 = $sha256;
    }

    /**
     * @param string|null $locale
     *
     * @return string
     */
    public function getTitle($locale = null)
    {
        if ($locale && $this->title->containsKey($locale)) {
            return $this->title->get($locale);
        }

        if ($this->title->containsKey('default')) {
            return $this->title->get('default');
        }

        return $this->getOriginalName();
    }

    /**
     * Sets a collection of titles
     *
     * @param ArrayCollection $titles
     */
    public function setTitles(ArrayCollection $titles)
    {
        $this->title = $titles;
    }

    /**
     * Available types are ['document', 'audio', 'video', 'image', 'flash', 'map', 'unknown']
     */
    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    /**  @return \DateTime */
    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated(\DateTime $updated = null)
    {
        $this->updated = $updated;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /** @return ArrayCollection */
    public function getCaption()
    {
        return $this->caption;
    }

    public function setCaption(ArrayCollection $caption)
    {
        $this->caption = $caption;
    }

    /** @return ArrayCollection */
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

    /** @return ArrayCollection */
    public function getSubdefs()
    {
        return $this->subdefs;
    }

    /** @return ArrayCollection */
    public function setSubdefs(ArrayCollection $subdefs)
    {
        $this->subdefs = $subdefs;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get status of current current as 32 bits binary string
     *
     * Eg: 00000000001011100000000000011111
     *
     * Where the
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Returns the position of the record in the result set
     */
    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }
}
