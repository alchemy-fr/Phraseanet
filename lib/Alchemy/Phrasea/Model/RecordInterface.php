<?php

namespace Alchemy\Phrasea\Model;

interface RecordInterface
{
    /** @return string */
    public function getId();

    /**
     * The unique id of the collection where belong the record.
     *
     * @return integer
     */
    public function getBaseId();

    /**
     * The id of the collection where belong the record.
     *
     * @return integer
     */
    public function getCollectionId();

    /** @return \DateTime */
    public function getCreated();

    /**
     * The id of the databox where belong the record.
     *
     * @return integer
     */
    public function getDataboxId();

    /** @return boolean */
    public function isStory();

    /** @return string */
    public function getMimeType();

    /** @return string */
    public function getOriginalName();

    /** @return integer */
    public function getRecordId();

    /** @return string */
    public function getSha256();

    /** @return string */
    public function getType();

    /** @return \DateTime */
    public function getUpdated();

    /** @return string */
    public function getUuid();

    /** @return string */
    public function getStatus();
}

interface MutableRecordInterface
{
    /** @param integer $baseId */
    public function setBaseId($baseId);

    /** @param string $sha256 */
    public function setSha256($sha256);

    /** @param integer $recordId */
    public function setRecordId($recordId);;

    /** @param string $originalName */
    public function setOriginalName($originalName);

    /** @param string $mimeType */
    public function setMimeType($mimeType);

    /** @param boolean $isStory */
    public function setIsStory($isStory);

    /** @param \DateTime $created */
    public function setCreated(\DateTime $created = null);

    /** @param integer $databoxId */
    public function setDataboxId($databoxId);

    /** @param integer $collectionId */
    public function setCollectionId($collectionId);

    /** @param string $uuid */
    public function setUuid($uuid);

    /** @param string $status */
    public function setStatus($status);

    /** @param \DateTime $updated */
    public function setUpdated(\DateTime $updated = null);

    /** @param string $type */
    public function setType($type);
}
