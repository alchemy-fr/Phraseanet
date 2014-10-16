<?php

namespace Alchemy\Phrasea\Model\Entities;

/**
 * Interface RecordInterface
 *
 *
 *                            +---------------------+                        +---------------------+
 *                            | Application Box XXX |                        | Application Box YYY |
 *                            +---------------------+                        +---------------------+
 *                            | Users               |                        | Users               |
 *                            | ~~~~~~~~~~~~~~~~~~~ |                        | ~~~~~~~~~~~~~~~~~~~ |
 *                            | Rights (ACL)        |                        | Rights (ACL)        |
 *                            | ~~~~~~~~~~~~~~~~~~~ |                        | ~~~~~~~~~~~~~~~~~~~ |
 *                            | Baskets, Feeds,     |                        | Baskets, Feeds,     |
 *                            | Publications etc... |                        | Publications etc... |
 *                            +---------------------+                        +---------------------+
 *                            /                     \                         /
 *                           /                       \                       /
 *                          /                         \                     /
 *                         /                           \                   /
 *                        /                             \                 /
 *     +--------------------+                         +--------------------+
 *     | Data Box AAA       |                         | Data Box BBB       |
 *     +--------------------+                         +--------------------+
 *     | Collection (1)     |                         | Collection (1)     |
 *     | Collection (2)     |                         | Collection (2)     |
 *     | ...                |                         | ...                |
 *     | Collection (n)     |                         | Collection (n)     |
 *     | ~~~~~~~~~~~~~~~~~  |                         | ~~~~~~~~~~~~~~~~~  |
 *     |  Record (1)        |                         |  Record (1)        |
 *     |  Record (2)        |                         |  Record (2)        |
 *     |  ...               |                         |  ...               |
 *     |  Record (n)        |                         |  Record (n)        |
 *     +--------------------+                         +--------------------+
 *
 *
 * An appbox (which is a database) is the heart of the application as it contains information
 * about users, rights (ACL), publications, tasks and so on ...
 *
 * A databox (which is also a database) contains information about records and their documentary
 * structure (metadata). It also contains collections which is a subset that allows to organize
 * the records inside the databox.
 *
 * We could assimilate a databox to a wardrobe where collections would be the shelves and everything
 * you put inside would be a record.
 *
 * A databox can be mounted or unmounted to an appbox .
 *
 * An appbox can reference "n" databox.
 * A databox can be mounted on "n" appbox.
 *
 * This allows Phraseanet to share data between "n" instances of Phraseanet.
 *
 * The "databox_id" is the id of the databox from the appbox point of view, it is given when a new
 * databox is mounted on the appbox.
 *
 * A databox contains a set of records.
 * The "record_id" is the is of the record from the databox point of view.
 *
 * This "record_id" is no more unique from the appbox if two databox that have both records with the
 * same ids.
 *
 * Thus the unique id of a record from an application point of view is the concatenation of the
 * "databox_id' and the "record_id".
 *
 * A databox contains a set of collection. Each records belongs to one and only one collection.
 * The "collection_id" is the id of the collection from the databox point of view.
 *
 * This "collection_id" is no more unique from the appbox point of view if two databox
 * that have both a collection with the same id are mounted to the same appbox.
 *
 * The unique id of a collection from an application point of view could be the concatenation of
 * the "databox_id" and "the collection_id" but when a databox is mounted on an appbox.
 * A unique id is created foreach collection referenced in the mounted databox.
 * This unique id id is called "base_id"
 *
 */
interface RecordInterface
{
    /**
     * The record id is the concatenation of the databox_id where the record belongs
     * And its record_id separated by an underscore.
     *
     * Eg: "1_256" for a record with an id of 256 in databox 1
     *
     * @return string
     */
    public function getId();

    /**
     * The unique id of the collection where belong the record.
     *
     * @return integer
     */
    public function getBaseId();

    /** @param integer $baseId */
    public function setBaseId($baseId);

    /**
     * The id of the collection where belong the record.
     *
     * @return integer
     */
    public function getCollectionId();

    /** @param integer $collectionId */
    public function setCollectionId($collectionId);

    /** @return \DateTime */
    public function getCreated();

    /** @param \DateTime $created */
    public function setCreated(\DateTime $created = null);

    /**
     * The id of the databox where belong the record.
     *
     * @return integer
     */
    public function getDataboxId();

    /** @param integer $databoxId */
    public function setDataboxId($databoxId);

    /** @return boolean */
    public function isStory();

    /** @param boolean $isStory */
    public function setIsStory($isStory);

    /** @return string */
    public function getMimeType();

    /** @param string $mimeType */
    public function setMimeType($mimeType);

    /** @return string */
    public function getOriginalName();

    /** @param string $originalName */
    public function setOriginalName($originalName);

    /**
     * The record id of the record.
     *
     * @return integer
     */
    public function getRecordId();

    /** @param integer $recordId */
    public function setRecordId($recordId);

    /** @return string */
    public function getSha256();

    /** @param string $sha256 */
    public function setSha256($sha256);

    /** @return string */
    public function getType();

    /** @param string $type */
    public function setType($type);

    /** @return \DateTime */
    public function getUpdated();

    /** @param \DateTime $updated */
    public function setUpdated(\DateTime $updated = null);

    /** @return string */
    public function getUuid();

    /** @param string $uuid */
    public function setUuid($uuid);

    /** @param string $status */
    public function setStatus($status);

    /** @return string */
    public function getStatus();
}
