<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="WebhookEvents", indexes={@ORM\Index(name="name", columns={"name"})})
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\WebhookEventRepository")
 */
class WebhookEvent
{
    const NEW_FEED_ENTRY = 'new_feed_entry';
    const FEED_ENTRY_TYPE = 'feed_entry';

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
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $processed = false;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    public static function types()
    {
        return [self::FEED_ENTRY_TYPE];
    }

    public static function events()
    {
        return [self::NEW_FEED_ENTRY];
    }

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
     * @param $name
     *
     * @return WebhookEvent
     * @throws \InvalidArgumentException
     */
    public function setName($name)
    {
        if (!in_array($name, self::events())) {
            throw new \InvalidArgumentException("Invalid event name");
        }

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
     * @param $type
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setType($type)
    {
        if (!in_array($type, self::types())) {
            throw new \InvalidArgumentException("Invalid event name");
        }
        $this->type = $type;

        return $this;
    }
}
