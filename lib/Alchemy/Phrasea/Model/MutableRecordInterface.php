<?php

namespace Alchemy\Phrasea\Model;

interface MutableRecordInterface
{
    /** @param integer $baseId */
    public function setBaseId($baseId);

    /** @param string $sha256 */
    public function setSha256($sha256);

    /** @param integer $recordId */
    public function setRecordId($recordId);

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

    /** @param integer $status */
    public function setStatusBitField($status);

    /** @param \DateTime $updated */
    public function setUpdated(\DateTime $updated = null);

    /** @param string $type */
    public function setType($type);
}
