<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="WebhookEventDeliveries",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="unique_app_delivery",columns={"application_id", "event_id"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\WebhookEventDeliveryRepository")
 */
class WebhookEventDelivery
{
    const MAX_DELIVERY_TRIES = 3;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="ApiApplication")
     * @ORM\JoinColumn(name="application_id", referencedColumnName="id", nullable=false)
     *
     * @return ApiApplication
     **/
    private $application;

    /**
     * @ORM\ManyToOne(targetEntity="WebhookEvent")
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id", nullable=false)
     *
     * @return WebhookEvent
     **/
    private $event;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = 0})
     */
    private $delivered = false;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default" = 0})
     */
    private $deliveryTries = 0;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\OneToOne(targetEntity="WebhookEventPayload", mappedBy="delivery")
     */
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
        $this->delivered = (Boolean) $delivered;

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

    public function getDelivered(): ?bool
    {
        return $this->delivered;
    }

    public function setDeliveryTries(int $deliveryTries): self
    {
        $this->deliveryTries = $deliveryTries;

        return $this;
    }

    public function getApplication(): ?ApiApplication
    {
        return $this->application;
    }

    public function setApplication(?ApiApplication $application): self
    {
        $this->application = $application;

        return $this;
    }

    public function getEvent(): ?WebhookEvent
    {
        return $this->event;
    }

    public function setEvent(?WebhookEvent $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function setPayload(?WebhookEventPayload $payload): self
    {
        $this->payload = $payload;

        // set (or unset) the owning side of the relation if necessary
        $newDelivery = $payload === null ? null : $this;
        if ($newDelivery !== $payload->getDelivery()) {
            $payload->setDelivery($newDelivery);
        }

        return $this;
    }
}
