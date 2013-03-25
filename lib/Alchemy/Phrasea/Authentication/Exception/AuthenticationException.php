<?php

namespace Alchemy\Phrasea\Authentication\Exception;;

use Alchemy\Phrasea\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationException extends RuntimeException
{
    private $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
