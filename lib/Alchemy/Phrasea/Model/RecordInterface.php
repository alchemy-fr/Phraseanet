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
