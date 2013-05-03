<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Token;

use Alchemy\Phrasea\Application;

class TokenValidator
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Returns true if the token is valid
     *
     * @param type $token
     * @return boolean
     */
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
