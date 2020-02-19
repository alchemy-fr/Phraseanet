<?php

/*
 * This file is part of phrasea-4.1.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Table(name="WebhookEventPayloads")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\WebhookEventPayloadRepository")
 */
class WebhookEventPayload
{
    /**
     * @ORM\Column(type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @var string
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="WebhookEventDelivery", inversedBy="payload")
     * @ORM\JoinColumn(name="delivery_id", referencedColumnName="id")
     */
    private $delivery;

    /**
     * @ORM\Column(type="text", name="request")
     * @var string
     */
    private $requestPayload;

    /**
     * @ORM\Column(type="text", name="response")
     * @var string
     */
    private $responsePayload;

    /**
     * @ORM\Column(type="integer", name="status")
     * @var int
     */
    private $statusCode;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $headers;

    /**
     * @param WebhookEventDelivery $eventDelivery
     * @param string $requestPayload
     * @param string $responsePayload
     * @param int $statusCode
     * @param string $headers
     */
    public function __construct(WebhookEventDelivery $eventDelivery, $requestPayload, $responsePayload, $statusCode, $headers)
    {
        $this->id = Uuid::uuid4()->toString();

        $this->delivery = $eventDelivery;
        $this->requestPayload = $requestPayload;
        $this->responsePayload = $responsePayload;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return WebhookEventDelivery
     */
    public function getDelivery()
    {
        return $this->delivery;
    }

    /**
     * @return string
     */
    public function getRequestPayload()
    {
        return $this->requestPayload;
    }

    /**
     * @return string
     */
    public function getResponsePayload()
    {
        return $this->responsePayload;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getResponseHeaders()
    {
        return $this->headers;
    }
}
