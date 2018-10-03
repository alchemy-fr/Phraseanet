<?php

namespace Alchemy\Phrasea\Model;

interface RecordInterface extends RecordReferenceInterface
{
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

    /** @return boolean */
    public function isStory();

    /** @return string */
    public function getMimeType();

    /** @return string */
    public function getOriginalName();

    /** @return string */
    public function getSha256();

    /** @return string */
    public function getType();

    /** @return \DateTime */
    public function getUpdated();

    /** @return string */
    public function getUuid();

    /** @return integer */
    public function getStatusBitField();

    /** @return array */
    public function getExif();

    /**
     * The width of the 'document' subdef
     *
     * @return integer|null
     */
    public function getWidth();

    /**
     * The height of the 'document' subdef
     *
     * @return integer|null
     */
    public function getHeight();

    /**
     * The size (filesize) of the 'document' subdef
     *
     * @return integer|null
     */
    public function getSize();

    /**
     * Get Caption with requested fields if exists.
     * @param array $fields Returns only public fields when null
     * @return array
     */
    public function getCaption(array $fields = null);
}
