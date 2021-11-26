<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="WebhookEvents", indexes={@ORM\Index(name="webhook_event_name", columns={"name"})})
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\WebhookEventRepository")
 */
class WebhookEvent
{
    const WEBHOOK_VERSION = 1;

    const NEW_FEED_ENTRY = 'new_feed_entry';
    const FEED_ENTRY_TYPE = 'feed_entry';

    const USER_REGISTRATION_GRANTED = 'user.registration.granted';
    const USER_REGISTRATION_REJECTED = 'user.registration.rejected';
    const USER_REGISTRATION_TYPE = 'user.registration';

    const RECORD_SUBDEF_CREATED = 'record.subdef.created';
    const RECORD_SUBDEF_FAILED = 'record.subdef.creation_failed';
    const RECORD_SUBDEFS_CREATED = 'record.subdefs.created';
    const RECORD_SUBDEF_TYPE = 'record.subdef';

    const ORDER_TYPE = 'order';
    const ORDER_CREATED = 'order.created';
    const ORDER_DELIVERED = 'order.delivered';
    const ORDER_DENIED = 'order.denied';

    const USER_TYPE = 'user';
    const USER_CREATED = 'user.created';
    const USER_DELETED = 'user.deleted';

    // const use only as name and type of a webhook
    const RECORD_TYPE = 'record';
    const RECORD_CREATED = 'record.created';
    const RECORD_EDITED = 'record.edited';
    const RECORD_DELETED = 'record.deleted';
    const RECORD_MEDIA_SUBSTITUTED = 'record.media_substituted';
    const RECORD_COLLECTION_CHANGED = 'record.collection_changed';
    const RECORD_STATUS_CHANGED = 'record.status_changed';

    public static $eventsAccessRight = [
            self::RECORD_CREATED    => [\ACL::ACCESS, \ACL::ACTIF],
            self::RECORD_EDITED     => [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANMODIFRECORD],
            self::RECORD_DELETED    => [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANDELETERECORD],
            self::RECORD_MEDIA_SUBSTITUTED  => [\ACL::ACCESS, \ACL::ACTIF, \ACL::IMGTOOLS],
            self::RECORD_COLLECTION_CHANGED => [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANADDRECORD, \ACL::CANDELETERECORD],
            self::RECORD_STATUS_CHANGED     => [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANMODIFRECORD],
            self::RECORD_SUBDEF_CREATED     => [\ACL::ACCESS, \ACL::ACTIF, [\ACL::IMGTOOLS, \ACL::CANADDRECORD]],// only one right required from the sub-array
            self::RECORD_SUBDEF_FAILED      => [\ACL::ACCESS, \ACL::ACTIF, [\ACL::IMGTOOLS, \ACL::CANADDRECORD]],
            self::USER_CREATED              => [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANADMIN],
            self::USER_DELETED              => [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANADMIN],
            self::USER_REGISTRATION_GRANTED => [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANADMIN],
            self::USER_REGISTRATION_REJECTED=> [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANADMIN],
            self::NEW_FEED_ENTRY            => [\ACL::ACCESS, \ACL::ACTIF, \ACL::BAS_CHUPUB],
            self::ORDER_CREATED             => [\ACL::ACCESS, \ACL::ACTIF, \ACL::ORDER_MASTER],
            self::ORDER_DELIVERED           => [\ACL::ACCESS, \ACL::ACTIF, \ACL::ORDER_MASTER],
            self::ORDER_DENIED              => [\ACL::ACCESS, \ACL::ACTIF, \ACL::ORDER_MASTER]
    ];

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64, nullable=false)
     */
    private $name;

    /**
     *  @ORM\Column(type="string", length=64, nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(type="json_array", nullable=false)
     */
    private $data;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default" = 0})
     */
    private $processed = false;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * List of collection base_id concerned
     * @var array
     *
     * @ORM\Column(name="collection_base_ids", type="json_array", nullable=true)
     */
    private $collectionBaseIds;

    /**
     * @param \DateTime $created
     *
     * @return WebhookEvent
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param array $data
     *
     * @return WebhookEvent
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param boolean $processed
     *
     * @return $this
     */
    public function setProcessed($processed)
    {
        $this->processed = (Boolean) $processed;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isProcessed()
    {
        return $this->processed;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param array $collectionBaseIds
     *
     * @return $this
     */
    public function setCollectionBaseIds(array $collectionBaseIds)
    {
        $this->collectionBaseIds = $collectionBaseIds;

        return $this;
    }

    /**
     * @return  array
     */
    public function getCollectionBaseIds()
    {
        return $this->collectionBaseIds;
    }
}
