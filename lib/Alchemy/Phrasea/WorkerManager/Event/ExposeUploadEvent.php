<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;

class ExposeUploadEvent extends SfEvent
{
    private $lst;
    private $exposeName;
    private $publicationId;
    private $accessToken;

    public function __construct($lst, $exposeName, $publicationId, $accessToken)
    {
        $this->lst              = $lst;
        $this->exposeName       = $exposeName;
        $this->publicationId    = $publicationId;
        $this->accessToken      = $accessToken;
    }

    public function getLst()
    {
        return $this->lst;
    }

    public function getExposeName()
    {
        return $this->exposeName;
    }

    public function getPublicationId()
    {
        return $this->publicationId;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }
}
