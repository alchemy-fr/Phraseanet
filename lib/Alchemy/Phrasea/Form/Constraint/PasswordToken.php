<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Application;
use Symfony\Component\Validator\Constraint;

class PasswordToken extends Constraint
{
    private $app;
    private $random;
    private $message;

    public function __construct(Application $app, \random $random)
    {
        $this->message = _('The token provided is not valid anymore');
        $this->app = $app;
        $this->random = $random;
        parent::__construct();
    }

    public function isValid($token)
    {
        try {
            $datas = $this->random->helloToken($this->app, $token);
        } catch (\Exception_NotFound $e) {
            return false;
        }

        return \random::TYPE_PASSWORD === $datas['type'];
    }
}
