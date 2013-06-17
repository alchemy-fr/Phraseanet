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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            $data = $this->random->helloToken($token);
        } catch (NotFoundHttpException $e) {
            return false;
        }

        return \random::TYPE_PASSWORD === $data['type'];
    }
}
