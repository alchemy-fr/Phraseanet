<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PostAuthenticate extends AuthenticationEvent
{
    private $user;
    private $response;

    public function __construct(Request $request, Response $response, User $user, Context $context)
    {
        parent::__construct($request, $context);

        $this->response = $response;
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }
}
