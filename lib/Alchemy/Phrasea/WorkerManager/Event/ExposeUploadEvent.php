<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;

class ExposeUploadEvent extends SfEvent
{
    private $lst;
    private $exposeName;
    private $publicationId;
    private $accessTokenInfo;

    public function __construct($lst, $exposeName, $publicationId, $accessTokenInfo)
    {
        $this->lst              = $lst;
        $this->exposeName       = $exposeName;
        $this->publicationId    = $publicationId;
        $this->accessTokenInfo  = $accessTokenInfo;
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

    public function getAccessTokenInfo()
    {
        return $this->accessTokenInfo;
    }
}
