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

use Alchemy\Phrasea\Model\MutableRecordInterface;
use Alchemy\Phrasea\Model\RecordInterface;

/**
 * Record entity from elastic search
 */
class ElasticsearchRecord implements RecordInterface, MutableRecordInterface
{
    // ES data
    private $_index;
    private $_type;
    private $_id;
    private $_version;
    private $_score;

    // Phraseanet Record data
    private $databoxId;
    private $recordId;
    private $collectionId;
    private $baseId;
    private $collectionName;
    private $mimeType;
    private $title = [];
    private $originalName;
    private $updated;
    private $created;
    private $sha256;
    private $width;
    private $height;
    private $size;
    private $uuid;
    private $position;
    private $type;
    private $status;
    private $isStory;
    private $caption = [];
    private $privateCaption = [];
    private $exif = [];
    private $subdefs = [];
    private $flags = [];
    private $highlight = [];

    /**
     * @param string $index
     * @param string $type
     * @param string $id
     * @param int $version
     * @param float $score
     */
    public function setESData($index, $type, $id, $version, $score)
    {
        $this->_index = $index;
        $this->_type = $type;
        $this->_id = $id;
        $this->_version = $version;
        $this->_score = $score;
    }

    /**
     * @return string
     */
    public function getIndex()
    {
        return $this->_index;
    }

    /**
     * @return string
     */
    public function getScore()
    {
        return $this->_score;
    }

    /**
     * @return string
     */
    public function getElasticsearchType()
    {
        return $this->_type;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->_version;
    }


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
        $this->isStory = (bool)$isStory;
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

    /**
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collectionName;
    }

    /**
     * @param string $collectionName
     */
    public function setCollectionName($collectionName)
    {
        $this->collectionName = $collectionName;
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

    /** {@inheritdoc} */
    public function getWidth()
    {
        return $this->width;
    }

    /** {@inheritdoc} */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /** {@inheritdoc} */
    public function getHeight()
    {
        return $this->height;
    }

    /** {@inheritdoc} */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /** {@inheritdoc} */
    public function getSize()
    {
        return $this->size;
    }

    /** {@inheritdoc} */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @param string|null $locale
     *
     * @return string
     */
    public function getTitle($locale = null)
    {
        if ($locale && isset($this->title[$locale])) {
            return $this->title[$locale];
        }

        if (isset($this->title['default'])) {
            return $this->title['default'];
        }

        return $this->getOriginalName();
    }

    /**
     * Sets a collection of titles
     *
     * @param string[] $titles
     */
    public function setTitles(array $titles)
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

    public function getCaption(array $fields = null)
    {
        if (null === $fields) {
            return $this->caption;
        }

        $known = array_merge($this->caption, $this->privateCaption);

        $caption = [];
        foreach ($fields as $field) {
            if (isset($known[$field]) || array_key_exists($field, $known)) {
                $caption[$field] = $known[$field];
            }
        }

        return $caption;
    }

    public function setCaption(array $caption)
    {
        $this->caption = $caption;
    }

    /** @return array */
    public function getPrivateCaption()
    {
        return $this->privateCaption;
    }

    /**
     * @param array $privateCaption
     */
    public function setPrivateCaption(array $privateCaption)
    {
        $this->privateCaption = $privateCaption;
    }

    /** @return array */
    public function getExif()
    {
        return $this->exif;
    }

    public function setExif(array $exif)
    {
        $this->exif = $exif;
    }

    /** @return array */
    public function getSubdefs()
    {
        return $this->subdefs;
    }

    public function setSubdefs(array $subdefs)
    {
        $this->subdefs = $subdefs;
    }

    /**
     * @return array
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @param array $flags
     */
    public function setFlags(array $flags)
    {
        $this->flags = $flags;
    }

    public function setStatusBitField($status)
    {
        $this->status = $status;
    }

    /**
     *
     * @return integer
     */
    public function getStatusBitField()
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

    /**
     * @return array
     */
    public function getHighlight()
    {
        return $this->highlight;
    }

    /**
     * @param array $highlight
     */
    public function setHighlight(array $highlight)
    {
        $this->highlight = $highlight;
    }
}
