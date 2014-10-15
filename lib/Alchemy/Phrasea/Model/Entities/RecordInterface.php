<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\Common\Collections\ArrayCollection;

interface RecordInterface
{
    public function getId();

    /**
     * @return mixed
     */
    public function getBaseId();

    /**
     * @param mixed $baseId
     */
    public function setBaseId($baseId);

    /**
     * @return mixed
     */
    public function getCollectionId();

    /**
     * @param mixed $collectionId
     */
    public function setCollectionId($collectionId);

    /**
     * @return mixed
     */
    public function getCreated();

    /**
     * @param mixed $created
     */
    public function setCreated($created);

    /**
     * @return mixed
     */
    public function getDataboxId();

    /**
     * @param mixed $databoxId
     */
    public function setDataboxId($databoxId);

    /**
     * @return mixed
     */
    public function isStory();

    /**
     * @param mixed $isStory
     */
    public function setIsStory($isStory);

    /**
     * @return mixed
     */
    public function getMimeType();

    /**
     * @param mixed $mimeType
     */
    public function setMimeType($mimeType);

    /**
     * @return mixed
     */
    public function getOriginalName();

    /**
     * @param mixed $originalName
     */
    public function setOriginalName($originalName);

    /**
     * @return mixed
     */
    public function getRecordId();

    /**
     * @param mixed $recordId
     */
    public function setRecordId($recordId);

    /**
     * @return mixed
     */
    public function getSha256();

    /**
     * @param mixed $sha256
     */
    public function setSha256($sha256);

    /**
     * @return mixed
     */
    public function getType();

    /**
     * @param mixed $type
     */
    public function setType($type);

    /**
     * @return mixed
     */
    public function getUpdated();

    /**
     * @param mixed $updated
     */
    public function setUpdated($updated);

    /**
     * @return mixed
     */
    public function getUuid();

    /**
     * @param mixed $uuid
     */
    public function setUuid($uuid);

    /**
     * @param $status
     */
    public function setStatus($status);

    /**
     * @return mixed
     */
    public function getStatus();
}