<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Authentication\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\Event as SfEvent;

class PostAuthenticate extends SfEvent
{
    private $context;
    private $user;
    private $request;
    private $response;

    public function __construct(Request $request, Response $response, \User_Adapter $user, Context $context)
    {
        $this->request = $request;
        $this->response = $response;
        $this->user = $user;
        $this->context = $context;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    public function getContext()
    {
        return $this->context;
    }
}
