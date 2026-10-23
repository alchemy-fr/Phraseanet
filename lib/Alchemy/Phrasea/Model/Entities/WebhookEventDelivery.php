<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: \Alchemy\Phrasea\Model\Repositories\WebhookEventDeliveryRepository::class)]
#[ORM\Table(name: 'WebhookEventDeliveries')]
#[ORM\UniqueConstraint(name: 'unique_app_delivery', columns: ['application_id', 'event_id'])]
class WebhookEventDelivery
{
    const MAX_DELIVERY_TRIES = 3;

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private $id;

    /**
     *
     * @return ApiApplication
     **/
    #[ORM\ManyToOne(targetEntity: ApiApplication::class)]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', nullable: false)]
    private $application;

    /**
     *
     * @return WebhookEvent
     **/
    #[ORM\ManyToOne(targetEntity: WebhookEvent::class)]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id', nullable: false)]
    private $event;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => 0])]
    private $delivered = false;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    private $deliveryTries = 0;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime')]
    private $created;

    #[ORM\OneToOne(targetEntity: WebhookEventPayload::class, mappedBy: 'delivery')]
    private $payload;

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
     * @param $delivered
     *
     * @return $this
     */
    public function setDelivered($delivered)
    {
        $this->delivered = (bool) $delivered;

        return $this;
    }

    /**
     * @return Boolean
     */
    public function isDelivered()
    {
        return $this->delivered;
    }

    /**
     * @return integer
     */
    public function getDeliveryTries()
    {
        return $this->deliveryTries;
    }

    /**
     * @param integer $try
     *
     * @return $this
     */
    public function setDeliverTries($try)
    {
        $this->deliveryTries = (int) $try;

        return $this;
    }

    /**
     * @return ApiApplication
     */
    public function getThirdPartyApplication()
    {
        return $this->application;
    }

    /**
     * @param ApiApplication $application
     *
     * @return $this
     */
    public function setThirdPartyApplication(ApiApplication $application)
    {
        $this->application = $application;

        return $this;
    }

    /**
     * @param WebhookEvent $event
     *
     * @return $this
     */
    public function setWebhookEvent(WebhookEvent $event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return WebhookEvent
     */
    public function getWebhookEvent()
    {
        return $this->event;
    }

    /**
     * @return WebhookEventPayload
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
