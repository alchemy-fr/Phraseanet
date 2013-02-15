<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Event as SfEvent;

class PreAuthenticate extends SfEvent
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }
}
