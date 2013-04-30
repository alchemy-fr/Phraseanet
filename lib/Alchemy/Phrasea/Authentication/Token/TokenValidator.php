<?php

namespace Alchemy\Phrasea\Authentication\Token;

use Alchemy\Phrasea\Application;

class TokenValidator
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function isValid($token)
    {
        try {
            $datas = $this->app['tokens']->helloToken($token);

            return $datas['usr_id'];
        } catch (\Exception_NotFound $e) {

        }

        return false;
    }
}
